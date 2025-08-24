<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\VoiceCallController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified', 'tenant.scope'])->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Campaigns
    Route::resource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/activate', [CampaignController::class, 'activate'])->name('campaigns.activate');
    Route::post('campaigns/{campaign}/pause', [CampaignController::class, 'pause'])->name('campaigns.pause');
    Route::post('campaigns/{campaign}/complete', [CampaignController::class, 'complete'])->name('campaigns.complete');
    
    // Contacts
    Route::resource('contacts', ContactController::class);
    Route::post('contacts/{contact}/dnc', [ContactController::class, 'addToDnc'])->name('contacts.dnc.add');
    Route::delete('contacts/{contact}/dnc', [ContactController::class, 'removeFromDnc'])->name('contacts.dnc.remove');
    Route::post('contacts/import', [ContactController::class, 'import'])->name('contacts.import');
    Route::get('contacts/export', [ContactController::class, 'export'])->name('contacts.export');
    
    // Import functionality
    Route::get('import/create', [ImportController::class, 'create'])->name('import.create');
    Route::post('import/parse-headers', [ImportController::class, 'parseHeaders'])->name('import.parse-headers');
    Route::post('import/dry-run', [ImportController::class, 'dryRun'])->name('import.dry-run');
    Route::post('import/import', [ImportController::class, 'import'])->name('import.import');
    
    // Orders (E-commerce)
    Route::resource('orders', OrderController::class);
    Route::post('orders/import', [OrderController::class, 'import'])->name('orders.import');
    Route::get('orders/export', [OrderController::class, 'export'])->name('orders.export');
    
    // Agents
    Route::resource('agents', AgentController::class);
    Route::post('agents/{agent}/toggle-status', [AgentController::class, 'toggleStatus'])->name('agents.toggle-status');
    Route::post('agents/{agent}/test', [AgentController::class, 'test'])->name('agents.test');
    Route::get('agents/{agent}/variables', [AgentController::class, 'getVariables'])->name('agents.variables');
    Route::get('agents/{agent}/call-test', [AgentController::class, 'callTest'])->name('agents.call-test');
    Route::post('agents/{agent}/voice-connection', [AgentController::class, 'getVoiceWebSocket'])->name('agents.voice-connection');
    Route::post('agents/{agent}/clone', [AgentController::class, 'clone'])->name('agents.clone');
    
    // ElevenLabs Integration (Super Admin only)
    Route::get('agents/elevenlabs/list', [AgentController::class, 'getElevenLabsAgents'])->name('agents.elevenlabs.list');
    Route::post('agents/{agent}/elevenlabs/connect', [AgentController::class, 'connectToElevenLabs'])->name('agents.elevenlabs.connect');
    Route::post('agents/{agent}/elevenlabs/disconnect', [AgentController::class, 'disconnectFromElevenLabs'])->name('agents.elevenlabs.disconnect');
    
    // Voice Calls (Laravel Reverb WebSocket)
    Route::post('agents/{agent}/voice-call/initialize', [VoiceCallController::class, 'initialize'])->name('voice-call.initialize');
    Route::post('voice-call/send-audio', [VoiceCallController::class, 'sendAudio'])->name('voice-call.send-audio');
    Route::post('voice-call/end', [VoiceCallController::class, 'endCall'])->name('voice-call.end');
    Route::get('voice-call/{sessionId}/status', [VoiceCallController::class, 'getSessionStatus'])->name('voice-call.status');
    
    // WebSocket Event Handlers (for client events)
    Route::post('voice-call/websocket/audio', [VoiceCallController::class, 'handleWebSocketAudio'])->name('voice-call.websocket.audio');
    Route::post('voice-call/websocket/end', [VoiceCallController::class, 'handleWebSocketEnd'])->name('voice-call.websocket.end');
    
    // Calls
    Route::resource('calls', CallController::class)->only(['index', 'show']);
    Route::post('calls/initiate', [CallController::class, 'initiate'])->name('calls.initiate');
    Route::get('calls/analytics', [CallController::class, 'analytics'])->name('calls.analytics');
    Route::get('calls/{call}/recording', [CallController::class, 'downloadRecording'])->name('calls.recording');
    Route::post('calls/{call}/retry', [CallController::class, 'retry'])->name('calls.retry');
    Route::post('calls/bulk-action', [CallController::class, 'bulkAction'])->name('calls.bulk-action');
    
    // Analytics
    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');
});

// Webhook routes (no auth required)
Route::post('webhooks/calls', [CallController::class, 'webhook'])->name('webhooks.calls');
Route::post('webhooks/voice-call/metadata', [VoiceCallController::class, 'handleConversationMetadata'])->name('webhooks.voice-call.metadata');

// TwiML routes (no auth required - for Twilio callbacks)
Route::prefix('api/twiml')->name('twiml.')->group(function () {
    Route::post('call', [\App\Http\Controllers\TwiMLController::class, 'call'])->name('call');
    Route::post('flow', [\App\Http\Controllers\TwiMLController::class, 'flow'])->name('flow');
    Route::post('status', [\App\Http\Controllers\TwiMLController::class, 'status'])->name('status');
    Route::post('recording', [\App\Http\Controllers\TwiMLController::class, 'recording'])->name('recording');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
