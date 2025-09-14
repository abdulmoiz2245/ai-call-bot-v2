<?php

namespace App\Services;

use App\Models\Agent;
use App\Services\OpenAIService;
use App\Services\ElevenLabsService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use React\EventLoop\Loop;
use React\Socket\Connector;
use React\Stream\WritableResourceStream;
use Ratchet\Client\Connector as WsConnector;
use React\Socket\ConnectionInterface;

class VoiceCallService
{
    private string $apiKey;
    private string $baseUrl;
    private array $activeConnections = [];

    public function __construct()
    {
        $config = config('services.elevenlabs');
        $this->apiKey = $config['api_key'];
        $this->baseUrl = $config['base_url'];
    }

    /**
     * Create a new voice call session
     */
    public function createSession(Agent $agent, string $channelName, array $variables = []): array
    {
        $sessionId = uniqid('voice_call_');
        
        // Generate processed prompts with variables
        $processedSystemPrompt = $agent->getProcessedSystemPrompt($variables);
        $processedGreetingMessage = $agent->getProcessedGreetingMessage($variables);
        
        // Store session data in Redis
        $sessionData = [
            'session_id' => $sessionId,
            'agent_id' => $agent->id,
            'elevenlabs_agent_id' => $agent->elevenlabs_agent_id,
            'channel_name' => $channelName,
            'variables' => $variables,
            'processed_system_prompt' => $processedSystemPrompt,
            'processed_greeting_message' => $processedGreetingMessage,
            'status' => 'initializing',
            'created_at' => now()->toISOString(),
            'elevenlabs_websocket_url' => null,
            'conversation_metadata' => null
        ];

        Redis::setex("voice_call:{$sessionId}", 3600, json_encode($sessionData));

        Log::info('Voice call session created with processed prompts', [
            'session_id' => $sessionId,
            'agent_id' => $agent->id,
            'channel_name' => $channelName,
            'variables' => $variables,
            'processed_system_prompt' => $processedSystemPrompt,
            'processed_greeting_message' => $processedGreetingMessage
        ]);

        return $sessionData;
    }

    /**
     * Initialize ElevenLabs connection for a session
     */
    public function initializeElevenLabsConnection(string $sessionId): bool
    {
        $sessionData = $this->getSessionData($sessionId);
        if (!$sessionData) {
            Log::error('Session not found for ElevenLabs initialization', ['session_id' => $sessionId]);
            return false;
        }

        try {
            $agent = Agent::find($sessionData['agent_id']);
            if (!$agent || !$agent->elevenlabs_agent_id) {
                throw new \Exception('Agent not found or not connected to ElevenLabs');
            }

            // Create ElevenLabs WebSocket URL
            $websocketUrl = 'wss://api.elevenlabs.io/v1/convai/conversation?agent_id=' . $agent->elevenlabs_agent_id;
            
            // Update session with WebSocket URL
            $sessionData['elevenlabs_websocket_url'] = $websocketUrl;
            $sessionData['status'] = 'connecting_elevenlabs';
            $this->updateSessionData($sessionId, $sessionData);

            // Create WebSocket connection to ElevenLabs
            $this->createElevenLabsWebSocketConnection($sessionId, $websocketUrl);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to initialize ElevenLabs connection', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            $this->updateSessionStatus($sessionId, 'error', $e->getMessage());
            return false;
        }
    }

    /**
     * Create WebSocket connection to ElevenLabs
     */
    private function createElevenLabsWebSocketConnection(string $sessionId, string $websocketUrl): void
    {
        try {
            $sessionData = $this->getSessionData($sessionId);
            if (!$sessionData) {
                throw new \Exception('Session data not found');
            }

            $agent = Agent::find($sessionData['agent_id']);
            if (!$agent || !$agent->elevenlabs_agent_id) {
                throw new \Exception('Agent not found or not connected to ElevenLabs');
            }

            // Use ElevenLabsService to create connection
            $elevenLabsService = app(ElevenLabsService::class);
            $connectionData = $elevenLabsService->createWebSocketConnection($sessionId, $agent->elevenlabs_agent_id);

            // Store the connection reference
            $this->activeConnections[$sessionId] = $connectionData;

            // Update session status
            $this->updateSessionStatus($sessionId, 'connected');

            Log::info('ElevenLabs WebSocket connection established', [
                'session_id' => $sessionId,
                'agent_id' => $agent->elevenlabs_agent_id,
                'websocket_url' => $connectionData['websocket_url']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create ElevenLabs WebSocket connection', [
                'session_id' => $sessionId,
                'websocket_url' => $websocketUrl,
                'error' => $e->getMessage()
            ]);
            
            $this->updateSessionStatus($sessionId, 'error', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send audio data to ElevenLabs
     */
    public function sendAudioToElevenLabs(string $sessionId, $audioData): bool
    {
        $sessionData = $this->getSessionData($sessionId);
        if (!$sessionData) {
            return false;
        }

        if (!isset($this->activeConnections[$sessionId])) {
            Log::warning('No active ElevenLabs connection for session', ['session_id' => $sessionId]);
            return false;
        }

        try {
            // Use ElevenLabsService to send audio
            $elevenLabsService = app(ElevenLabsService::class);
            $result = $elevenLabsService->sendAudioToWebSocket($sessionId, $audioData);

            Log::info('Audio sent to ElevenLabs', [
                'session_id' => $sessionId,
                'audio_size' => is_string($audioData) ? strlen($audioData) : 'binary',
                'has_response' => !empty($result)
            ]);

            // If we get a response, handle it
            if ($result) {
                $this->handleElevenLabsResponse($sessionId, $result);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send audio to ElevenLabs', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Handle response from ElevenLabs
     */
    private function handleElevenLabsResponse(string $sessionId, array $response): void
    {
        $sessionData = $this->getSessionData($sessionId);
        if (!$sessionData) {
            return;
        }

        // Broadcast the response to the browser
        broadcast(new \App\Events\VoiceCallMessage(
            $sessionData['channel_name'],
            $response
        ));
    }

    /**
     * Handle conversation metadata from ElevenLabs
     */
    public function handleConversationMetadata(string $sessionId, array $metadata): void
    {
        $sessionData = $this->getSessionData($sessionId);
        if (!$sessionData) {
            return;
        }

        $sessionData['conversation_metadata'] = $metadata;
        $sessionData['status'] = 'conversation_active';
        $this->updateSessionData($sessionId, $sessionData);

        Log::info('Conversation metadata received', [
            'session_id' => $sessionId,
            'metadata' => $metadata
        ]);

        // Broadcast metadata to browser
        broadcast(new \App\Events\VoiceCallMessage(
            $sessionData['channel_name'],
            [
                'type' => 'conversation_initiation_metadata',
                'conversation_initiation_metadata_event' => $metadata
            ]
        ));
    }

    /**
     * Process audio chunk from WebRTC and get AI response
     */
    public function processAudioChunk(string $sessionId, string $audioData, array $metadata = []): ?array
    {
        $sessionData = $this->getSessionData($sessionId);
        if (!$sessionData) {
            Log::error('Session not found for audio processing', ['session_id' => $sessionId]);
            return null;
        }

        try {
            // Store the audio chunk for multi-user access
            $this->storeAudioChunk($sessionId, $audioData, 'incoming', $metadata);

            // Send audio to ElevenLabs for processing (if connected)
            $aiResponse = $this->sendAudioToElevenLabsWebSocket($sessionId, $audioData);

            // If we get an AI response, store it too
            if ($aiResponse && isset($aiResponse['audio_base64'])) {
                $this->storeAudioChunk($sessionId, $aiResponse['audio_base64'], 'outgoing', [
                    'type' => $aiResponse['type'] ?? 'ai_response',
                    'transcript' => $aiResponse['transcript'] ?? null,
                    'timestamp' => time()
                ]);
            }

            Log::info('Audio chunk processed', [
                'session_id' => $sessionId,
                'input_audio_length' => strlen($audioData),
                'has_ai_response' => !empty($aiResponse),
                'user_id' => $metadata['user_id'] ?? null
            ]);

            return $aiResponse;

        } catch (\Exception $e) {
            Log::error('Failed to process audio chunk', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Process audio file directly through AI pipeline
     */
    public function processAudioFile(string $sessionId, string $filePath, array $metadata = []): ?array
    {
        $sessionData = $this->getSessionData($sessionId);
        // dd($sessionId);

        // if (!$sessionData) {
        //     Log::error('Session not found for audio file processing', ['session_id' => $sessionId]);
        //     return null;
        // }

        // try {
            Log::info('Processing audio file', [
                'session_id' => $sessionId,
                'file_path' => $filePath,
                'file_exists' => file_exists($filePath),
                'file_size' => file_exists($filePath) ? filesize($filePath) : 0
            ]);

            // Check if file exists before processing
            if (!file_exists($filePath)) {
                throw new \Exception("Audio file not found at path: {$filePath}");
            }

            $fileSize = filesize($filePath);
            if ($fileSize === 0) {
                throw new \Exception("Audio file is empty: {$filePath}");
            }

            // Read the file and convert to base64 for now 
            // (we'll use direct file processing with ElevenLabs later)
            $audioContent = file_get_contents($filePath);
            if ($audioContent === false) {
                throw new \Exception("Failed to read audio file: {$filePath}");
            }
            
            $audioData = base64_encode($audioContent);

            
            try {
                // Step 1: Get session data and agent for language support
                $sessionData = $this->getSessionData($sessionId);

                if (!$sessionData) {
                    Log::error('Session data not found', ['session_id' => $sessionId]);
                    return null;
                }

                $agent = Agent::find($sessionData['agent_id']);
                if (!$agent) {
                    Log::error('Agent not found', ['session_id' => $sessionId, 'agent_id' => $sessionData['agent_id']]);
                    return null;
                }
                
                // Step 2: Transcribe audio using ElevenLabs Speech-to-Text with language support
                $elevenLabsService = app(ElevenLabsService::class);
                $transcript = $elevenLabsService->transcribeAudio($audioData, 'audio/wav', $agent->language);
                
                if (!$transcript) {
                    Log::warning('Failed to transcribe audio with ElevenLabs', ['session_id' => $sessionId]);
                    return null;
                }

                Log::info('Audio transcribed successfully with ElevenLabs', [
                    'session_id' => $sessionId,
                    'transcript' => $transcript,
                    'language' => $agent->language
                ]);

                // Step 3: Get AI response using OpenAI ChatGPT

                // Get processed prompts with variables from session
                $variables = $sessionData['variables'] ?? [];
                $processedSystemPrompt = $agent->getProcessedSystemPrompt($variables);
                $processedGreetingMessage = $agent->getProcessedGreetingMessage($variables);

                // Get conversation history from session or cache
                $conversationHistory = $this->getConversationHistory($sessionId);
                
                // Log the processed prompts being sent to OpenAI
                Log::info('Sending processed prompts to OpenAI', [
                    'session_id' => $sessionId,
                    'agent_id' => $agent->id,
                    'processed_system_prompt' => $processedSystemPrompt,
                    'processed_greeting_message' => $processedGreetingMessage,
                    'conversation_history_count' => count($conversationHistory),
                    'user_transcript' => $transcript
                ]);
                
                $openaiService = app(OpenAIService::class);
                $aiResponse = $openaiService->generateResponse($transcript, $agent, $conversationHistory, $processedSystemPrompt, $agent->language);
                
                if (!$aiResponse) {
                    Log::warning('Failed to generate AI response', ['session_id' => $sessionId]);
                    return null;
                }

                Log::info('AI response generated successfully', [
                    'session_id' => $sessionId,
                    'response' => $aiResponse['text']
                ]);

                // Step 3: Convert AI response to speech using ElevenLabs TTS with caching
                $voiceId = $agent->voice_id ?: 'default';
                
                // Check if audio already exists in cache
                $audioBase64 = $this->getCachedAudio($voiceId, $aiResponse['text']);
                
                if ($audioBase64) {
                    Log::info('Using cached audio for TTS', [
                        'session_id' => $sessionId,
                        'voice_id' => $voiceId,
                        'text_preview' => substr($aiResponse['text'], 0, 100) . '...',
                        'cache_hit' => true
                    ]);
                } else {
                    // Generate new audio using ElevenLabs TTS with language support
                    $elevenLabsService = app(ElevenLabsService::class);
                    $audioBase64 = $elevenLabsService->textToSpeech($aiResponse['text'], $voiceId, [], $agent->language);
                    
                    if (!$audioBase64) {
                        Log::warning('Failed to convert text to speech with ElevenLabs', ['session_id' => $sessionId]);
                        return null;
                    }
                    
                    // Cache the newly generated audio
                    $this->cacheAudio($voiceId, $aiResponse['text'], $audioBase64);
                    
                    Log::info('Generated and cached new TTS audio', [
                        'session_id' => $sessionId,
                        'voice_id' => $voiceId,
                        'audio_length' => strlen($audioBase64),
                        'cache_hit' => false
                    ]);
                }

                Log::info('Text-to-speech processing completed', [
                    'session_id' => $sessionId,
                    'voice_id' => $voiceId,
                    'audio_length' => strlen($audioBase64),
                    'was_cached' => $this->getCachedAudio($voiceId, $aiResponse['text']) !== null
                ]);

                // Step 4: Update conversation history with full context
                $conversationUpdate = [
                    ['role' => 'user', 'content' => $transcript],
                    ['role' => 'assistant', 'content' => $aiResponse['text']]
                ];
                
                $this->updateConversationHistory($sessionId, $conversationUpdate);
                
                // Log conversation history update
                Log::info('Updated conversation history', [
                    'session_id' => $sessionId,
                    'new_messages_added' => count($conversationUpdate),
                    'total_history_messages' => count($this->getConversationHistory($sessionId)),
                    'user_message' => $transcript,
                    'ai_response' => $aiResponse['text']
                ]);

                return [
                    'type' => 'audio',
                    'audio_base64' => $audioBase64,
                    'transcript' => $aiResponse['text'],
                    'user_transcript' => $transcript,
                    'ai_model' => $aiResponse['model'] ?? 'gpt-4o-mini',
                    'transcription_service' => 'elevenlabs',
                    'tts_service' => 'elevenlabs',
                    'timestamp' => time()
                ];

            } catch (\Exception $e) {
                Log::error('Failed to process audio through ElevenLabs + OpenAI pipeline', [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return null;
            }
            // If we get an AI response, store it too
            if ($aiResponse && isset($aiResponse['audio_base64'])) {
                $this->storeAudioChunk($sessionId, $aiResponse['audio_base64'], 'outgoing', [
                    'type' => $aiResponse['type'] ?? 'ai_response',
                    'transcript' => $aiResponse['transcript'] ?? null,
                    'timestamp' => time()
                ]);
            }

            Log::info('Audio file processed', [
                'session_id' => $sessionId,
                'input_file_size' => strlen($audioContent),
                'has_ai_response' => !empty($aiResponse),
                'user_id' => $metadata['user_id'] ?? null
            ]);

            return $aiResponse;

        // } catch (\Exception $e) {
        //     Log::error('Failed to process audio file', [
        //         'session_id' => $sessionId,
        //         'file_path' => $filePath,
        //         'error' => $e->getMessage(),
        //         'trace' => $e->getTraceAsString()
        //     ]);
        //     return null;
        // }
    }

    /**
     * Store audio chunk for multi-user access
     */
    private function storeAudioChunk(string $sessionId, string $audioData, string $direction, array $metadata = []): void
    {
        $chunkId = uniqid('chunk_');
        $timestamp = time();

        $chunkData = [
            'chunk_id' => $chunkId,
            'session_id' => $sessionId,
            'audio_data' => $audioData,
            'direction' => $direction, // 'incoming' or 'outgoing'
            'metadata' => $metadata,
            'timestamp' => $timestamp,
            'created_at' => now()->toISOString()
        ];

        // Store individual chunk
        Redis::setex("audio_chunk:{$sessionId}:{$chunkId}", 7200, json_encode($chunkData)); // 2 hours

        // Add to session's audio log
        $logKey = "audio_log:{$sessionId}";
        Redis::lpush($logKey, json_encode([
            'chunk_id' => $chunkId,
            'direction' => $direction,
            'timestamp' => $timestamp,
            'size' => strlen($audioData),
            'user_id' => $metadata['user_id'] ?? null
        ]));
        Redis::expire($logKey, 7200); // 2 hours
    }

    /**
     * Get audio chunks for a session (for multi-user access)
     */
    public function getSessionAudioChunks(string $sessionId, int $limit = 100): array
    {
        $logKey = "audio_log:{$sessionId}";
        $logEntries = Redis::lrange($logKey, 0, $limit - 1);

        $chunks = [];
        foreach ($logEntries as $entry) {
            $logData = json_decode($entry, true);
            if ($logData) {
                $chunkData = Redis::get("audio_chunk:{$sessionId}:{$logData['chunk_id']}");
                if ($chunkData) {
                    $chunks[] = json_decode($chunkData, true);
                }
            }
        }

        return array_reverse($chunks); // Return in chronological order
    }

    /**
     * Send audio to ElevenLabs WebSocket
     */
    private function sendAudioToElevenLabsWebSocket(string $sessionId, string $audioData): ?array
    {
        try {
            // Step 1: Get session data and agent for language support
            $sessionData = $this->getSessionData($sessionId);
            if (!$sessionData) {
                Log::error('Session data not found', ['session_id' => $sessionId]);
                return null;
            }

            $agent = Agent::find($sessionData['agent_id']);
            if (!$agent) {
                Log::error('Agent not found', ['session_id' => $sessionId, 'agent_id' => $sessionData['agent_id']]);
                return null;
            }
            
            // Step 2: Transcribe audio using ElevenLabs Speech-to-Text with language support
            $elevenLabsService = app(ElevenLabsService::class);
            $transcript = $elevenLabsService->transcribeAudio($audioData, 'audio/wav', $agent->language);
            
            if (!$transcript) {
                Log::warning('Failed to transcribe audio with ElevenLabs', ['session_id' => $sessionId]);
                return null;
            }

            Log::info('Audio transcribed successfully with ElevenLabs', [
                'session_id' => $sessionId,
                'transcript' => $transcript,
                'language' => $agent->language
            ]);

            // Step 3: Get conversation history from session or cache
            $conversationHistory = $this->getConversationHistory($sessionId);
            
            // Step 4: Generate AI response using OpenAI ChatGPT with language support
            $openaiService = app(OpenAIService::class);
            $aiResponse = $openaiService->generateResponse($transcript, $agent, $conversationHistory, null, $agent->language);
            
            if (!$aiResponse) {
                Log::warning('Failed to generate AI response', ['session_id' => $sessionId]);
                return null;
            }

            Log::info('AI response generated successfully', [
                'session_id' => $sessionId,
                'response' => $aiResponse['text']
            ]);

            // Step 5: Convert AI response to speech using ElevenLabs TTS with language support
            $audioBase64 = $elevenLabsService->textToSpeech($aiResponse['text'], $agent->voice_id, [], $agent->language);
            
            if (!$audioBase64) {
                Log::warning('Failed to convert text to speech with ElevenLabs', ['session_id' => $sessionId]);
                return null;
            }

            Log::info('Text-to-speech conversion successful', [
                'session_id' => $sessionId,
                'audio_length' => strlen($audioBase64)
            ]);

            // Step 4: Update conversation history
            $this->updateConversationHistory($sessionId, [
                ['role' => 'user', 'content' => $transcript],
                ['role' => 'assistant', 'content' => $aiResponse['text']]
            ]);

            return [
                'type' => 'audio',
                'audio_base64' => $audioBase64,
                'transcript' => $aiResponse['text'],
                'user_transcript' => $transcript,
                'ai_model' => $aiResponse['model'] ?? 'gpt-4o-mini',
                'transcription_service' => 'elevenlabs',
                'tts_service' => 'elevenlabs',
                'timestamp' => time()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to process audio through ElevenLabs + OpenAI pipeline', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get conversation history for a session
     */
    private function getConversationHistory(string $sessionId): array
    {
        $historyKey = "conversation_history:{$sessionId}";
        $history = Redis::get($historyKey);
        return $history ? json_decode($history, true) : [];
    }

    /**
     * Update conversation history for a session
     */
    private function updateConversationHistory(string $sessionId, array $newMessages): void
    {
        $historyKey = "conversation_history:{$sessionId}";
        $existingHistory = $this->getConversationHistory($sessionId);
        
        // Add new messages to history
        $updatedHistory = array_merge($existingHistory, $newMessages);
        
        // Keep only last 20 messages to avoid memory issues
        if (count($updatedHistory) > 20) {
            $updatedHistory = array_slice($updatedHistory, -20);
        }
        
        Redis::setex($historyKey, 3600, json_encode($updatedHistory)); // 1 hour expiry
    }

    /**
     * Clear conversation history for a session
     */
    private function clearConversationHistory(string $sessionId): void
    {
        $historyKey = "conversation_history:{$sessionId}";
        Redis::del($historyKey);
    }

    /**
     * End a voice call session
     */
    public function endSession(string $sessionId): bool
    {
        $sessionData = $this->getSessionData($sessionId);
        if (!$sessionData) {
            return false;
        }

        try {
            // Close ElevenLabs connection if exists
            if (isset($this->activeConnections[$sessionId])) {
                // In production, properly close the WebSocket connection
                unset($this->activeConnections[$sessionId]);
            }

            // Clear conversation history
            $this->clearConversationHistory($sessionId);

            // Update session status
            $this->updateSessionStatus($sessionId, 'ended');

            // Broadcast session end
            broadcast(new \App\Events\VoiceCallStatusUpdated(
                $sessionData['channel_name'],
                'ended',
                'Voice call session ended'
            ));

            // Clean up Redis data after 1 hour
            Redis::expire("voice_call:{$sessionId}", 3600);

            Log::info('Voice call session ended', ['session_id' => $sessionId]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to end voice call session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get session data from Redis
     */
    public function getSessionData(string $sessionId): ?array
    {
        $data = Redis::get("voice_call:{$sessionId}");
        return $data ? json_decode($data, true) : null;
    }

    /**
     * Update session data in Redis
     */
    public function updateSessionData(string $sessionId, array $data): void
    {
        $data['updated_at'] = now()->toISOString();
        Redis::setex("voice_call:{$sessionId}", 3600, json_encode($data));
    }

    /**
     * Update session status
     */
    public function updateSessionStatus(string $sessionId, string $status, ?string $message = null): void
    {
        $sessionData = $this->getSessionData($sessionId);
        if ($sessionData) {
            $sessionData['status'] = $status;
            if ($message) {
                $sessionData['status_message'] = $message;
            }
            $this->updateSessionData($sessionId, $sessionData);
        }
    }

    /**
     * Get all active sessions (for monitoring)
     */
    public function getActiveSessions(): array
    {
        $keys = Redis::keys('voice_call:*');
        $sessions = [];

        foreach ($keys as $key) {
            $data = Redis::get($key);
            if ($data) {
                $sessions[] = json_decode($data, true);
            }
        }

        return $sessions;
    }

    /**
     * Handle audio data received via WebSocket
     */
    public function handleWebSocketAudioData(string $sessionId, string $audioData): bool
    {
        $sessionData = $this->getSessionData($sessionId);
        if (!$sessionData) {
            Log::warning('Session not found for WebSocket audio data', ['session_id' => $sessionId]);
            return false;
        }

        try {
            // Send audio to ElevenLabs via WebSocket
            return $this->sendAudioToElevenLabs($sessionId, $audioData);
        } catch (\Exception $e) {
            Log::error('Failed to handle WebSocket audio data', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Handle call end request via WebSocket
     */
    public function handleWebSocketCallEnd(string $sessionId, string $reason = 'user_ended'): bool
    {
        $sessionData = $this->getSessionData($sessionId);
        if (!$sessionData) {
            Log::warning('Session not found for WebSocket call end', ['session_id' => $sessionId]);
            return false;
        }

        try {
            // End the call
            $this->endSession($sessionId);
            
            // Broadcast call ended event
            broadcast(new \App\Events\VoiceCallStatusUpdated(
                $sessionData['channel_name'], 
                'ended',
                "Call ended: {$reason}"
            ));

            Log::info('Call ended via WebSocket', [
                'session_id' => $sessionId,
                'reason' => $reason
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to handle WebSocket call end', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Execute pending broadcast for a session (to be called after HTTP response)
     */
    public function executePendingBroadcast(string $sessionId): void
    {
        $sessionData = $this->getSessionData($sessionId);
        if (!$sessionData || !isset($sessionData['pending_broadcast'])) {
            return;
        }

        $broadcastData = $sessionData['pending_broadcast'];
        
        Log::info('Executing pending broadcast', [
            'session_id' => $sessionId,
            'channel_name' => $sessionData['channel_name'],
            'broadcast_data' => $broadcastData
        ]);

        $event = new \App\Events\VoiceCallStatusUpdated(
            $sessionData['channel_name'], 
            $broadcastData['type'],
            $broadcastData['message']
        );
        
        try {
            $result = broadcast($event);
            
            Log::info('Pending broadcast executed successfully', [
                'session_id' => $sessionId,
                'channel_name' => $sessionData['channel_name'],
                'result' => $result
            ]);
            
            // Clear the pending broadcast
            unset($sessionData['pending_broadcast']);
            $this->updateSessionData($sessionId, $sessionData);
            
        } catch (\Exception $broadcastException) {
            Log::error('Pending broadcast failed', [
                'session_id' => $sessionId,
                'channel_name' => $sessionData['channel_name'],
                'error' => $broadcastException->getMessage()
            ]);
        }
    }

    /**
     * Handle audio data received from WebSocket client events
     */
    public function handleAudioData(string $sessionId, string $audioData): void
    {
        $sessionData = $this->getSessionData($sessionId);
        if (!$sessionData) {
            Log::error('Session not found for audio data', ['session_id' => $sessionId]);
            return;
        }

        Log::info('Handling WebSocket audio data', [
            'session_id' => $sessionId,
            'audio_data_size' => strlen($audioData)
        ]);

        // If ElevenLabs WebSocket is connected, send audio data
        if (isset($sessionData['elevenlabs_websocket_url'])) {
            try {
                $this->sendAudioToElevenLabs($sessionId, $audioData);
            } catch (\Exception $e) {
                Log::error('Failed to send audio to ElevenLabs', [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            Log::warning('ElevenLabs WebSocket not ready for audio data', [
                'session_id' => $sessionId
            ]);
        }
    }

    /**
     * Handle call end request from WebSocket client events
     */
    public function endCall(string $sessionId, string $reason = 'client_ended'): void
    {
        $sessionData = $this->getSessionData($sessionId);
        if (!$sessionData) {
            Log::error('Session not found for end call', ['session_id' => $sessionId]);
            return;
        }

        Log::info('Ending call via WebSocket client event', [
            'session_id' => $sessionId,
            'reason' => $reason
        ]);

        try {
            // Update session status
            $sessionData['status'] = 'ended';
            $sessionData['ended_at'] = now()->toISOString();
            $sessionData['end_reason'] = $reason;
            $this->updateSessionData($sessionId, $sessionData);

            // Close ElevenLabs WebSocket connection if active
            if (isset($this->activeConnections[$sessionId])) {
                $this->activeConnections[$sessionId]->close();
                unset($this->activeConnections[$sessionId]);
            }

            // Broadcast call ended status
            $event = new \App\Events\VoiceCallStatusUpdated(
                $sessionData['channel_name'],
                'ended',
                "Call ended: {$reason}"
            );
            broadcast($event);

            Log::info('Call ended successfully', [
                'session_id' => $sessionId,
                'reason' => $reason
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to end call', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generate cache key for audio based on voice_id and text content
     */
    private function generateAudioCacheKey(string $voiceId, string $text): string
    {
        $textHash = md5(trim($text));
        return "voice_{$voiceId}_audio_generated_{$textHash}";
    }

    /**
     * Check if audio already exists in Redis cache
     */
    private function getCachedAudio(string $voiceId, string $text): ?string
    {
        $cacheKey = $this->generateAudioCacheKey($voiceId, $text);
        $cachedAudio = Redis::get($cacheKey);
        
        if ($cachedAudio) {
            Log::info('Found cached audio', [
                'voice_id' => $voiceId,
                'cache_key' => $cacheKey,
                'text_preview' => substr($text, 0, 100) . '...',
                'cached_audio_length' => strlen($cachedAudio)
            ]);
            return $cachedAudio;
        }
        
        return null;
    }

    /**
     * Store audio in Redis cache with voice_id_audio_generated_key format
     */
    private function cacheAudio(string $voiceId, string $text, string $audioBase64): void
    {
        $cacheKey = $this->generateAudioCacheKey($voiceId, $text);
        
        // Store audio in Redis with 24 hour expiry
        Redis::setex($cacheKey, 86400, $audioBase64);
        
        Log::info('Cached audio for future use', [
            'voice_id' => $voiceId,
            'cache_key' => $cacheKey,
            'text_preview' => substr($text, 0, 100) . '...',
            'audio_length' => strlen($audioBase64),
            'expiry_hours' => 24
        ]);
    }

    /**
     * Generate cache key for greeting audio based on voice_id and processed greeting message
     */
    private function generateGreetingAudioCacheKey(string $voiceId, string $greetingText): string
    {
        $textHash = md5(trim($greetingText));
        return "voice_{$voiceId}_greeting_audio_{$textHash}";
    }

    /**
     * Check if greeting audio exists in cache
     */
    public function hasGreetingAudio(Agent $agent, array $variables = []): bool
    {
        $processedGreeting = $agent->getProcessedGreetingMessage($variables);
        
        if (empty($processedGreeting)) {
            return false;
        }

        $voiceId = $agent->voice_id ?: 'default';
        $cacheKey = $this->generateGreetingAudioCacheKey($voiceId, $processedGreeting);
        
        return Redis::exists($cacheKey);
    }

    /**
     * Get greeting audio from cache
     */
    public function getGreetingAudio(Agent $agent, array $variables = []): ?string
    {
        $processedGreeting = $agent->getProcessedGreetingMessage($variables);
        
        if (empty($processedGreeting)) {
            return null;
        }

        $voiceId = $agent->voice_id ?: 'default';
        $cacheKey = $this->generateGreetingAudioCacheKey($voiceId, $processedGreeting);
        
        $cachedAudio = Redis::get($cacheKey);
        
        if ($cachedAudio) {
            Log::info('Found cached greeting audio', [
                'voice_id' => $voiceId,
                'cache_key' => $cacheKey,
                'greeting_preview' => substr($processedGreeting, 0, 100) . '...',
                'cached_audio_length' => strlen($cachedAudio)
            ]);
            return $cachedAudio;
        }
        
        return null;
    }

    /**
     * Generate and cache greeting audio
     */
    public function generateGreetingAudio(Agent $agent, array $variables = []): ?array
    {
        $processedGreeting = $agent->getProcessedGreetingMessage($variables);
        
        if (empty($processedGreeting)) {
            Log::warning('No greeting message to generate audio for', [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name
            ]);
            return null;
        }

        $voiceId = $agent->voice_id ?: 'default';
        
        // Check if already cached
        $existingAudio = $this->getGreetingAudio($agent, $variables);
        if ($existingAudio) {
            return [
                'audio_base64' => $existingAudio,
                'greeting_text' => $processedGreeting,
                'voice_id' => $voiceId,
                'cached' => true
            ];
        }

        try {
            Log::info('Generating greeting audio', [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'voice_id' => $voiceId,
                'greeting_text' => $processedGreeting,
                'variables' => $variables
            ]);

            // Generate audio using ElevenLabs TTS with language support
            $elevenLabsService = app(ElevenLabsService::class);
            $audioBase64 = $elevenLabsService->textToSpeech($processedGreeting, $voiceId, [], $agent->language);
            
            if (!$audioBase64) {
                Log::error('Failed to generate greeting audio with ElevenLabs', [
                    'agent_id' => $agent->id,
                    'voice_id' => $voiceId,
                    'greeting_text' => $processedGreeting
                ]);
                return null;
            }
            
            // Cache the greeting audio with longer expiry (24 hours)
            $cacheKey = $this->generateGreetingAudioCacheKey($voiceId, $processedGreeting);
            Redis::setex($cacheKey, 86400, $audioBase64); // 24 hours
            
            Log::info('Generated and cached greeting audio successfully', [
                'agent_id' => $agent->id,
                'voice_id' => $voiceId,
                'audio_length' => strlen($audioBase64),
                'cache_key' => $cacheKey,
                'greeting_preview' => substr($processedGreeting, 0, 100) . '...'
            ]);
            
            return [
                'audio_base64' => $audioBase64,
                'greeting_text' => $processedGreeting,
                'voice_id' => $voiceId,
                'cached' => false
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate greeting audio', [
                'agent_id' => $agent->id,
                'voice_id' => $voiceId,
                'greeting_text' => $processedGreeting,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Prepare greeting audio for session - check cache and generate if needed
     */
    public function prepareGreetingAudio(Agent $agent, array $variables = []): ?array
    {
        $processedGreeting = $agent->getProcessedGreetingMessage($variables);
        
        if (empty($processedGreeting)) {
            Log::info('No greeting message configured for agent', [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name
            ]);
            return null;
        }

        // Check if greeting audio exists in cache
        if ($this->hasGreetingAudio($agent, $variables)) {
            $cachedAudio = $this->getGreetingAudio($agent, $variables);
            if ($cachedAudio) {
                Log::info('Using existing cached greeting audio', [
                    'agent_id' => $agent->id,
                    'voice_id' => $agent->voice_id,
                    'greeting_preview' => substr($processedGreeting, 0, 100) . '...'
                ]);
                
                return [
                    'audio_base64' => $cachedAudio,
                    'greeting_text' => $processedGreeting,
                    'voice_id' => $agent->voice_id ?: 'default',
                    'cached' => true,
                    'ready' => true
                ];
            }
        }

        // Generate new greeting audio
        Log::info('Generating new greeting audio for agent', [
            'agent_id' => $agent->id,
            'voice_id' => $agent->voice_id,
            'greeting_preview' => substr($processedGreeting, 0, 100) . '...'
        ]);

        $greetingAudio = $this->generateGreetingAudio($agent, $variables);
        
        if ($greetingAudio) {
            $greetingAudio['ready'] = true;
            return $greetingAudio;
        }

        return null;
    }

    /**
     * Handle audio playback completion notification
     */
    public function handleAudioPlaybackCompleted(string $sessionId, string $audioId): void
    {
        Log::info('Audio playback completed notification received', [
            'session_id' => $sessionId,
            'audio_id' => $audioId
        ]);

        $this->markAudioAsCompleted($sessionId, $audioId);

        // Check if call should end after this audio
        $sessionData = $this->getSessionData($sessionId);
        if ($sessionData && ($sessionData['call_state']['should_end'] ?? false)) {
            $this->endCallSession($sessionId);
        }
    }

    /**
     * Handle user audio interruption (when user speaks during AI response)
     */
    public function handleUserInterruption(string $sessionId, float $interruptionDuration = 4.0): bool
    {
        Log::info('User interruption detected', [
            'session_id' => $sessionId,
            'interruption_duration' => $interruptionDuration
        ]);

        $sessionData = $this->getSessionData($sessionId);
        if (!$sessionData) {
            return false;
        }

        $interruptThreshold = $sessionData['audio_state']['interrupt_threshold'] ?? 4.0;

        // Only interrupt if user spoke for at least the threshold duration
        if ($interruptionDuration >= $interruptThreshold) {
            return $this->handleAudioInterruption($sessionId, uniqid('interrupt_'));
        }

        return false;
    }

    /**
     * End the call session and cleanup resources
     */
    public function endCallSession(string $sessionId): void
    {
        $sessionData = $this->getSessionData($sessionId);
        if ($sessionData) {
            $sessionData['call_state']['active'] = false;
            $sessionData['status'] = 'ended';
            $sessionData['ended_at'] = now()->toISOString();
            
            $this->updateSessionData($sessionId, $sessionData);
            
            // Broadcast call end event
            broadcast(new \App\Events\VoiceCallAudioEvent(
                $sessionId,
                '',
                'call_ended',
                [
                    'type' => 'call_ended',
                    'message' => 'Call session has ended',
                    'timestamp' => time(),
                    'session_duration' => isset($sessionData['created_at']) 
                        ? \Carbon\Carbon::parse($sessionData['created_at'])->diffInSeconds(now())
                        : 0
                ]
            ));

            Log::info('Call session ended', [
                'session_id' => $sessionId,
                'session_duration' => isset($sessionData['created_at']) 
                    ? \Carbon\Carbon::parse($sessionData['created_at'])->diffInSeconds(now())
                    : 0
            ]);

            // Clean up conversation history after a delay
            dispatch(function() use ($sessionId) {
                sleep(300); // Wait 5 minutes before cleanup
                $this->clearConversationHistory($sessionId);
                Redis::del("voice_call:{$sessionId}");
            })->delay(now()->addMinutes(5));
        }
    }

    /**
     * Mark audio as playing
     */
    private function markAudioAsPlaying(string $sessionId, string $audioId): void
    {
        $sessionData = $this->getSessionData($sessionId);
        if ($sessionData) {
            $sessionData['audio_state']['playing'] = true;
            $sessionData['audio_state']['current_audio_id'] = $audioId;
            $sessionData['audio_state']['interrupted'] = false;
            
            $this->updateSessionData($sessionId, $sessionData);
            
            Log::info('Audio marked as playing', [
                'session_id' => $sessionId,
                'audio_id' => $audioId
            ]);
        }
    }

    /**
     * Mark audio as completed
     */
    private function markAudioAsCompleted(string $sessionId, string $audioId): void
    {
        $sessionData = $this->getSessionData($sessionId);
        if ($sessionData) {
            $sessionData['audio_state']['playing'] = false;
            $sessionData['audio_state']['current_audio_id'] = null;
            $sessionData['audio_state']['interrupted'] = false;
            
            $this->updateSessionData($sessionId, $sessionData);
            
            Log::info('Audio marked as completed', [
                'session_id' => $sessionId,
                'audio_id' => $audioId
            ]);
        }
    }

    /**
     * Handle audio interruption when user speaks during AI response
     */
    private function handleAudioInterruption(string $sessionId, string $newRequestId): bool
    {
        $sessionData = $this->getSessionData($sessionId);
        if (!$sessionData) {
            return false;
        }
        
        // Check if audio is currently playing
        if ($sessionData['audio_state']['playing'] ?? false) {
            // Stop current audio playback
            $sessionData['audio_state']['playing'] = false;
            $sessionData['audio_state']['interrupted'] = true;
            $sessionData['audio_state']['current_audio_id'] = null;
            
            // Stop any processing request
            $sessionData['request_state']['processing'] = false;
            $sessionData['request_state']['current_request_id'] = null;
            
            $this->updateSessionData($sessionId, $sessionData);
            
            Log::info('Audio playback interrupted by user input', [
                'session_id' => $sessionId,
                'new_request_id' => $newRequestId,
                'interrupted_audio' => $sessionData['audio_state']['current_audio_id'] ?? 'unknown'
            ]);
            
            // Broadcast interruption event
            $this->broadcastInterruptionEvent($sessionId);
            
            return true;
        }
        
        return false;
    }

    /**
     * Broadcast audio interruption event
     */
    private function broadcastInterruptionEvent(string $sessionId): void
    {
        broadcast(new \App\Events\VoiceCallAudioEvent(
            $sessionId,
            '',
            'interruption',
            [
                'type' => 'audio_interrupted',
                'message' => 'AI response interrupted by user input',
                'timestamp' => time(),
                'action' => 'stop_audio'
            ]
        ));
    }
}
