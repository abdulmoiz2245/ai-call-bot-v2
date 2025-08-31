<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Agent;

class OpenAIService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
    }

    /**
     * Transcribe audio using OpenAI Whisper
     */
    public function transcribeAudio(string $audioData, string $mimeType = 'audio/wav'): ?string
    {
        try {
            // Decode base64 audio data
            $audioContent = base64_decode($audioData);
            
            // Create a temporary file for the audio
            $tempFile = tempnam(sys_get_temp_dir(), 'whisper_transcribe_');
            file_put_contents($tempFile, $audioContent);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->attach(
                'file', file_get_contents($tempFile), 'audio.wav'
            )->post("{$this->baseUrl}/audio/transcriptions", [
                'model' => 'whisper-1',
                'response_format' => 'json'
            ]);

            // Clean up temp file
            unlink($tempFile);

            if ($response->successful()) {
                $result = $response->json();
                return $result['text'] ?? null;
            }

            Log::error('OpenAI Whisper Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('OpenAI Whisper Service Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate AI response using OpenAI ChatGPT
     */
    public function generateResponse(string $userMessage, Agent $agent, array $conversationHistory = [], ?string $processedSystemPrompt = null): ?array
    {
        try {
            // Build conversation context
            $messages = [];
            
            // Add system prompt (use processed version if provided)
            $systemPrompt = $processedSystemPrompt ?: $agent->system_prompt;
            if ($systemPrompt) {
                $messages[] = [
                    'role' => 'system',
                    'content' => $systemPrompt
                ];
                
                Log::info('Using system prompt in OpenAI request', [
                    'agent_id' => $agent->id,
                    'system_prompt' => $systemPrompt,
                    'is_processed' => !is_null($processedSystemPrompt)
                ]);
            }

            // Add conversation history with full context
            Log::info('Adding conversation history to OpenAI request', [
                'agent_id' => $agent->id,
                'history_messages_count' => count($conversationHistory),
                'conversation_history' => $conversationHistory
            ]);
            
            foreach ($conversationHistory as $message) {
                $messages[] = [
                    'role' => $message['role'] ?? 'user',
                    'content' => $message['content']
                ];
            }

            // Add current user message
            $messages[] = [
                'role' => 'user',
                'content' => $userMessage
            ];

            Log::info('Complete OpenAI request context', [
                'agent_id' => $agent->id,
                'total_messages' => count($messages),
                'messages' => $messages,
                'user_message' => $userMessage
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/chat/completions", [
                'model' => 'gpt-5-nano',
                'messages' => $messages,
                // 'max_completion_tokens' => 150,
                // 'temperature' => 0.7,
                // 'stream' => false
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $aiResponse = $result['choices'][0]['message']['content'] ?? null;
                
                if ($aiResponse) {
                    return [
                        'text' => $aiResponse,
                        'usage' => $result['usage'] ?? null,
                        'model' => $result['model'] ?? 'gpt-5-nano'
                    ];
                }
            }

            Log::error('OpenAI ChatGPT Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('OpenAI ChatGPT Service Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Test OpenAI API connection
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get("{$this->baseUrl}/models");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('OpenAI Connection Test Failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
