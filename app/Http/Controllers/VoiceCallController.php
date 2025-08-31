<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Services\VoiceCallService;
use App\Events\VoiceCallStatusUpdated;
use App\Jobs\ProcessAudioForAgent;
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

            $response = response()->json([
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

            return $response;

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
     * Handle audio chunks via private channel broadcasting
     */
    public function sendAudioChunk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'audio_data' => 'required|string',
            'user_id' => 'required|integer',
            'timestamp' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid audio chunk data',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sessionId = $request->input('session_id');
            $audioData = $request->input('audio_data');
            $userId = $request->input('user_id');
            $timestamp = $request->input('timestamp');

            // Get session data
            $sessionData = $this->voiceCallService->getSessionData($sessionId);
            if (!$sessionData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid session'
                ], 404);
            }

            // Process audio with ElevenLabs and get response
            $aiResponse = $this->voiceCallService->processAudioChunk($sessionId, $audioData, [
                'user_id' => $userId,
                'timestamp' => $timestamp
            ]);

            // Broadcast incoming audio to private channel
            broadcast(new \App\Events\VoiceCallAudioEvent(
                $sessionId,
                $audioData,
                'incoming',
                [
                    'user_id' => $userId,
                    'timestamp' => $timestamp,
                    'audio_length' => strlen($audioData)
                ]
            ));

            $response = [
                'success' => true,
                'message' => 'Audio chunk processed successfully',
                'session_id' => $sessionId
            ];

            // If we received an AI response, broadcast it and include in response
            if ($aiResponse && isset($aiResponse['audio_base64'])) {
                // Broadcast AI response audio to private channel
                broadcast(new \App\Events\VoiceCallAudioEvent(
                    $sessionId,
                    $aiResponse['audio_base64'],
                    'outgoing',
                    [
                        'response_type' => $aiResponse['type'] ?? 'audio',
                        'transcript' => $aiResponse['transcript'] ?? null,
                        'timestamp' => time()
                    ]
                ));

                $response['ai_response'] = $aiResponse;
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Failed to process audio chunk', [
                'session_id' => $request->input('session_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process audio chunk'
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

    /**
     * Trigger connected status broadcast (called after frontend subscribes to channel)
     */
    public function triggerConnectedStatus(Request $request): JsonResponse
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
            
            // Get session data
            $sessionData = $this->voiceCallService->getSessionData($sessionId);
            if (!$sessionData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found'
                ], 404);
            }

            // Broadcast connected status immediately since frontend is already subscribed
            $event = new \App\Events\VoiceCallStatusUpdated(
                $sessionData['channel_name'], 
                'connected',
                'Connected to voice AI service'
            );

            $result = broadcast($event);

            Log::info('Connected status broadcast triggered manually', [
                'session_id' => $sessionId,
                'channel_name' => $sessionData['channel_name'],
                'result' => $result
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Connected status broadcast sent successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to trigger connected status', [
                'session_id' => $request->input('session_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to trigger connected status'
            ], 500);
        }
    }

    /**
     * Handle WebSocket audio data received via whisper events
     */
    public function handleWhisperAudio(Request $request): JsonResponse
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
            
            $this->voiceCallService->handleAudioData($sessionId, $audioData);
            
            return response()->json([
                'success' => true,
                'message' => 'Audio data processed successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to handle whisper audio', [
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
     * Handle WebSocket end call received via whisper events
     */
    public function handleWhisperEndCall(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'reason' => 'nullable|string',
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
            $reason = $request->input('reason', 'client_ended');
            
            $this->voiceCallService->endCall($sessionId, $reason);
            
            return response()->json([
                'success' => true,
                'message' => 'Call ended successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to handle whisper end call', [
                'session_id' => $request->input('session_id'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to end call'
            ], 500);
        }
    }

    /**
     * Handle audio chunks via WebSocket and broadcast to private channel
     */
    public function handleAudioChunk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'audio_data' => 'required|string',
            'user_id' => 'sometimes|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid audio chunk data',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sessionId = $request->input('session_id');
            $audioData = $request->input('audio_data');
            $userId = $request->input('user_id');

            // Process audio chunk through service
            $aiResponse = $this->voiceCallService->processAudioChunk($sessionId, $audioData, [
                'user_id' => $userId,
                'timestamp' => time(),
                'source' => 'websocket'
            ]);

            // Broadcast incoming audio to private channel
            broadcast(new \App\Events\VoiceCallAudioEvent(
                $sessionId,
                $audioData,
                'incoming',
                ['user_id' => $userId, 'timestamp' => time()]
            ));

            // If AI responded, broadcast the response audio
            if ($aiResponse && isset($aiResponse['audio_base64'])) {
                broadcast(new \App\Events\VoiceCallAudioEvent(
                    $sessionId,
                    $aiResponse['audio_base64'],
                    'outgoing',
                    [
                        'type' => $aiResponse['type'] ?? 'ai_response',
                        'transcript' => $aiResponse['transcript'] ?? null,
                        'timestamp' => time()
                    ]
                ));
            }

            return response()->json([
                'success' => true,
                'message' => 'Audio chunk processed successfully',
                'ai_response' => $aiResponse
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to handle audio chunk', [
                'session_id' => $request->input('session_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process audio chunk'
            ], 500);
        }
    }

    /**
     * Handle audio file upload directly and process through AI pipeline
     */
    public function handleAudioFile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'audio_file' => 'required|file|mimes:wav,mp3,m4a|max:10240', // 10MB max
            'user_id' => 'sometimes|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid audio file data',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sessionId = $request->input('session_id');
            $audioFile = $request->file('audio_file');
            $userId = $request->input('user_id');

            // Store file in a persistent location for background processing
            $folderPath = "audio_processing/{$sessionId}";
            $fileName = 'speech_' . uniqid() . '.' . $audioFile->getClientOriginalExtension();
            $path = $audioFile->storeAs($folderPath, $fileName, 'local');
            $absolutePath = storage_path('app/private/' . $path);

            Log::info('Audio file uploaded and queued for processing', [
                'session_id' => $sessionId,
                'file_size' => $audioFile->getSize(),
                'mime_type' => $audioFile->getMimeType(),
                'storage_path' => $path,
                'absolute_path' => $absolutePath
            ]);

            // Verify file exists before dispatching job
            if (!file_exists($absolutePath)) {
                Log::error('Uploaded file not found after storage', [
                    'storage_path' => $path,
                    'absolute_path' => $absolutePath
                ]);
                return response()->json([
                    'success' => false, 
                    'message' => 'Failed to store uploaded file'
                ], 500);
            }

            $start = microtime(true);

            Log::info('Dispatching audio processing job start', [
                'session_id' => $sessionId,
                'file_path' => $absolutePath,
                'start_time' => $start
            ]);

            // Dispatch background job to process the audio
            ProcessAudioForAgent::dispatch(
                $sessionId,
                $absolutePath,
                [
                    'user_id' => $userId,
                    'timestamp' => time(),
                    'start_time' => time(),
                    'source' => 'file_upload',
                    'original_filename' => $audioFile->getClientOriginalName(),
                    'file_size' => $audioFile->getSize(),
                    'mime_type' => $audioFile->getMimeType()
                ]
            );

            // Return immediately - the job will broadcast the response when ready
            return response()->json([
                'success' => true,
                'message' => 'Audio file uploaded and queued for processing',
                'session_id' => $sessionId,
                'processing_status' => 'queued'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to handle audio file upload', [
                'session_id' => $request->input('session_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process audio file: ' . $e->getMessage()
            ], 500);
        }
    }
}
