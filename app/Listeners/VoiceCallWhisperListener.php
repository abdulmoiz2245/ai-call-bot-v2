<?php

namespace App\Listeners;

use App\Services\VoiceCallService;
use Illuminate\Support\Facades\Log;
use Laravel\Reverb\Events\MessageReceived;

class VoiceCallWhisperListener
{
    public function __construct(
        private VoiceCallService $voiceCallService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(MessageReceived $event): void
    {
        $message = json_decode($event->message, true);
        
        
        // Check if this is a client event on a voice-call channel (both public and private)
        $channel = $message['channel'] ?? '';
        $isVoiceCallChannel = str_starts_with($channel, 'voice-call.') || str_starts_with($channel, 'private-voice-call.');
        
        if ($isVoiceCallChannel) {
            $eventName = $message['event'] ?? null;
            
            // Only handle client events (events starting with 'client-')
            if ($eventName && str_starts_with($eventName, 'client-')) {
                $data = isset($message['data']) ? json_decode($message['data'], true) : [];
                
                Log::info('Received client event via Reverb', [
                    'channel' => $channel,
                    'event' => $eventName,
                    'data_keys' => is_array($data) ? array_keys($data) : 'not_array',
                    'session_id' => $data['session_id'] ?? 'no_session',
                    'audio_data_length' => isset($data['audio_data']) ? strlen($data['audio_data']) : 'no_audio'
                ]);
                
                switch ($eventName) {
                    case 'client-audio-data': // Fixed: match the event name from Vue component
                        if (isset($data['session_id']) && isset($data['audio_data'])) {
                            Log::info('Processing audio data from WebSocket client event', [
                                'session_id' => $data['session_id'],
                                'audio_size' => strlen($data['audio_data']),
                                'user_id' => $data['user_id'] ?? 'unknown',
                                'timestamp' => $data['timestamp'] ?? 'no_timestamp'
                            ]);
                            
                            // Call the enhanced audio processing pipeline
                            $result = $this->voiceCallService->processAudioChunk(
                                $data['session_id'], 
                                $data['audio_data'],
                                [
                                    'user_id' => $data['user_id'] ?? null,
                                    'timestamp' => $data['timestamp'] ?? time(),
                                    'audio_format' => $data['audio_format'] ?? 'wav',
                                    'sample_rate' => $data['sample_rate'] ?? 16000,
                                    'source' => 'websocket_whisper'
                                ]
                            );
                            
                            if ($result) {
                                Log::info('Audio processing completed successfully', [
                                    'session_id' => $data['session_id'],
                                    'has_ai_response' => !empty($result)
                                ]);
                            } else {
                                Log::warning('Audio processing failed or returned no result', [
                                    'session_id' => $data['session_id']
                                ]);
                            }
                        } else {
                            Log::warning('Invalid audio data received from client event', [
                                'session_id' => $data['session_id'] ?? 'missing',
                                'has_audio_data' => isset($data['audio_data']),
                                'data_keys' => is_array($data) ? array_keys($data) : 'not_array'
                            ]);
                        }
                        break;
                        
                    case 'client-end-call':
                        if (isset($data['session_id'])) {
                            Log::info('Processing end call from WebSocket client event', [
                                'session_id' => $data['session_id'],
                                'reason' => $data['reason'] ?? 'client_ended'
                            ]);
                            $this->voiceCallService->endCall($data['session_id'], $data['reason'] ?? 'client_ended');
                        }
                        break;
                        
                    default:
                        Log::info('Unhandled client event', [
                            'event' => $eventName,
                            'channel' => $message['channel']
                        ]);
                }
            }
        }
    }
}
