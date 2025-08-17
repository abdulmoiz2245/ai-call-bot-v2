<?php

namespace App\Jobs;

use App\Models\Call;
use App\Services\CallingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCallJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public function __construct(
        public Call $call
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            app(CallingService::class)->originateCall($this->call);
        } catch (\Exception $e) {
            // Log the error and potentially schedule retry
            Log::error('Failed to process call: ' . $e->getMessage(), [
                'call_id' => $this->call->id,
                'error' => $e->getMessage(),
            ]);

            // Release back to queue for retry
            $this->release(60);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->call->update([
            'status' => 'failed',
            'failure_reason' => $exception->getMessage(),
            'ended_at' => now(),
        ]);
    }
}
