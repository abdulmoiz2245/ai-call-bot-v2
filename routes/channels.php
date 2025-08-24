<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Voice call channels - allow authenticated users to join voice call sessions
Broadcast::channel('voice-call.{sessionId}', function ($user, $sessionId) {
    // Allow authenticated users to join voice call channels
    // You could add additional authorization logic here
    return $user !== null;
});
