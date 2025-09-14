<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Call;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;
use Agence104\LiveKit\RoomServiceClient;
use Agence104\LiveKit\RoomCreateOptions;

class LiveKitService
{
    private string $livekitUrl;
    private string $livekitApiUrl;
    private string $apiKey;
    private string $apiSecret;
    private RoomServiceClient $roomService;

    public function __construct()
    {
        $this->livekitUrl = config('services.livekit.url');
        $this->apiKey = config('services.livekit.api_key');
        $this->apiSecret = config('services.livekit.api_secret');
        
        // Convert WebSocket URL to HTTP URL for API calls
        $this->livekitApiUrl = $this->convertToHttpUrl($this->livekitUrl);
        
        // Initialize room service client with HTTP URL
        $this->roomService = new RoomServiceClient($this->livekitApiUrl, $this->apiKey, $this->apiSecret);
        
        Log::info('LiveKitService initialized', [
            'websocket_url' => $this->livekitUrl,
            'api_url' => $this->livekitApiUrl
        ]);
    }

    /**
     * Convert WebSocket URL to HTTP URL for API calls
     */
    private function convertToHttpUrl(string $wsUrl): string
    {
        if (str_starts_with($wsUrl, 'wss://')) {
            return str_replace('wss://', 'https://', $wsUrl);
        } elseif (str_starts_with($wsUrl, 'ws://')) {
            return str_replace('ws://', 'http://', $wsUrl);
        }
        
        // If it's already HTTP/HTTPS, return as is
        return $wsUrl;
    }

    /**
     * Generate access token for participants
     */
    public function generateAccessToken(string $roomName, string $participantName, array $permissions = []): string
    {
        $options = new AccessTokenOptions();
        $options->setIdentity($participantName);
        $options->setTtl(3600); // 1 hour
        
        $token = new AccessToken($this->apiKey, $this->apiSecret, $options);
        
        $videoGrant = new VideoGrant();
        $videoGrant->setRoomName($roomName);
        $videoGrant->setRoomJoin(true);
        $videoGrant->setCanPublish($permissions['canPublish'] ?? true);
        $videoGrant->setCanSubscribe($permissions['canSubscribe'] ?? true);
        $videoGrant->setCanPublishData($permissions['canPublishData'] ?? true);
        
        $token->setGrant($videoGrant);

        return $token->toJwt();
    }

    /**
     * Start a test call session (no database record)
     */
    public function startTestCall(Agent $agent, array $dynamicVariables = []): array
    {
        $roomName = 'test_' . $agent->id . '_' . time();

        // Process dynamic variables for the current agent
        $processedPrompt = $this->replaceDynamicVariables($agent->system_prompt, $dynamicVariables);
        $processedGreeting = $this->replaceDynamicVariables($agent->greeting_message, $dynamicVariables);
        // Prepare agent configuration that will be sent to the Python agent
        $agentConfig = [
            'agent_id' => $agent->id,
            'prompt' => $processedPrompt, // Send processed prompt with variables replaced
            'greeting_message' => $processedGreeting, // Send processed greeting with variables replaced
            'language' => $agent->language,
            'agent_name' => $agent->name,
            'company_name' => $agent->company->name ?? 'Our Company',
            'voice_settings' => [
                'voice_id' => $agent->voice_id,
                'stability' => $agent->stability,
                'similarity_boost' => $agent->similarity_boost,
                'style' => $agent->style,
                'use_speaker_boost' => $agent->use_speaker_boost,
            ],
            'model_settings' => [
                'model' => $agent->model,
                'temperature' => $agent->temperature,
                'max_tokens' => $agent->max_tokens,
            ],
        ];

        // Create the room with metadata
        try {
            $roomMetadata = json_encode($agentConfig);
            
            $roomOptions = (new RoomCreateOptions())
                ->setName($roomName)
                ->setEmptyTimeout(30) // 30 seconds timeout for empty room
                ->setMaxParticipants(10)
                ->setMetadata($roomMetadata); // Set the room metadata here
            
            $room = $this->roomService->createRoom($roomOptions);
            
            Log::info('LiveKit room created with metadata', [
                'room_name' => $roomName,
                'agent_id' => $agent->id,
                'metadata_length' => ($roomMetadata),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to create LiveKit room', [
                'room_name' => $roomName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        $agentToken = $this->generateAccessToken(
            $roomName, 
            'agent',
            ['canPublish' => true, 'canSubscribe' => true, 'canPublishData' => true]
        );

        $callerToken = $this->generateAccessToken(
            $roomName, 
            'caller',
            ['canPublish' => true, 'canSubscribe' => true, 'canPublishData' => false]
        );

        Log::info('LiveKit test call session started', [
            'room_name' => $roomName,
            'agent_id' => $agent->id,
            'language' => $agent->language
        ]);

        return [
            'room_name' => $roomName,
            'url' => $this->livekitUrl,
            'tokens' => [
                'agent' => $agentToken,
                'caller' => $callerToken,
            ],
            'agent_config' => $agentConfig,
            'room_metadata' => $roomMetadata
        ];
    }

    /**
     * Create connection info for frontend
     */
    public function getConnectionInfo(string $roomName, string $participantName): array
    {
        $token = $this->generateAccessToken($roomName, $participantName);
        
        return [
            'url' => $this->livekitUrl,
            'token' => $token,
            'room_name' => $roomName,
        ];
    }

    /**
     * End a test call session (cleanup if needed)
     */
    public function endTestCall(string $roomName): bool
    {
        Log::info('LiveKit test call session ended', [
            'room_name' => $roomName
        ]);

        return true;
    }

    /**
     * Replace dynamic variables in text with actual values
     */
    private function replaceDynamicVariables(?string $text, array $dynamicVariables): string
    {
        if (!$text) {
            return '';
        }

        $payload = $dynamicVariables ?? [];


        $result = preg_replace_callback('/{{(.*?)}}/', function ($matches) use ($payload) {
            $key = trim($matches[1]);
            if (isset($payload[$key])) {
                return $payload[$key];
            } else {
                return $matches[0];  
            }
        }, $text);

      
        return $result;
    }

}
