<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Agent;
use App\Services\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CampaignController extends Controller
{
    public function __construct(
        private CampaignService $campaignService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $companyId = $request->input('company_id');
        
        $campaigns = Campaign::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->with(['agent', 'creator', 'company'])
            ->when($request->input('status'), fn($q, $status) => $q->where('status', $status))
            ->when($request->input('search'), fn($q, $search) => 
                $q->where(fn($q) => 
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                )
            )
            ->orderBy($request->input('sort', 'created_at'), $request->input('direction', 'desc'))
            ->paginate($request->input('per_page', 25))
            ->withQueryString();

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
            'filters' => $request->only(['status', 'search', 'sort', 'direction']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $companyId = $request->input('company_id');
        
        $agents = Agent::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        return Inertia::render('Campaigns/Create', [
            'agents' => $agents,
            'companyId' => $companyId,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'agent_id' => 'nullable|exists:agents,id',
            'data_source_type' => 'required|in:contacts,leads,orders',
            'max_retries' => 'integer|min:0|max:10',
            'max_concurrency' => 'integer|min:1|max:20',
            'call_order' => 'in:sequential,random,priority',
            'record_calls' => 'boolean',
            'caller_id' => 'nullable|string',
            'schedule_settings' => 'nullable|array',
            'filter_criteria' => 'nullable|array',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $campaign = $this->campaignService->createCampaign(
            $validated,
            $request->input('company_id'),
            Auth::id()
        );

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Campaign $campaign)
    {
        $campaign->load(['agent', 'creator', 'company', 'calls.contact']);
        
        $stats = $this->campaignService->getCampaignStats($campaign);

        return Inertia::render('Campaigns/Show', [
            'campaign' => $campaign,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Campaign $campaign, Request $request)
    {
        $companyId = $request->input('company_id');
        
        $agents = Agent::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        return Inertia::render('Campaigns/Edit', [
            'campaign' => $campaign,
            'agents' => $agents,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'agent_id' => 'nullable|exists:agents,id',
            'data_source_type' => 'required|in:contacts,leads,orders',
            'max_retries' => 'integer|min:0|max:10',
            'max_concurrency' => 'integer|min:1|max:20',
            'call_order' => 'in:sequential,random,priority',
            'record_calls' => 'boolean',
            'caller_id' => 'nullable|string',
            'schedule_settings' => 'nullable|array',
            'filter_criteria' => 'nullable|array',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $campaign = $this->campaignService->updateCampaign(
            $campaign,
            $validated,
            Auth::id()
        );

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Campaign $campaign)
    {
        // Only allow deletion of draft campaigns
        if (!$campaign->isDraft()) {
            return back()->with('error', 'Only draft campaigns can be deleted.');
        }

        $campaign->delete();

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    /**
     * Activate the campaign.
     */
    public function activate(Campaign $campaign)
    {
        try {
            $this->campaignService->activateCampaign($campaign, Auth::id());
            
            return back()->with('success', 'Campaign activated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pause the campaign.
     */
    public function pause(Campaign $campaign)
    {
        try {
            $this->campaignService->pauseCampaign($campaign, Auth::id());
            
            return back()->with('success', 'Campaign paused successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Complete the campaign.
     */
    public function complete(Campaign $campaign)
    {
        try {
            $this->campaignService->completeCampaign($campaign, Auth::id());
            
            return back()->with('success', 'Campaign completed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
