<?php

namespace App\Jobs;

use App\Services\VoiceCallService;
use App\Events\VoiceCallAudioEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAudioForAgent implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 120; // 2 minutes timeout for audio processing

    public function __construct(
        public string $sessionId,
        public string $audioFilePath,
        public array $metadata = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing audio file in background job with session context', [
                'session_id' => $this->sessionId,
                'file_path' => $this->audioFilePath,
                'metadata' => $this->metadata,
                'has_session_variables' => !empty($this->metadata['variables'] ?? [])
            ]);

            // Process audio file through the voice call service
            $voiceCallService = app(VoiceCallService::class);
            $aiResponse = $voiceCallService->processAudioFile(
                $this->sessionId, 
                $this->audioFilePath, 
                $this->metadata
            );

            // Clean up the temporary file after processing
            if (file_exists($this->audioFilePath)) {
                unlink($this->audioFilePath);
                Log::info('Temporary audio file cleaned up', [
                    'file_path' => $this->audioFilePath
                ]);
            }

            // If we got an AI response, broadcast it to the frontend
            if ($aiResponse && isset($aiResponse['audio_base64'])) {
                // Store the audio response as a file instead of broadcasting large base64
                $responseAudioPath = $this->storeResponseAudio($aiResponse['audio_base64'], $this->sessionId);
              
                // Determine broadcast type based on response
                $broadcastType = $aiResponse['type'] === 'call_end' ? 'call_end' : 'outgoing';
                
                broadcast(new VoiceCallAudioEvent(
                    $this->sessionId,
                    '', // Don't send base64 audio data
                    $broadcastType,
                    [
                        'type' => $aiResponse['type'] ?? 'ai_response',
                        'transcript' => $aiResponse['transcript'] ?? null,
                        'user_transcript' => $aiResponse['user_transcript'] ?? null,
                        'ai_model' => $aiResponse['ai_model'] ?? null,
                        'transcription_service' => $aiResponse['transcription_service'] ?? null,
                        'tts_service' => $aiResponse['tts_service'] ?? null,
                        'audio_url' => $responseAudioPath ? config('app.url') . "/audio-response/{$this->sessionId}/" . basename($responseAudioPath) : null,
                        'audio_file' => $responseAudioPath,
                        'timestamp' => time(),
                        'processing_time' => $this->metadata['start_time'] ? (time() - $this->metadata['start_time']) : null,
                        'request_id' => $aiResponse['request_id'] ?? null,
                        'audio_id' => $aiResponse['audio_id'] ?? null,
                        'should_end_call' => $aiResponse['should_end_call'] ?? false,
                        'call_end_after_audio' => $aiResponse['call_end_after_audio'] ?? false
                    ]
                ));

                Log::info('AI response broadcasted successfully with audio URL', [
                    'session_id' => $this->sessionId,
                    'response_type' => $aiResponse['type'] ?? 'ai_response',
                    'broadcast_type' => $broadcastType,
                    'transcript_length' => strlen($aiResponse['transcript'] ?? ''),
                    'audio_url' => $responseAudioPath ? config('app.url') . "/audio-response/{$this->sessionId}/" . basename($responseAudioPath) : null,
                    'audio_file_path' => $responseAudioPath,
                    'config_url' => config('app.url'),
                    'user_transcript' => $aiResponse['user_transcript'] ?? null,
                    'ai_transcript' => $aiResponse['transcript'] ?? null,
                    'should_end_call' => $aiResponse['should_end_call'] ?? false,
                    'request_id' => $aiResponse['request_id'] ?? null,
                    'audio_id' => $aiResponse['audio_id'] ?? null
                ]);
            } else {
                Log::warning('No AI response generated for audio file', [
                    'session_id' => $this->sessionId,
                    'file_path' => $this->audioFilePath
                ]);

                // Broadcast an error message to the frontend
                broadcast(new VoiceCallAudioEvent(
                    $this->sessionId,
                    '',
                    'error',
                    [
                        'type' => 'processing_error',
                        'message' => 'Failed to generate AI response',
                        'timestamp' => time()
                    ]
                ));
            }

        } catch (\Exception $e) {
            Log::error('Failed to process audio file in background job', [
                'session_id' => $this->sessionId,
                'file_path' => $this->audioFilePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Clean up file even on error
            if (file_exists($this->audioFilePath)) {
                unlink($this->audioFilePath);
            }

            // Broadcast error to frontend
            broadcast(new VoiceCallAudioEvent(
                $this->sessionId,
                '',
                'error',
                [
                    'type' => 'processing_error',
                    'message' => 'Audio processing failed: ' . $e->getMessage(),
                    'timestamp' => time()
                ]
            ));

            // Optionally retry the job
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Audio processing job failed permanently', [
            'session_id' => $this->sessionId,
            'file_path' => $this->audioFilePath,
            'error' => $exception->getMessage()
        ]);

        // Clean up file on permanent failure
        if (file_exists($this->audioFilePath)) {
            unlink($this->audioFilePath);
        }

        // Broadcast final error to frontend
        broadcast(new VoiceCallAudioEvent(
            $this->sessionId,
            '',
            'error',
            [
                'type' => 'processing_failed',
                'message' => 'Audio processing failed permanently',
                'timestamp' => time()
            ]
        ));
    }

    /**
     * Store the AI response audio as a file and return the relative path
     */
    private function storeResponseAudio(string $audioBase64, string $sessionId): ?string
    {
        try {
            $audioData = base64_decode($audioBase64);
            if ($audioData === false) {
                Log::error('Failed to decode base64 audio data for storage');
                return null;
            }

            // ElevenLabs typically returns MP3, but let's ensure compatibility
            $fileName = 'response_' . uniqid() . '.mp3';
            $relativePath = "{$sessionId}/{$fileName}";
            $fullPath = storage_path("app/public/audio_responses/{$relativePath}");

            // Create directory if it doesn't exist
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Write the audio file
            $bytesWritten = file_put_contents($fullPath, $audioData);
            if ($bytesWritten === false) {
                Log::error('Failed to write audio response file', ['path' => $fullPath]);
                return null;
            }

            // Verify the file was created and is readable
            if (!file_exists($fullPath) || !is_readable($fullPath)) {
                Log::error('Audio file was not created properly', ['path' => $fullPath]);
                return null;
            }

            Log::info('Audio response file stored successfully', [
                'path' => $relativePath,
                'full_path' => $fullPath,
                'size' => $bytesWritten,
                'is_readable' => is_readable($fullPath)
            ]);

            return $relativePath;

        } catch (\Exception $e) {
            Log::error('Failed to store audio response file', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);
            return null;
        }
    }
}
