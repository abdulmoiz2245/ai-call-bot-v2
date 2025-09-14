<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Call;
use App\Services\ElevenLabsService;
use App\Services\VoiceCallService;
use App\Services\LiveKitService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AgentController extends Controller
{
    public function __construct(
        private ElevenLabsService $elevenLabsService,
        private VoiceCallService $voiceCallService,
        private LiveKitService $liveKitService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $companyId = $request->input('company_id');
        
        $agents = Agent::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->with(['creator', 'company'])
            ->when($request->input('status'), function($q, $status) {
                if ($status === 'active') {
                    $q->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $q->where('is_active', false);
                }
            })
            ->when($request->input('voice'), fn($q, $voice) => $q->where('voice_id', $voice))
            ->when($request->input('search'), fn($q, $search) => 
                $q->where(fn($q) => 
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                )
            )
            ->orderBy($request->input('sort', 'created_at'), $request->input('direction', 'desc'))
            ->paginate($request->input('per_page', 25))
            ->withQueryString();

        return Inertia::render('Agents/Index', [
            'agents' => $agents,
            'filters' => $request->only(['status', 'search', 'sort', 'direction', 'voice']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        
        return Inertia::render('Agents/Create', [
            'voices' => $this->getAvailableVoices(),
            'canConnectElevenLabs' => $user->isSuperAdmin(),
            'elevenLabsAgents' => $user->isSuperAdmin() ? $this->elevenLabsService->getAgents() : [],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'role' => 'nullable|string|max:255',
            'tone' => 'nullable|string|max:255',
            'persona' => 'nullable|string',
            'voice_id' => 'required|string|max:100',
            'language' => 'nullable|string|max:5',
            'voice_settings' => 'nullable|array',
            'system_prompt' => 'required|string',
            'greeting_message' => 'nullable|string',
            'closing_message' => 'nullable|string',
            'transfer_conditions' => 'nullable|array',
            'conversation_flow' => 'nullable|array',
            'scripts' => 'nullable|array',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
            'elevenlabs_agent_id' => 'nullable|string',
            'is_elevenlabs_connected' => 'boolean',
        ]);

        // Only super admins can connect to ElevenLabs
        if (!Auth::user()->isSuperAdmin()) {
            unset($validated['elevenlabs_agent_id'], $validated['is_elevenlabs_connected']);
        }

        $agent = Agent::create([
            ...$validated,
            'company_id' => $request->input('company_id'),
            'created_by' => Auth::id(),
        ]);

        // Sync to ElevenLabs if connected and user is super admin
        if ($agent->is_elevenlabs_connected && Auth::user()->isSuperAdmin()) {
            $this->elevenLabsService->syncAgent($agent);
        }

        return redirect()->route('agents.show', $agent)
            ->with('success', 'Agent created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Agent $agent)
    {
        $agent->load(['creator', 'company', 'campaigns']);

        // Get agent performance stats
        $campaigns = $agent->campaigns()->with('calls')->get();
        
        $stats = [
            'total_campaigns' => $campaigns->count(),
            'active_campaigns' => $campaigns->where('status', 'active')->count(),
            'total_calls' => $campaigns->sum(fn($c) => $c->calls->count()),
            'avg_call_duration' => $campaigns->flatMap->calls->where('status', 'answered')->avg('duration') ?? 0,
            'success_rate' => $this->calculateSuccessRate($campaigns),
        ];

        return Inertia::render('Agents/Show', [
            'agent' => $agent,
            'campaigns' => $campaigns,
            'stats' => $stats,
            'voices' => $this->getAvailableVoices(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Agent $agent)
    {
        $user = Auth::user();
        
        return Inertia::render('Agents/Edit', [
            'agent' => $agent,
            'voices' => $this->getAvailableVoices(),
            'canConnectElevenLabs' => $user->isSuperAdmin(),
            'elevenLabsAgents' => $user->isSuperAdmin() ? $this->elevenLabsService->getAgents() : [],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Agent $agent)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'role' => 'nullable|string|max:255',
            'tone' => 'nullable|string|max:255',
            'persona' => 'nullable|string',
            'voice_id' => 'required|string|max:100',
            'language' => 'nullable|string|max:5',
            'voice_settings' => 'nullable|array',
            'system_prompt' => 'required|string',
            'greeting_message' => 'nullable|string',
            'closing_message' => 'nullable|string',
            'transfer_conditions' => 'nullable|array',
            'conversation_flow' => 'nullable|array',
            'scripts' => 'nullable|array',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
            'elevenlabs_agent_id' => 'nullable|string',
            'is_elevenlabs_connected' => 'boolean',
        ]);

        // Only super admins can modify ElevenLabs connection
        if (!Auth::user()->isSuperAdmin()) {
            unset($validated['elevenlabs_agent_id'], $validated['is_elevenlabs_connected']);
        }

        $agent->update($validated);

        // Sync to ElevenLabs if connected
        if ($agent->is_elevenlabs_connected) {
            $syncSuccess = $this->elevenLabsService->syncAgent($agent);
            
            if (!$syncSuccess && Auth::user()->isSuperAdmin()) {
                return redirect()->route('agents.show', $agent)
                    ->with('warning', 'Agent updated locally but failed to sync with ElevenLabs. Check logs for details.');
            }
        }

        return redirect()->route('agents.show', $agent)
            ->with('success', 'Agent updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Agent $agent)
    {
        // Check if agent has active campaigns
        if ($agent->campaigns()->where('status', 'active')->exists()) {
            return back()->with('error', 'Cannot delete agent with active campaigns.');
        }

        $agent->delete();

        return redirect()->route('agents.index')
            ->with('success', 'Agent deleted successfully.');
    }

    /**
     * Toggle agent active status.
     */
    public function toggleStatus(Agent $agent)
    {
        $agent->update(['is_active' => !$agent->is_active]);

        $status = $agent->is_active ? 'activated' : 'deactivated';
        
        return back()->with('success', "Agent {$status} successfully.");
    }

    /**
     * Test agent with sample conversation.
     */
    public function test(Request $request, Agent $agent)
    {
        $validated = $request->validate([
            'test_message' => 'required|string|max:500',
            'variables' => 'nullable|array',
            'variables.*' => 'nullable|string|max:255',
            'preview_only' => 'nullable|boolean',
        ]);

        // Get all variables used in the agent
        $allVariables = $agent->getAllVariables();
        $providedVariables = $validated['variables'] ?? [];
        
        // Process the system prompt and greeting with variables
        $processedSystemPrompt = $agent->getProcessedSystemPrompt($providedVariables);
        $processedGreeting = $agent->getProcessedGreetingMessage($providedVariables);

        // If this is just a preview request, return the processed content
        if ($validated['preview_only'] ?? false) {
            return response()->json([
                'processedSystemPrompt' => $processedSystemPrompt,
                'processedGreeting' => $processedGreeting,
                'variablesUsed' => $allVariables,
                'providedVariables' => $providedVariables,
            ]);
        }

        // This would integrate with ElevenLabs API to test the agent
        // For now, we'll return a mock response with processed content
        
        $response = [
            'message' => 'This is a test response from the agent.',
            'voice_sample_url' => 'https://example.com/test-voice-sample.mp3',
            'processing_time' => 1.2,
            'processed_system_prompt' => $processedSystemPrompt,
            'processed_greeting' => $processedGreeting,
            'variables_used' => $allVariables,
            'provided_variables' => $providedVariables,
        ];

        return back()->with('test_result', $response);
    }

    /**
     * Get variables used in agent prompts
     */
    public function getVariables(Agent $agent)
    {
        $variables = $agent->getAllVariables();
        
        return response()->json([
            'success' => true,
            'variables' => $variables,
        ]);
    }

    /**
     * Clone an existing agent.
     */
    public function clone(Agent $agent, Request $request)
    {
        $newAgent = $agent->replicate([
            'name',
            'created_at',
            'updated_at'
        ]);
        
        $newAgent->name = $agent->name . ' (Copy)';
        $newAgent->company_id = $request->input('company_id');
        $newAgent->created_by = Auth::id();
        $newAgent->is_active = false;
        $newAgent->save();

        return redirect()->route('agents.show', $newAgent)
            ->with('success', 'Agent cloned successfully.');
    }

    /**
     * Get available voices from ElevenLabs or other TTS providers.
     */
    private function getAvailableVoices(): array
    {
        // Fetch five English voices from ElevenLabs API
        $voices = $this->elevenLabsService->getVoices([
            'language' => 'en',
            'limit' => 5
        ]);

        // Map to expected format
        return collect($voices)
            ->filter(fn($v) => str_starts_with($v['language'], 'en'))
            ->take(5)
            ->map(fn($v) => [
            'id' => $v['voice_id'],
            'name' => $v['name'],
            'language' => $v['language'],
            'gender' => $v['gender'] ?? null,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Calculate success rate for agent campaigns.
     */
    private function calculateSuccessRate($campaigns): float
    {
        $totalCalls = $campaigns->flatMap->calls;
        
        if ($totalCalls->isEmpty()) {
            return 0;
        }

        $successfulCalls = $totalCalls->whereIn('status', ['answered', 'voicemail']);
        
        return ($successfulCalls->count() / $totalCalls->count()) * 100;
    }

    /**
     * Get ElevenLabs agents for connection
     */
    public function getElevenLabsAgents()
    {
        if (!Auth::user()->isSuperAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $agents = $this->elevenLabsService->getAgents();
        
        return response()->json(['agents' => $agents]);
    }

    /**
     * Connect agent to ElevenLabs
     */
    public function connectToElevenLabs(Request $request, Agent $agent)
    {
        if (!Auth::user()->isSuperAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'elevenlabs_agent_id' => 'required|string',
        ]);

        $agent->update([
            'elevenlabs_agent_id' => $validated['elevenlabs_agent_id'],
            'is_elevenlabs_connected' => true,
        ]);

        // Sync current settings to ElevenLabs
        $syncSuccess = $this->elevenLabsService->syncAgent($agent);

        if ($syncSuccess) {
            return response()->json([
                'success' => true,
                'message' => 'Agent connected to ElevenLabs successfully'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Agent connected but failed to sync settings. Check logs for details.'
            ], 422);
        }
    }

    /**
     * Disconnect agent from ElevenLabs
     */
    public function disconnectFromElevenLabs(Agent $agent)
    {
        if (!Auth::user()->isSuperAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $agent->update([
            'elevenlabs_agent_id' => null,
            'is_elevenlabs_connected' => false,
            'elevenlabs_settings' => null,
            'elevenlabs_last_synced' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Agent disconnected from ElevenLabs successfully'
        ]);
    }

    /**
     * Display the call test interface for an agent
     */
    public function callTest(Request $request, Agent $agent)
    {
        // Get variables from query parameters
        $variables = $request->input('variables', []);
        
        // Process the agent with variables
        $processedAgent = [
            'id' => $agent->id,
            'name' => $agent->name,
            'description' => $agent->description,
            'voice_id' => $agent->voice_id,
            'language' => $agent->language,
            'company_name' => $agent->company->name ?? '',
            'system_prompt' => $agent->getProcessedSystemPrompt($variables),
            'greeting_message' => $agent->getProcessedGreetingMessage($variables),
            'is_elevenlabs_connected' => !empty($agent->elevenlabs_agent_id),
            'elevenlabs_agent_id' => $agent->elevenlabs_agent_id,
            'variables' => $variables
        ];

        Log::info('Agent call test initialized with language support', [
            'agent_id' => $agent->id,
            'agent_name' => $agent->name,
            'language' => $agent->language,
            'voice_id' => $agent->voice_id,
            'variables_count' => count($variables)
        ]);

        // Prepare greeting audio
        $greetingAudio = null;
        $greetingReady = false;
        
        try {
            $greetingAudio = $this->voiceCallService->prepareGreetingAudio($agent, $variables);
            $greetingReady = !empty($greetingAudio);
            
            Log::info('Greeting audio prepared for call test', [
                'agent_id' => $agent->id,
                'has_greeting_audio' => $greetingReady,
                'variables' => $variables
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to prepare greeting audio for call test', [
                'agent_id' => $agent->id,
                'variables' => $variables,
                'error' => $e->getMessage()
            ]);
        }

        return Inertia::render('Agents/CallTest', [
            'agent' => $processedAgent,
            'variables' => $variables,
            'greeting_audio' => $greetingAudio,
            'greeting_ready' => $greetingReady
        ]);
    }

    /**
     * Get voice WebSocket URL for real-time conversation
     */
    public function getVoiceWebSocket(Request $request, Agent $agent)
    {
        $validated = $request->validate([
            'variables' => 'nullable|array',
            'variables.*' => 'nullable|string|max:255',
        ]);

        $variables = $validated['variables'] ?? [];

        try {
            if ($agent->is_elevenlabs_connected && $agent->elevenlabs_agent_id) {
                // Use ElevenLabs WebSocket for connected agents
                $websocketData = $this->elevenLabsService->getConversationalWebSocketUrl($agent, $variables);
                
                if (!$websocketData) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to get voice service WebSocket URL'
                    ], 500);
                }

                return response()->json([
                    'success' => true,
                    'websocket_url' => $websocketData['url'],
                    'session_id' => $websocketData['session_id'] ?? null,
                    'agent_id' => $agent->elevenlabs_agent_id,
                    'api_key' => $websocketData['api_key'] ?? null,
                    'connection_type' => 'voice_service',
                    'processed_prompts' => [
                        'system_prompt' => $agent->getProcessedSystemPrompt($variables),
                        'greeting_message' => $agent->getProcessedGreetingMessage($variables),
                    ]
                ]);
            } else {
                // Create session for custom WebSocket to store variables and processed prompts
                $sessionData = $this->voiceCallService->createSession($agent, 'custom-websocket', $variables);
                
                // Return custom WebSocket configuration for non-connected agents
                return response()->json([
                    'success' => true,
                    'websocket_url' => config('app.websocket_url', 'ws://localhost:8080') . '/call-test/' . $agent->id,
                    'session_id' => $sessionData['session_id'],
                    'agent_id' => $agent->id,
                    'connection_type' => 'custom',
                    'variables' => $variables,
                    'processed_prompts' => [
                        'system_prompt' => $sessionData['processed_system_prompt'],
                        'greeting_message' => $sessionData['processed_greeting_message'],
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to get voice WebSocket URL', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize voice conversation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get call session status for debugging
     */
    public function getCallStatus(string $sessionId)
    {
        try {
            $sessionData = $this->voiceCallService->getSessionData($sessionId);
            
            if (!$sessionData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'status' => $sessionData['status'] ?? 'unknown',
                'session_data' => $sessionData
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get call status', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get call status'
            ], 500);
        }
    }

    /**
     * Start a LiveKit voice call session
     */
    public function startLiveKitCall(Request $request, Agent $agent)
    {
        try {
            $validated = $request->validate([
                'phone_number' => 'required|string',
                'caller_name' => 'nullable|string',
                'dynamic_variables' => 'nullable|array', // Accept dynamic variables from frontend
            ]);

            // Create a voice call record
            $voiceCall = \App\Models\VoiceCall::create([
                'agent_id' => $agent->id,
                'phone_number' => $validated['phone_number'],
                'caller_name' => $validated['caller_name'] ?? null,
                'status' => 'initiated',
                'call_type' => 'outbound',
                'created_by' => Auth::id(),
                'company_id' => $agent->company_id,
            ]);

            // Start LiveKit session with dynamic variables
            $dynamicVariables = $validated['dynamic_variables'] ?? [];
            $sessionData = $this->liveKitService->startCall($voiceCall, $dynamicVariables);

            // Update voice call with LiveKit data
            $voiceCall->update([
                'session_data' => $sessionData,
                'status' => 'active'
            ]);

            Log::info('LiveKit call session started', [
                'agent_id' => $agent->id,
                'call_id' => $voiceCall->id,
                'room_name' => $sessionData['room_name']
            ]);

            return response()->json([
                'success' => true,
                'call_id' => $voiceCall->id,
                'session_data' => $sessionData,
                'message' => 'LiveKit call session started successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to start LiveKit call', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start LiveKit call: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get LiveKit room token for frontend
     */
    public function getLiveKitToken(Request $request)
    {
        try {
            $validated = $request->validate([
                'room_name' => 'required|string',
                'participant_name' => 'required|string',
            ]);

            $connectionInfo = $this->liveKitService->getConnectionInfo(
                $validated['room_name'],
                $validated['participant_name']
            );

            return response()->json([
                'success' => true,
                'connection_info' => $connectionInfo
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get LiveKit token', [
                'error' => $e->getMessage(),
                'request_data' => $validated ?? []
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get LiveKit token'
            ], 500);
        }
    }

    /**
     * End a LiveKit voice call session
     */
    public function endLiveKitCall(Request $request)
    {
        try {
            $validated = $request->validate([
                'call_id' => 'required|integer',
            ]);

            $voiceCall = \App\Models\VoiceCall::findOrFail($validated['call_id']);
            
            // End LiveKit session
            $this->liveKitService->endCall($voiceCall);

            // Update voice call status
            $voiceCall->update([
                'status' => 'completed',
                'ended_at' => now()
            ]);

            Log::info('LiveKit call session ended', [
                'call_id' => $voiceCall->id,
                'agent_id' => $voiceCall->agent_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'LiveKit call session ended successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to end LiveKit call', [
                'error' => $e->getMessage(),
                'call_id' => $validated['call_id'] ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to end LiveKit call'
            ], 500);
        }
    }

    /**
     * Start a LiveKit test call (no database record)
     */
    public function startLiveKitTest(Request $request, Agent $agent)
    {
        try {
            $validated = $request->validate([
                'variables' => 'nullable|array',
            ]);
            
            $dynamicVariables = $validated['variables'] ?? [];
            $sessionData = DB::transaction(function() use ($agent, $dynamicVariables) {
            
                $callLog = Call::create([
                    'agent_id' => $agent->id,
                    'company_id' => $agent->company_id,
                    'created_by' => Auth::id(),
                ]);

                $sessionData = $this->liveKitService->startTestCall($agent, $dynamicVariables);
                $callLog->update([
                    'call_sid' => $sessionData['room_name'] ?? null,
                    'started_at' => now(),
                    'metadata' => $sessionData['room_metadata'] ?? null,
                ]);

                Log::info('LiveKit test call session started', [
                    'agent_id' => $agent->id,
                    'room_name' => $sessionData['room_name'] ?? null,
                    'dynamic_variables' => $dynamicVariables
                ]);

                return $sessionData;

                
            });
            return response()->json([
                'success' => true,
                'session_data' => $sessionData,
                'message' => 'Call session started successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to start test call', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start test call: ' . $e->getMessage()
            ], 500);
        }
    }
}
