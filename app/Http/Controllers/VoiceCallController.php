<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Services\VoiceCallService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VoiceCallController extends Controller
{
    public function __construct(
        private VoiceCallService $voiceCallService
    ) {}

    /**
     * Initialize a voice call session
     */
    public function initialize(Request $request, Agent $agent): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'variables' => 'nullable|array',
            'variables.*' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid parameters',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if agent is connected to ElevenLabs
            if (!$agent->is_elevenlabs_connected || !$agent->elevenlabs_agent_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agent is not connected to voice AI service'
                ], 400);
            }

            $variables = $request->input('variables', []);
            
            // Create a unique channel name for this call session
            $channelName = 'voice-call.' . $agent->id . '.' . uniqid();
            
            // Create voice call session
            $sessionData = $this->voiceCallService->createSession($agent, $channelName, $variables);

            // Initialize ElevenLabs connection
            $connectionInitialized = $this->voiceCallService->initializeElevenLabsConnection($sessionData['session_id']);

            if (!$connectionInitialized) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to initialize voice AI connection'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'session_id' => $sessionData['session_id'],
                'channel_name' => $channelName,
                'reverb_config' => [
                    'key' => config('broadcasting.connections.reverb.key'),
                    'host' => config('broadcasting.connections.reverb.options.host'),
                    'port' => config('broadcasting.connections.reverb.options.port'),
                    'scheme' => config('broadcasting.connections.reverb.options.scheme')
                ],
                'message' => 'Voice call session initialized successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to initialize voice call', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize voice call: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle audio data from browser
     */
    public function sendAudio(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'audio_data' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid audio data',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sessionId = $request->input('session_id');
            $audioData = $request->input('audio_data');

            // Get session data
            $sessionData = $this->voiceCallService->getSessionData($sessionId);
            if (!$sessionData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid session'
                ], 404);
            }

            // Send audio to ElevenLabs
            $sent = $this->voiceCallService->sendAudioToElevenLabs($sessionId, $audioData);

            if (!$sent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send audio to voice AI service'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Audio sent successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send audio data', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process audio data'
            ], 500);
        }
    }

    /**
     * End a voice call session
     */
    public function endCall(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session ID',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sessionId = $request->input('session_id');
            
            $ended = $this->voiceCallService->endSession($sessionId);

            if (!$ended) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to end voice call session'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Voice call session ended successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to end voice call', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to end voice call'
            ], 500);
        }
    }

    /**
     * Get session status
     */
    public function getSessionStatus(Request $request, string $sessionId): JsonResponse
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
                'session_data' => $sessionData
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get session status', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get session status'
            ], 500);
        }
    }

    /**
     * Handle conversation metadata from ElevenLabs (webhook endpoint)
     */
    public function handleConversationMetadata(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->input('session_id');
            $metadata = $request->input('metadata', []);

            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session ID required'
                ], 400);
            }

            $this->voiceCallService->handleConversationMetadata($sessionId, $metadata);

            return response()->json([
                'success' => true,
                'message' => 'Metadata processed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to handle conversation metadata', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process metadata'
            ], 500);
        }
    }

    /**
     * Handle WebSocket audio data (called from frontend via WebSocket events)
     */
    public function handleWebSocketAudio(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'audio_data' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid parameters',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sessionId = $request->input('session_id');
            $audioData = $request->input('audio_data');

            $success = $this->voiceCallService->handleWebSocketAudioData($sessionId, $audioData);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process audio data'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Audio data processed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to handle WebSocket audio', [
                'session_id' => $request->input('session_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process audio data'
            ], 500);
        }
    }

    /**
     * Handle WebSocket call end (called from frontend via WebSocket events)
     */
    public function handleWebSocketEnd(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'reason' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid parameters',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sessionId = $request->input('session_id');
            $reason = $request->input('reason', 'user_ended');

            $success = $this->voiceCallService->handleWebSocketCallEnd($sessionId, $reason);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to end call'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Call ended successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to handle WebSocket call end', [
                'session_id' => $request->input('session_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to end call'
            ], 500);
        }
    }
}
