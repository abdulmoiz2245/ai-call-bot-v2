<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
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
        
        // Store session data in Redis
        $sessionData = [
            'session_id' => $sessionId,
            'agent_id' => $agent->id,
            'elevenlabs_agent_id' => $agent->elevenlabs_agent_id,
            'channel_name' => $channelName,
            'variables' => $variables,
            'status' => 'initializing',
            'created_at' => now()->toISOString(),
            'elevenlabs_websocket_url' => null,
            'conversation_metadata' => null
        ];

        Redis::setex("voice_call:{$sessionId}", 3600, json_encode($sessionData));

        Log::info('Voice call session created', [
            'session_id' => $sessionId,
            'agent_id' => $agent->id,
            'channel_name' => $channelName
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
        // For now, we'll simulate the connection since React/Ratchet WebSocket client
        // requires more complex setup. In production, you'd use ReactPHP or similar.
        
        Log::info('Creating ElevenLabs WebSocket connection', [
            'session_id' => $sessionId,
            'websocket_url' => $websocketUrl
        ]);

        // Store the connection reference
        $this->activeConnections[$sessionId] = [
            'websocket_url' => $websocketUrl,
            'status' => 'connected',
            'created_at' => now()
        ];

        // Update session status
        $this->updateSessionStatus($sessionId, 'elevenlabs_connected');

        // Broadcast connection success to the channel
        $sessionData = $this->getSessionData($sessionId);
        if ($sessionData) {
            broadcast(new \App\Events\VoiceCallStatusUpdated(
                $sessionData['channel_name'], 
                'elevenlabs_connected',
                'Connected to voice AI service'
            ));
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
            // In a real implementation, you would send the binary audio data
            // directly to the ElevenLabs WebSocket connection
            Log::info('Sending audio to ElevenLabs', [
                'session_id' => $sessionId,
                'audio_size' => is_string($audioData) ? strlen($audioData) : 'binary'
            ]);

            // Simulate processing and response from ElevenLabs
            $this->simulateElevenLabsResponse($sessionId);

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
     * Simulate ElevenLabs response (for development)
     * In production, this would be replaced with actual WebSocket message handling
     */
    private function simulateElevenLabsResponse(string $sessionId): void
    {
        $sessionData = $this->getSessionData($sessionId);
        if (!$sessionData) {
            return;
        }

        // Simulate different types of responses from ElevenLabs
        $responses = [
            [
                'type' => 'conversation_initiation_metadata',
                'conversation_initiation_metadata_event' => [
                    'conversation_id' => uniqid('conv_'),
                    'agent_output_audio_format' => 'pcm_16000'
                ]
            ],
            [
                'type' => 'user_transcript',
                'user_transcript' => 'Hello, this is a test message'
            ],
            [
                'type' => 'agent_response',
                'agent_response' => 'Hello! How can I help you today?'
            ],
            [
                'type' => 'audio',
                'audio_base64' => base64_encode('simulated_audio_data_' . time())
            ]
        ];

        $response = $responses[array_rand($responses)];

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
            $this->endCall($sessionId);
            
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
}
