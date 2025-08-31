<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VoiceCallAudioEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $sessionId;
    public string $audioData;
    public string $direction; // 'incoming' or 'outgoing'
    public array $metadata;
    public int $timestamp;
    private float $constructTime;

    /**
     * Create a new event instance.
     */
    public function __construct(
        string $sessionId,
        string $audioData,
        string $direction = 'incoming',
        array $metadata = []
    ) {
        $this->constructTime = microtime(true);
        
        Log::info('VoiceCallAudioEvent constructor started', [
            'session_id' => $sessionId,
            'direction' => $direction,
            'audio_data_size' => strlen($audioData),
            'metadata' => $metadata,
            'start_time' => $this->constructTime
        ]);

        $this->sessionId = $sessionId;
        $this->audioData = $audioData;
        $this->direction = $direction;
        $this->metadata = $metadata;
        $this->timestamp = time();
        
        $constructEndTime = microtime(true);
        Log::info('VoiceCallAudioEvent constructor completed', [
            'session_id' => $sessionId,
            'construct_duration' => round(($constructEndTime - $this->constructTime) * 1000, 2) . 'ms'
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $start = microtime(true);
        Log::info('VoiceCallAudioEvent broadcastOn started', [
            'session_id' => $this->sessionId,
            'start_time' => $start
        ]);
        
        $channels = [
            new PrivateChannel('voice-call-audio.' . $this->sessionId)
        ];
        
        $end = microtime(true);
        Log::info('VoiceCallAudioEvent broadcastOn completed', [
            'session_id' => $this->sessionId,
            'duration' => round(($end - $start) * 1000, 2) . 'ms'
        ]);
        
        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'voice.call.audio';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $start = microtime(true);
        Log::info('VoiceCallAudioEvent broadcastWith started', [
            'session_id' => $this->sessionId,
            'audio_data_size' => strlen($this->audioData),
            'start_time' => $start
        ]);
        
        $data = [
            'session_id' => $this->sessionId,
            'audio_data' => $this->audioData,
            'direction' => $this->direction,
            'metadata' => $this->metadata,
            'timestamp' => $this->timestamp,
        ];
        
        $end = microtime(true);
        Log::info('VoiceCallAudioEvent broadcastWith completed', [
            'session_id' => $this->sessionId,
            'duration' => round(($end - $start) * 1000, 2) . 'ms',
            'total_event_duration' => round(($end - $this->constructTime) * 1000, 2) . 'ms'
        ]);
        
        return $data;
    }
}
