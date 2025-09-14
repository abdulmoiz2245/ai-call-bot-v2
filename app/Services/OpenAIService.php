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
    public function transcribeAudio(string $audioData, string $mimeType = 'audio/wav', ?string $language = null): ?string
    {
        try {
            // Decode base64 audio data
            $audioContent = base64_decode($audioData);
            
            // Create a temporary file for the audio
            $tempFile = tempnam(sys_get_temp_dir(), 'whisper_transcribe_');
            file_put_contents($tempFile, $audioContent);

            // Prepare request payload
            $requestData = [
                'model' => 'whisper-1',
                'response_format' => 'json'
            ];

            // Add language parameter if provided (Whisper supports ISO 639-1 language codes)
            if ($language) {
                $langCode = strtolower(substr($language, 0, 2));
                $requestData['language'] = $langCode;
                
                Log::info('OpenAI Whisper: Adding language parameter', [
                    'original_language' => $language,
                    'language_code' => $langCode
                ]);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->attach(
                'file', file_get_contents($tempFile), 'audio.wav'
            )->post("{$this->baseUrl}/audio/transcriptions", $requestData);

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
     * Generate AI response using OpenAI ChatGPT with call end detection
     */
    public function generateResponse(string $userMessage, Agent $agent, array $conversationHistory = [], ?string $processedSystemPrompt = null, ?string $language = null): ?array
    {
        try {
            // Build conversation context
            $messages = [];
            
            // Add system prompt (use processed version if provided)
            $systemPrompt = $processedSystemPrompt ?: $agent->system_prompt;
            if ($systemPrompt) {
                // Enhance system prompt to include call end decision making and language awareness
                $enhancedSystemPrompt = $systemPrompt;
                
                // Add language instruction if provided
                if ($language) {
                    $languageInstruction = "\n\nIMPORTANT LANGUAGE INSTRUCTION: Respond in the same language as the user input. The expected language is: {$language}. Maintain natural conversation flow in this language.";
                    $enhancedSystemPrompt .= $languageInstruction;
                }
                
                $enhancedSystemPrompt .= "\n\nIMPORTANT: You must respond in valid JSON format with two fields:\n1. \"response\" - your conversational response to the user\n2. \"call_end\" - boolean (true if the conversation should end, false to continue)\n\nDecide to end the call when:\n- User says goodbye, bye, hang up, or similar farewell\n- Conversation has naturally concluded\n- User indicates they're done or satisfied\n- You've completed the task or purpose of the call\n- Call has been going on for a very long time (over 20 exchanges)\n\nExample response format:\n{\"response\": \"Thank you for calling! Have a great day.\", \"call_end\": true}";
                
                $messages[] = [
                    'role' => 'system',
                    'content' => $enhancedSystemPrompt
                ];
                
                Log::info('Using enhanced system prompt with call end detection and language support', [
                    'agent_id' => $agent->id,
                    'language' => $language,
                    'original_system_prompt' => $systemPrompt,
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
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $aiResponse = $result['choices'][0]['message']['content'] ?? null;
                
                if ($aiResponse) {
                    // Try to parse JSON response
                    $jsonResponse = json_decode($aiResponse, true);
                    
                    if ($jsonResponse && isset($jsonResponse['response'])) {
                        return [
                            'text' => $jsonResponse['response'],
                            'call_end' => $jsonResponse['call_end'] ?? false,
                            'usage' => $result['usage'] ?? null,
                            'model' => $result['model'] ?? 'gpt-4o-mini',
                            'raw_response' => $aiResponse
                        ];
                    } else {
                        // Fallback if JSON parsing fails
                        Log::warning('Failed to parse JSON response from OpenAI, using fallback', [
                            'agent_id' => $agent->id,
                            'raw_response' => $aiResponse
                        ]);
                        
                        return [
                            'text' => $aiResponse,
                            'call_end' => false,
                            'usage' => $result['usage'] ?? null,
                            'model' => $result['model'] ?? 'gpt-4o-mini',
                            'raw_response' => $aiResponse
                        ];
                    }
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
