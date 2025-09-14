<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Agent;

class ElevenLabsService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $config = config('services.elevenlabs');
        $this->apiKey = $config['api_key'];
        $this->baseUrl = $config['base_url'];
    }

    /**
     * Get available voices from ElevenLabs
     */
    public function getVoices(): array
    {
        try {
            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/shared-voices?search=conversation");

            if ($response->successful()) {
                return $response->json()['voices'] ?? [];
            }

            Log::error('ElevenLabs API Error: ' . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error('ElevenLabs Service Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate speech from text
     */
    public function textToSpeech(string $text, string $voiceId, array $voiceSettings = [], ?string $language = null): ?string
    {
        try {
            $defaultSettings = [
                'stability' => 0.5,
                'similarity_boost' => 0.75,
                'style' => 0.5,
                'use_speaker_boost' => true,
            ];

            $settings = array_merge($defaultSettings, $voiceSettings);

            // Select appropriate model based on language
            $modelId = $this->getModelIdForLanguage($language);

            Log::info('ElevenLabs TTS Request', [
                'voice_id' => $voiceId,
                'language' => $language,
                'model_id' => $modelId,
                'text_length' => strlen($text),
                'voice_settings' => $settings
            ]);

            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'audio/mpeg',
                'x-region' => 'europe-west4'
            ])->post("https://api-global-preview.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
                'text' => $text,
                'model_id' => $modelId,
                'voice_settings' => $settings,
            ]);

            if ($response->successful()) {
                // Return the audio content as base64 encoded string
                return base64_encode($response->body());
            }

            Log::error('ElevenLabs TTS Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('ElevenLabs TTS Service Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get voice settings for a specific voice
     */
    public function getVoiceSettings(string $voiceId): array
    {
        try {
            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/voices/{$voiceId}/settings");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('ElevenLabs Voice Settings Error: ' . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error('ElevenLabs Voice Settings Service Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Clone a voice from audio samples
     */
    public function cloneVoice(string $name, array $audioFiles, ?string $description = null): ?array
    {
        try {
            $multipart = [
                [
                    'name' => 'name',
                    'contents' => $name,
                ],
            ];

            if ($description) {
                $multipart[] = [
                    'name' => 'description',
                    'contents' => $description,
                ];
            }

            foreach ($audioFiles as $index => $audioFile) {
                $multipart[] = [
                    'name' => "files[{$index}]",
                    'contents' => fopen($audioFile, 'r'),
                    'filename' => basename($audioFile),
                ];
            }

            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey,
            ])->asMultipart()->post("{$this->baseUrl}/voices/add", $multipart);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('ElevenLabs Voice Clone Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('ElevenLabs Voice Clone Service Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete a cloned voice
     */
    public function deleteVoice(string $voiceId): bool
    {
        try {
            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey,
            ])->delete("{$this->baseUrl}/voices/{$voiceId}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('ElevenLabs Delete Voice Service Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user subscription info
     */
    public function getUserInfo(): ?array
    {
        try {
            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/user");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('ElevenLabs User Info Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('ElevenLabs User Info Service Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all ElevenLabs agents
     */
    public function getAgents(): array
    {
        try {
            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/convai/agents');


            if ($response->successful()) {
                return $response->json('agents', []);
            }

            Log::error('ElevenLabs API Error - Get Agents', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('ElevenLabs API Exception - Get Agents', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Update ElevenLabs agent
     */
    public function updateAgent(string $agentId, array $settings): bool
    {
        try {
            $payload = $this->prepareUpdatePayload($settings);
            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->patch($this->baseUrl . "/convai/agents/{$agentId}", $payload);
            if ($response->successful()) {
                Log::info('ElevenLabs Agent Updated Successfully', [
                    'agent_id' => $agentId,
                    'payload' => $payload
                ]);
                return true;
            }

            Log::error('ElevenLabs API Error - Update Agent', [
                'agent_id' => $agentId,
                'status' => $response->status(),
                'payload' => $payload,
                'response' => $response->json()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('ElevenLabs API Exception - Update Agent', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
                'payload' => $payload ?? null
            ]);

            return false;
        }
    }

    /**
     * Sync agent settings to ElevenLabs
     */
    public function syncAgent(Agent $agent): bool
    {
        if (!$agent->is_elevenlabs_connected || !$agent->elevenlabs_agent_id) {
            return false;
        }

        $settings = $this->mapAgentToElevenLabsSettings($agent);
        $success = $this->updateAgent($agent->elevenlabs_agent_id, $settings);

        if ($success) {
            $agent->update([
                'elevenlabs_settings' => $settings,
                'elevenlabs_last_synced' => now()
            ]);
        }

        return $success;
    }

    /**
     * Map our agent model to ElevenLabs settings
     */
    private function mapAgentToElevenLabsSettings(Agent $agent): array
    {
        $voiceSettings = $agent->voice_settings ?? [];
        
        // Get processed prompt and greeting with default variables for sync
        $processedPrompt = $agent->getProcessedSystemPrompt([]);
        $processedGreeting = $agent->getProcessedGreetingMessage([]);
        
        return [
            'name' => $agent->name,
            'conversation_config' => [
                'tts' => [
                    'voice_id' => $agent->voice_id,
                    'stability' => max(0.1, (float)($voiceSettings['stability'] ?? 0.5)),
                    'similarity_boost' => (float)($voiceSettings['similarity_boost'] ?? 0.8),
                    'speed' => max(0.7, (float)($voiceSettings['speed'] ?? 1.0))
                ],
                'agent' => [
                    'first_message' => $processedGreeting,
                    'prompt' => [
                        'prompt' => $processedPrompt,
                        // 'built_in_tools' => $this->mapAgentTools($agent),
                    ],
                ],
                'language' => $agent->language ?? 'en', // Add language support for ElevenLabs
            ],
        ];
    }

    /**
     * Map agent tools/capabilities to ElevenLabs format
     */
    // private function mapAgentTools(Agent $agent): array
    // {
    //     $tools = [];
    //     $transferConditions = $agent->transfer_conditions ?? [];
    //     $conversationFlow = $agent->conversation_flow ?? [];

    //     if ($conversationFlow['allow_end_call'] ?? true) {
    //         // $tools['end_call'] = new \stdClass();
    //         $tools["end_call"] = [
    //             "name" => "End Call",
    //             "description" => "End the current call",
    //             "params" => [
    //                 "system_tool_type" => "end_call"
    //             ],
    //             "type" => "system",
    //             "response_timeout_secs" => 20,
    //             "disable_interruptions" => false,
    //             "force_pre_tool_speech" => false,
    //             "assignments" => [
    //                 [
    //                     "dynamic_variable" => "string",
    //                     "value_path" => "string",
    //                     "source" => "response"
    //                 ]
    //             ]
    //         ];
    //     }

    //     if ($conversationFlow['detect_language'] ?? false) {
    //         $tools['language_detection'] = new \stdClass();
    //         $tools['language_detection'] = [
    //             "name" => "Detect Language",
    //             "description" => "Detect the language of the user's input",
    //             "params" => [
    //                 "system_tool_type" => "language_detection"
    //             ],
    //             "type" => "system",
    //             "response_timeout_secs" => 20,
    //             "disable_interruptions" => false,
    //             "force_pre_tool_speech" => false,
    //             "assignments" => [
    //                 [
    //                     "dynamic_variable" => "string",
    //                     "value_path" => "string",
    //                     "source" => "response"
    //                 ]
    //             ]
    //         ];
    //     }

           

    //     if ($conversationFlow['voicemail_detection'] ?? true) {
    //         $tools['voicemail_detection'] = new \stdClass();
    //         $tools['voicemail_detection'] = [
    //             "name" => "Voicemail Detection",
    //             "description" => "Detect if the user is leaving a voicemail",
    //             "params" => [
    //                 "system_tool_type" => "voicemail_detection"
    //             ],
    //             "type" => "system",
    //             "response_timeout_secs" => 20,
    //             "disable_interruptions" => false,
    //             "force_pre_tool_speech" => false,
    //             "assignments" => [
    //                 [
    //                     "dynamic_variable" => "string",
    //                     "value_path" => "string",
    //                     "source" => "response"
    //                 ]
    //             ]
    //         ];
    //     }


    //     // if (!empty($transferConditions['transfer_to_agent']) || !empty($transferConditions['transfer_to_number'])) {
    //     //     $tools['transfer_call'] = [
    //     //         'phone_number' => $transferConditions['agent_phone']
    //     //             ?? $transferConditions['transfer_number']
    //     //             ?? null
    //     //     ];
    //     // }

    //     return $tools;
    // }

    private function mapAgentTools(Agent $agent): array
    {
        $tools = [];
        $transferConditions = $agent->transfer_conditions ?? [];
        $conversationFlow = $agent->conversation_flow ?? [];

        if ($conversationFlow['allow_end_call'] ?? true) {
            $tools['end_call'] = new \stdClass();
        }

        if ($conversationFlow['detect_language'] ?? false) {
            $tools['language_detection'] = new \stdClass();
        }

        if ($conversationFlow['voicemail_detection'] ?? true) {
            $tools['voicemail_detection'] = new \stdClass();
        }

        if (!empty($transferConditions['transfer_to_agent']) || !empty($transferConditions['transfer_to_number'])) {
            $tools['transfer_call'] = [
                'phone_number' => $transferConditions['agent_phone']
                    ?? $transferConditions['transfer_number']
                    ?? null
            ];
        }

        return $tools;
    }

    /**
     * Prepare update payload for ElevenLabs API
     */
    private function prepareUpdatePayload(array $settings): array
    {
        return array_filter($settings, function($value) {
            return $value !== null && $value !== '';
        });
    }

    /**
     * Test API connection
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey,
            ])->get($this->baseUrl . '/user');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('ElevenLabs Connection Test Failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get ElevenLabs Conversational AI WebSocket URL for real-time conversation
     */
    public function getConversationalWebSocketUrl(Agent $agent, array $variables = []): ?array
    {
        try {
            // Check if agent has ElevenLabs agent ID
            if (!$agent->elevenlabs_agent_id) {
                throw new \Exception('Agent is not connected to ElevenLabs');
            }

            // For ElevenLabs Conversational AI, we connect directly via WebSocket
            // The WebSocket URL format includes language parameter for better conversation handling
            $websocketUrl = 'wss://api.elevenlabs.io/v1/convai/conversation?agent_id=' . $agent->elevenlabs_agent_id;
            
            // Add language parameter if available
            if ($agent->language) {
                $langCode = strtolower(substr($agent->language, 0, 2));
                $websocketUrl .= '&language=' . $langCode;
                
                Log::info('Adding language parameter to ElevenLabs WebSocket URL', [
                    'agent_id' => $agent->id,
                    'language' => $agent->language,
                    'language_code' => $langCode
                ]);
            }

            // Generate a unique session ID for this conversation
            $sessionId = uniqid('conv_' . $agent->elevenlabs_agent_id . '_');

            // Create session in VoiceCallService to store variables and processed prompts
            $voiceCallService = app(VoiceCallService::class);
            $sessionData = $voiceCallService->createSession($agent, 'elevenlabs-websocket', $variables);

            Log::info('Created ElevenLabs WebSocket session with processed prompts', [
                'session_id' => $sessionId,
                'agent_id' => $agent->id,
                'elevenlabs_agent_id' => $agent->elevenlabs_agent_id,
                'variables' => $variables,
                'processed_system_prompt' => $sessionData['processed_system_prompt'] ?? null,
                'processed_greeting_message' => $sessionData['processed_greeting_message'] ?? null
            ]);

            return [
                'url' => $websocketUrl,
                'session_id' => $sessionData['session_id'], // Use the session ID from VoiceCallService
                'agent_id' => $agent->elevenlabs_agent_id,
                'api_key' => $this->apiKey, // Will be used for authentication in WebSocket headers
                'expires_at' => now()->addHours(2)->toISOString(),
                'variables' => $variables,
                'processed_prompts' => [
                    'system_prompt' => $sessionData['processed_system_prompt'] ?? null,
                    'greeting_message' => $sessionData['processed_greeting_message'] ?? null
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get ElevenLabs WebSocket URL', [
                'agent_id' => $agent->id,
                'elevenlabs_agent_id' => $agent->elevenlabs_agent_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Transcribe audio using ElevenLabs Speech-to-Text
     */
    public function transcribeAudio(string $audioData, string $mimeType = 'audio/wav', ?string $language = null): ?string
    {
        try {
            // Decode base64 audio data
            $audioContent = base64_decode($audioData);
            
            // Validate audio content
            if (empty($audioContent)) {
                Log::error('ElevenLabs STT: Empty audio content after base64 decode');
                return null;
            }

            // Log audio details for debugging
            Log::info('ElevenLabs STT: Processing audio', [
                'original_base64_length' => strlen($audioData),
                'decoded_content_length' => strlen($audioContent),
                'content_starts_with' => substr(bin2hex($audioContent), 0, 16), // First 8 bytes in hex
                'language' => $language
            ]);

            // Create a temporary file for the audio
            $tempFile = tempnam(sys_get_temp_dir(), 'audio_transcribe_') . '.wav';
            
            // For debugging - let's try to create a proper WAV file
            $this->createValidWAVFile($audioContent, $tempFile);
            
            // Validate the created file
            if (!file_exists($tempFile) || filesize($tempFile) === 0) {
                Log::error('ElevenLabs STT: Failed to create valid temp file', [
                    'file_exists' => file_exists($tempFile),
                    'file_size' => file_exists($tempFile) ? filesize($tempFile) : 'N/A'
                ]);
                return null;
            }

            Log::info('ElevenLabs STT: Temp file created', [
                'temp_file' => $tempFile,
                'file_size' => filesize($tempFile)
            ]);
            

            // Prepare request payload with language support
            $payload = [
                'model_id' => 'scribe_v1'
            ];

            // Add language parameter if provided
            if ($language) {
                $langCode = strtolower(substr($language, 0, 2));
                $payload['language'] = $langCode;
                
                Log::info('ElevenLabs STT: Adding language parameter', [
                    'original_language' => $language,
                    'language_code' => $langCode
                ]);
            }

            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey,
            ])->attach(
                'file', file_get_contents($tempFile), 'audio.wav'
            )->post("{$this->baseUrl}/speech-to-text", $payload);

            // Clean up temp file
            unlink($tempFile);

            if ($response->successful()) {
                $result = $response->json();
                $transcript = $result['text'] ?? null;
                
                Log::info('ElevenLabs transcription successful', [
                    'transcript_length' => strlen($transcript ?? ''),
                    'transcript' => $transcript
                ]);
                
                return $transcript;
            }

            Log::error('ElevenLabs STT Error', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('ElevenLabs STT Service Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a valid WAV file from audio content
     */
    private function createValidWAVFile(string $audioContent, string $outputPath): void
    {
        // Check if the content already has WAV headers
        if (substr($audioContent, 0, 4) === 'RIFF') {
            // Already a WAV file, just write it
            file_put_contents($outputPath, $audioContent);
            return;
        }

        // If it's raw PCM data, we need to add WAV headers
        $sampleRate = 16000; // 16kHz
        $channels = 1; // Mono
        $bitsPerSample = 16; // 16-bit
        
        $dataSize = strlen($audioContent);
        $fileSize = $dataSize + 44 - 8; // Total file size - 8 bytes for RIFF header
        
        // Create WAV header
        $header = '';
        $header .= 'RIFF';                          // ChunkID
        $header .= pack('V', $fileSize);            // ChunkSize
        $header .= 'WAVE';                          // Format
        $header .= 'fmt ';                          // Subchunk1ID
        $header .= pack('V', 16);                   // Subchunk1Size (16 for PCM)
        $header .= pack('v', 1);                    // AudioFormat (1 for PCM)
        $header .= pack('v', $channels);            // NumChannels
        $header .= pack('V', $sampleRate);          // SampleRate
        $header .= pack('V', $sampleRate * $channels * $bitsPerSample / 8); // ByteRate
        $header .= pack('v', $channels * $bitsPerSample / 8); // BlockAlign
        $header .= pack('v', $bitsPerSample);       // BitsPerSample
        $header .= 'data';                          // Subchunk2ID
        $header .= pack('V', $dataSize);            // Subchunk2Size
        
        // Write WAV file
        file_put_contents($outputPath, $header . $audioContent);
    }

    /**
     * Create WebSocket connection to ElevenLabs
     */
    public function createWebSocketConnection(string $sessionId, string $agentId): array
    {
        try {
            $websocketUrl = 'wss://api.elevenlabs.io/v1/convai/conversation?agent_id=' . $agentId;
            
            Log::info('Creating ElevenLabs WebSocket connection', [
                'session_id' => $sessionId,
                'agent_id' => $agentId,
                'websocket_url' => $websocketUrl
            ]);

            return [
                'websocket_url' => $websocketUrl,
                'session_id' => $sessionId,
                'agent_id' => $agentId,
                'status' => 'connected',
                'created_at' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to create ElevenLabs WebSocket connection', [
                'session_id' => $sessionId,
                'agent_id' => $agentId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Send audio data to ElevenLabs WebSocket
     */
    public function sendAudioToWebSocket(string $sessionId, string $audioData): ?array
    {
        try {
            // For now, simulate the WebSocket communication
            // In production, this would send to the actual ElevenLabs WebSocket connection
            Log::info('Sending audio to ElevenLabs WebSocket', [
                'session_id' => $sessionId,
                'audio_size' => strlen($audioData)
            ]);

            // Simulate different types of responses from ElevenLabs
            $responses = [
                [
                    'type' => 'audio',
                    'audio_base64' => base64_encode('simulated_ai_response_' . time()),
                    'transcript' => 'Thank you for your message. How can I assist you further?'
                ],
                [
                    'type' => 'audio',
                    'audio_base64' => base64_encode('simulated_ai_response_' . time()),
                    'transcript' => 'I understand. Let me help you with that.'
                ],
                [
                    'type' => 'audio',
                    'audio_base64' => base64_encode('simulated_ai_response_' . time()),
                    'transcript' => 'That\'s a great question. Here\'s what I can tell you about that.'
                ]
            ];

            // Randomly return a response or null (to simulate when AI doesn't respond immediately)
            return rand(0, 2) ? $responses[array_rand($responses)] : null;

        } catch (\Exception $e) {
            Log::error('Failed to send audio to ElevenLabs WebSocket', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get the appropriate model ID based on language
     */
    private function getModelIdForLanguage(?string $language): string
    {
        if (!$language) {
            return 'eleven_flash_v2_5'; // Default model
        }

        // Map languages to appropriate ElevenLabs models
        $languageModelMap = [
            'en' => 'eleven_flash_v2_5',           // English - fastest, high quality
            'es' => 'eleven_multilingual_v2',      // Spanish
            'fr' => 'eleven_multilingual_v2',      // French
            'de' => 'eleven_multilingual_v2',      // German
            'it' => 'eleven_multilingual_v2',      // Italian
            'pt' => 'eleven_multilingual_v2',      // Portuguese
            'pl' => 'eleven_multilingual_v2',      // Polish
            'hi' => 'eleven_multilingual_v2',      // Hindi
            'ar' => 'eleven_multilingual_v2',      // Arabic
            'zh' => 'eleven_multilingual_v2',      // Chinese
            'ja' => 'eleven_multilingual_v2',      // Japanese
            'ko' => 'eleven_multilingual_v2',      // Korean
        ];

        // Get the first two characters of language code (e.g., 'en-US' -> 'en')
        $langCode = strtolower(substr($language, 0, 2));
        
        return $languageModelMap[$langCode] ?? 'eleven_multilingual_v2';
    }
}
