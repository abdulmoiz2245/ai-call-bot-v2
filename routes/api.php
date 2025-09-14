<?php

use App\Http\Controllers\CallController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

Route::post('webhooks/calls', [CallController::class, 'webhook'])->name('webhooks.calls');
