<?php

use Illuminate\Support\Facades\Broadcast;
use App\Services\VoiceCallService;
use Illuminate\Support\Facades\Log;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Voice call channels - allow access to voice call sessions
Broadcast::channel('voice-call.{agentId}.{uniqueId}', function ($user, $agentId, $uniqueId) {
    // For now, allow any user to join voice call channels
    // In production, you might want to check session ownership or user permissions
    return true;
});

// Private voice call audio channels - for real-time audio streaming
Broadcast::channel('voice-call-audio.{sessionId}', function ($user, $sessionId) {
    // Verify user has access to this session
    $voiceCallService = app(VoiceCallService::class);
    $sessionData = $voiceCallService->getSessionData($sessionId);
    
    if (!$sessionData) {
        Log::warning('Unauthorized access attempt to audio channel', [
            'user_id' => $user->id,
            'session_id' => $sessionId
        ]);
        return false;
    }
    
    // Allow access if session exists and is active
    return [
        'id' => $user->id,
        'name' => $user->name,
        'session_id' => $sessionId
    ];
});
