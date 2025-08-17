<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            ])->get("{$this->baseUrl}/voices");

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
}
