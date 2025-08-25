<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Events\VoiceCallStatusUpdated;
use App\Services\VoiceCallService;


Route::get('/test-broadcast/{channel}', function ($channel) {
    $event = new VoiceCallStatusUpdated($channel, 'connected', 'Manual test broadcast');
    
    // Log the attempt with detailed debugging
    Log::info('Manual broadcast test', [
        'channel' => $channel,
        'event_class' => get_class($event),
        'broadcast_on' => $event->broadcastOn(),
        'broadcast_with' => $event->broadcastWith(),
        'current_user' => Auth::id(),
        'broadcasting_config' => [
            'default' => config('broadcasting.default'),
            'broadcaster' => get_class(app('Illuminate\Contracts\Broadcasting\Broadcaster'))
        ]
    ]);
    
    // Try different broadcast methods
    try {
        $result = broadcast($event);
        Log::info('Manual broadcast result', [
            'channel' => $channel,
            'result' => $result,
            'result_type' => gettype($result)
        ]);
        
        // Try alternative broadcasting method
        $broadcaster = app('Illuminate\Contracts\Broadcasting\Broadcaster');
        $directResult = $broadcaster->broadcast(
            [$channel],
            'App\\Events\\VoiceCallStatusUpdated',
            $event->broadcastWith()
        );
        
        Log::info('Manual direct broadcaster result', [
            'channel' => $channel,
            'direct_result' => $directResult
        ]);
        
    } catch (\Exception $e) {
        Log::error('Manual broadcast failed', [
            'channel' => $channel,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    return response()->json([
        'message' => 'Broadcast sent',
        'channel' => $channel,
        'event_data' => $event->broadcastWith()
    ]);
});
