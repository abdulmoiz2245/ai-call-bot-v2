<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VoiceCallClientEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $channel,
        public string $event,
        public array $data
    ) {
        Log::info('VoiceCallClientEvent created', [
            'channel' => $this->channel,
            'event' => $this->event,
            'data_keys' => array_keys($this->data)
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel($this->channel),
        ];
    }
}
