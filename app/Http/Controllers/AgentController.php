<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AgentController extends Controller
{
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
        return Inertia::render('Agents/Create', [
            'voices' => $this->getAvailableVoices(),
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
            'voice_id' => 'required|string|max:100',
            'voice_settings' => 'nullable|array',
            'system_prompt' => 'required|string',
            'greeting_message' => 'nullable|string',
            'closing_message' => 'nullable|string',
            'transfer_conditions' => 'nullable|array',
            'conversation_flow' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $agent = Agent::create([
            ...$validated,
            'company_id' => $request->input('company_id'),
            'created_by' => Auth::id(),
        ]);

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
        return Inertia::render('Agents/Edit', [
            'agent' => $agent,
            'voices' => $this->getAvailableVoices(),
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
            'voice_id' => 'required|string|max:100',
            'voice_settings' => 'nullable|array',
            'system_prompt' => 'required|string',
            'greeting_message' => 'nullable|string',
            'closing_message' => 'nullable|string',
            'transfer_conditions' => 'nullable|array',
            'conversation_flow' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $agent->update($validated);

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
        ]);

        // This would integrate with ElevenLabs API to test the agent
        // For now, we'll return a mock response
        
        $response = [
            'message' => 'This is a test response from the agent.',
            'voice_sample_url' => 'https://example.com/test-voice-sample.mp3',
            'processing_time' => 1.2,
        ];

        return back()->with('test_result', $response);
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
        // This would fetch from ElevenLabs API
        // For now, return mock data
        return [
            ['id' => 'voice-1', 'name' => 'Sarah', 'language' => 'en-US', 'gender' => 'female'],
            ['id' => 'voice-2', 'name' => 'John', 'language' => 'en-US', 'gender' => 'male'],
            ['id' => 'voice-3', 'name' => 'Emma', 'language' => 'en-GB', 'gender' => 'female'],
            ['id' => 'voice-4', 'name' => 'David', 'language' => 'en-GB', 'gender' => 'male'],
        ];
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
}
