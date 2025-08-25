<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoiceCallAudioEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $sessionId;
    public string $audioData;
    public string $direction; // 'incoming' or 'outgoing'
    public array $metadata;
    public int $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(
        string $sessionId,
        string $audioData,
        string $direction = 'incoming',
        array $metadata = []
    ) {
        $this->sessionId = $sessionId;
        $this->audioData = $audioData;
        $this->direction = $direction;
        $this->metadata = $metadata;
        $this->timestamp = time();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('voice-call-audio.' . $this->sessionId)
        ];
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
        return [
            'session_id' => $this->sessionId,
            'audio_data' => $this->audioData,
            'direction' => $this->direction,
            'metadata' => $this->metadata,
            'timestamp' => $this->timestamp,
        ];
    }
}
