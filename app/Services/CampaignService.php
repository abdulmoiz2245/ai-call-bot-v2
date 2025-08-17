<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Order;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    public function createCampaign(array $data, int $companyId, int $userId): Campaign
    {
        return DB::transaction(function () use ($data, $companyId, $userId) {
            $campaign = Campaign::create([
                'company_id' => $companyId,
                'created_by' => $userId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'agent_id' => $data['agent_id'] ?? null,
                'data_source_type' => $data['data_source_type'],
                'schedule_settings' => $data['schedule_settings'] ?? null,
                'max_retries' => $data['max_retries'] ?? 3,
                'max_concurrency' => $data['max_concurrency'] ?? 5,
                'call_order' => $data['call_order'] ?? 'sequential',
                'record_calls' => $data['record_calls'] ?? false,
                'caller_id' => $data['caller_id'] ?? null,
                'filter_criteria' => $data['filter_criteria'] ?? null,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'status' => 'draft',
            ]);

            // Emit campaign created event
            app(EventService::class)->emit('campaign.created', $campaign, $userId);

            return $campaign;
        });
    }

    public function updateCampaign(Campaign $campaign, array $data, int $userId): Campaign
    {
        return DB::transaction(function () use ($campaign, $data, $userId) {
            $campaign->update($data);

            // Emit campaign updated event
            app(EventService::class)->emit('campaign.updated', $campaign, $userId);

            return $campaign;
        });
    }

    public function activateCampaign(Campaign $campaign, int $userId): Campaign
    {
        if (!$campaign->isDraft()) {
            throw new \Exception('Only draft campaigns can be activated');
        }

        $campaign->update(['status' => 'active']);

        // Emit campaign activated event
        app(EventService::class)->emit('campaign.activated', $campaign, $userId);

        // Enqueue eligible contacts for calling
        $this->enqueueCampaignContacts($campaign);

        return $campaign;
    }

    public function pauseCampaign(Campaign $campaign, int $userId): Campaign
    {
        if (!$campaign->isActive()) {
            throw new \Exception('Only active campaigns can be paused');
        }

        $campaign->update(['status' => 'paused']);

        // Emit campaign paused event
        app(EventService::class)->emit('campaign.paused', $campaign, $userId);

        return $campaign;
    }

    public function completeCampaign(Campaign $campaign, int $userId): Campaign
    {
        $campaign->update(['status' => 'completed']);

        // Emit campaign completed event
        app(EventService::class)->emit('campaign.completed', $campaign, $userId);

        return $campaign;
    }

    public function enqueueCampaignContacts(Campaign $campaign): int
    {
        $query = $this->getEligibleContactsQuery($campaign);
        
        $contacts = $query->limit($campaign->max_concurrency)->get();
        
        foreach ($contacts as $contact) {
            // Update contact status to queued
            $contact->update(['status' => 'queued']);
            
            // Dispatch call job
            app(CallingService::class)->scheduleCall($campaign, $contact);
        }

        return $contacts->count();
    }

    public function getEligibleContactsQuery(Campaign $campaign)
    {
        $query = null;

        switch ($campaign->data_source_type) {
            case 'contacts':
                $query = Contact::where('company_id', $campaign->company_id)
                    ->where('status', 'new')
                    ->where('is_dnc', false);
                break;
            
            case 'orders':
                $query = Order::where('company_id', $campaign->company_id)
                    ->whereHas('contact', function ($q) {
                        $q->where('is_dnc', false);
                    });
                break;
            
            case 'leads':
                // Similar to contacts but with different criteria
                $query = Contact::where('company_id', $campaign->company_id)
                    ->where('status', 'new')
                    ->where('is_dnc', false)
                    ->whereNotNull('segment');
                break;
        }

        // Apply filter criteria if provided
        if ($campaign->filter_criteria) {
            $this->applyFilterCriteria($query, $campaign->filter_criteria);
        }

        return $query;
    }

    private function applyFilterCriteria($query, array $criteria)
    {
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
    }

    public function getCampaignStats(Campaign $campaign): array
    {
        $calls = $campaign->calls();

        return [
            'total_calls' => $calls->count(),
            'attempts' => $calls->count(),
            'connected' => $calls->where('status', 'answered')->count(),
            'voicemail' => $calls->where('status', 'voicemail')->count(),
            'busy' => $calls->where('status', 'busy')->count(),
            'failed' => $calls->where('status', 'failed')->count(),
            'no_answer' => $calls->where('status', 'no_answer')->count(),
            'total_cost' => $calls->sum('cost'),
            'avg_duration' => $calls->where('status', 'answered')->avg('duration'),
            'completion_rate' => $calls->count() > 0 
                ? ($calls->whereIn('status', ['answered', 'voicemail'])->count() / $calls->count()) * 100 
                : 0,
        ];
    }
}
