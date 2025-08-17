<?php

namespace App\Services;

use App\Models\Call;
use App\Models\Campaign;
use App\Models\Contact;
use App\Jobs\ProcessCallJob;
use Illuminate\Support\Facades\DB;

class CallingService
{
    public function scheduleCall(Campaign $campaign, Contact $contact): Call
    {
        return DB::transaction(function () use ($campaign, $contact) {
            $call = Call::create([
                'company_id' => $campaign->company_id,
                'campaign_id' => $campaign->id,
                'contact_id' => $contact->id,
                'agent_id' => $campaign->agent_id,
                'from_number' => $campaign->caller_id ?? $this->getDefaultCallerID($campaign->company),
                'to_number' => $contact->phone,
                'status' => 'queued',
                'retry_count' => 0,
            ]);

            // Dispatch the job to make the call
            ProcessCallJob::dispatch($call)->delay(now()->addSeconds(5));

            // Update contact status
            $contact->update(['status' => 'queued']);

            return $call;
        });
    }

    public function originateCall(Call $call): array
    {
        // Get the gateway adapter based on company settings
        $adapter = $this->getGatewayAdapter($call->company);
        
        try {
            $call->update([
                'status' => 'ringing',
                'started_at' => now(),
            ]);

            // Make the actual call through the gateway
            $response = $adapter->originateCall(
                $call->to_number,
                $call->from_number,
                $call->campaign_id,
                [
                    'call_id' => $call->id,
                    'contact_id' => $call->contact_id,
                    'agent_id' => $call->agent_id,
                    'campaign_id' => $call->campaign_id,
                ]
            );

            $call->update([
                'external_call_id' => $response['call_id'],
                'gateway_data' => $response,
            ]);

            // Emit call started event
            app(EventService::class)->emit('call.started', $call);

            return $response;

        } catch (\Exception $e) {
            $call->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
                'ended_at' => now(),
            ]);

            // Emit call failed event
            app(EventService::class)->emit('call.failed', $call, null, [
                'reason' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function handleWebhook(array $data): void
    {
        $callId = $data['call_id'] ?? null;
        if (!$callId) {
            return;
        }

        $call = Call::where('external_call_id', $callId)->first();
        if (!$call) {
            return;
        }

        $status = $this->mapGatewayStatus($data['status'] ?? '');
        
        $updates = [
            'status' => $status,
            'gateway_data' => array_merge($call->gateway_data ?? [], $data),
        ];

        if ($status === 'answered' && !$call->answered_at) {
            $updates['answered_at'] = now();
        }

        if (in_array($status, ['answered', 'voicemail', 'busy', 'failed', 'no_answer']) && !$call->ended_at) {
            $updates['ended_at'] = now();
            $updates['duration'] = $data['duration'] ?? 0;
            $updates['cost'] = $data['cost'] ?? 0;
        }

        $call->update($updates);

        // Update contact status based on call result
        if ($call->contact) {
            $contactStatus = $this->mapCallStatusToContactStatus($status);
            $call->contact->update([
                'status' => $contactStatus,
                'last_contacted_at' => now(),
            ]);
        }

        // Emit call status updated event
        app(EventService::class)->emit('call.status.updated', $call, null, [
            'old_status' => $call->getOriginal('status'),
            'new_status' => $status,
        ]);

        // Handle call completion
        if ($call->isCompleted()) {
            $this->handleCallCompletion($call);
        }
    }

    public function scheduleRetry(Call $call): ?Call
    {
        if ($call->retry_count >= $call->campaign->max_retries) {
            return null;
        }

        $retryCall = Call::create([
            'company_id' => $call->company_id,
            'campaign_id' => $call->campaign_id,
            'contact_id' => $call->contact_id,
            'agent_id' => $call->agent_id,
            'from_number' => $call->from_number,
            'to_number' => $call->to_number,
            'status' => 'queued',
            'retry_count' => $call->retry_count + 1,
        ]);

        // Schedule retry with delay
        $delay = $this->calculateRetryDelay($call->retry_count + 1);
        ProcessCallJob::dispatch($retryCall)->delay($delay);

        // Emit call retry scheduled event
        app(EventService::class)->emit('call.retry.scheduled', $retryCall);

        return $retryCall;
    }

    private function getGatewayAdapter($company)
    {
        // This would return the configured gateway adapter (Twilio, Maqsam, etc.)
        // For now, return a mock adapter
        return app(\App\Adapters\MockGatewayAdapter::class);
    }

    private function getDefaultCallerID($company): string
    {
        return $company->call_settings['default_caller_id'] ?? '+1234567890';
    }

    private function mapGatewayStatus(string $gatewayStatus): string
    {
        $statusMap = [
            'initiated' => 'queued',
            'ringing' => 'ringing',
            'in-progress' => 'answered',
            'completed' => 'answered',
            'busy' => 'busy',
            'failed' => 'failed',
            'no-answer' => 'no_answer',
            'cancelled' => 'failed',
        ];

        return $statusMap[$gatewayStatus] ?? 'failed';
    }

    private function mapCallStatusToContactStatus(string $callStatus): string
    {
        $statusMap = [
            'answered' => 'called',
            'voicemail' => 'called',
            'busy' => 'called',
            'no_answer' => 'called',
            'failed' => 'failed',
        ];

        return $statusMap[$callStatus] ?? 'failed';
    }

    public function handleCallCompletion(Call $call): void
    {
        // Check if campaign should continue or be completed
        $campaign = $call->campaign;
        
        // If this was the last call or campaign reached completion criteria
        if ($this->shouldCompleteCampaign($campaign)) {
            app(CampaignService::class)->completeCampaign($campaign, $call->created_by ?? 1);
        } else {
            // Enqueue next batch of calls
            app(CampaignService::class)->enqueueCampaignContacts($campaign);
        }
    }

    private function shouldCompleteCampaign(Campaign $campaign): bool
    {
        // Check if there are more eligible contacts to call
        $eligibleContacts = app(CampaignService::class)->getEligibleContactsQuery($campaign)->count();
        
        return $eligibleContacts === 0;
    }

    private function calculateRetryDelay(int $retryCount): \Carbon\Carbon
    {
        // Exponential backoff: 5 minutes, 15 minutes, 30 minutes
        $delays = [5, 15, 30];
        $minutes = $delays[$retryCount - 1] ?? 60;
        
        return now()->addMinutes($minutes);
    }

    public function initiateCall(Campaign $campaign, Contact $contact): Call
    {
        // Check if contact is on DNC list
        if ($contact->is_dnc) {
            throw new \Exception('Contact is on Do Not Call list');
        }

        // Check campaign status
        if (!$campaign->isActive()) {
            throw new \Exception('Campaign is not active');
        }

        return $this->scheduleCall($campaign, $contact);
    }

    public function retryCall(Call $call): Call
    {
        if ($call->retry_count >= $call->campaign->max_retries) {
            throw new \Exception('Maximum retry attempts reached');
        }

        $newCall = Call::create([
            'company_id' => $call->company_id,
            'campaign_id' => $call->campaign_id,
            'contact_id' => $call->contact_id,
            'agent_id' => $call->agent_id,
            'from_number' => $call->from_number,
            'to_number' => $call->to_number,
            'status' => 'queued',
            'retry_count' => $call->retry_count + 1,
        ]);

        ProcessCallJob::dispatch($newCall)->delay($this->calculateRetryDelay($call->retry_count + 1));

        return $newCall;
    }
}
