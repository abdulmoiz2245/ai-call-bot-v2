<?php

use Illuminate\Support\Facades\Broadcast;
use App\Services\VoiceCallService;
use Illuminate\Support\Facades\Log;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private voice call channels - allow access to voice call sessions
Broadcast::channel('voice-call.{agentId}.{uniqueId}', function ($user, $agentId, $uniqueId) {
    // // Verify user has access to this agent and call session
    // $voiceCallService = app(VoiceCallService::class);
    
    // // Check if the session exists and belongs to this user
    // $sessionData = $voiceCallService->getSessionData($uniqueId);
    
    // if (!$sessionData) {
    //     Log::warning('Unauthorized access attempt to voice call channel', [
    //         'user_id' => $user->id,
    //         'agent_id' => $agentId,
    //         'unique_id' => $uniqueId
    //     ]);
    //     return false;
    // }
    
    // // Verify the agent belongs to the user's company or the user has access
    // // You may need to adjust this logic based on your business rules
    // if (!$user->hasAccessToAgent($agentId)) {
    //     Log::warning('User attempted to access unauthorized agent', [
    //         'user_id' => $user->id,
    //         'agent_id' => $agentId
    //     ]);
    //     return false;
    // }
    
    return [
        'id' => $user->id,
        'name' => $user->name,
        'agent_id' => $agentId,
        'session_id' => $uniqueId
    ];
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
