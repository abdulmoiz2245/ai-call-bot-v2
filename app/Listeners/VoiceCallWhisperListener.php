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
        
        // Check if this is a client event on a voice-call channel
        if (isset($message['channel']) && str_starts_with($message['channel'], 'voice-call.')) {
            $eventName = $message['event'] ?? null;
            
            // Only handle client events (events starting with 'client-')
            if ($eventName && str_starts_with($eventName, 'client-')) {
                $data = isset($message['data']) ? json_decode($message['data'], true) : [];
                
                Log::info('Received client event via Reverb', [
                    'channel' => $message['channel'],
                    'event' => $eventName,
                    'data_keys' => is_array($data) ? array_keys($data) : 'not_array',
                    'session_id' => $data['session_id'] ?? 'no_session'
                ]);
                
                switch ($eventName) {
                    case 'client-audio-data':
                        if (isset($data['session_id']) && isset($data['audio_data'])) {
                            Log::info('Processing audio data from WebSocket client event', [
                                'session_id' => $data['session_id'],
                                'audio_size' => strlen($data['audio_data'])
                            ]);
                            $this->voiceCallService->handleAudioData($data['session_id'], $data['audio_data']);
                        } else {
                            Log::warning('Invalid audio data received from client event', [
                                'session_id' => $data['session_id'] ?? 'missing',
                                'has_audio_data' => isset($data['audio_data'])
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
