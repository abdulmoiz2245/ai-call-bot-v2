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
    public function textToSpeech(string $text, string $voiceId, array $voiceSettings = []): ?string
    {
        try {
            $defaultSettings = [
                'stability' => 0.5,
                'similarity_boost' => 0.75,
                'style' => 0.5,
                'use_speaker_boost' => true,
            ];

            $settings = array_merge($defaultSettings, $voiceSettings);

            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'audio/mpeg',
            ])->post("{$this->baseUrl}/text-to-speech/{$voiceId}", [
                'text' => $text,
                'model_id' => 'eleven_monolingual_v1',
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
                    'first_message' => $agent->greeting_message,
                    'prompt' => [
                        'prompt' => $agent->system_prompt,
                        // 'built_in_tools' => $this->mapAgentTools($agent),
                    ],
                ],
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
}
