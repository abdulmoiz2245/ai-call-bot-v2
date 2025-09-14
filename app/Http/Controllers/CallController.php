<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Campaign;
use App\Services\CallingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Log;

class CallController extends Controller
{
    public function __construct(
        private CallingService $callingService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $companyId = $request->input('company_id');
        
        $calls = Call::query()
            ->whereHas('campaign', fn($q) => $q->where('company_id', $companyId))
            ->with(['campaign', 'contact'])
            ->when($request->input('status'), fn($q, $status) => $q->where('status', $status))
            ->when($request->input('campaign_id'), fn($q, $campaignId) => $q->where('campaign_id', $campaignId))
            ->when($request->input('search'), fn($q, $search) => 
                $q->whereHas('contact', fn($q) => 
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                )
            )
            ->when($request->input('date_from'), fn($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->input('date_to'), fn($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->orderBy($request->input('sort', 'created_at'), $request->input('direction', 'desc'))
            ->paginate($request->input('per_page', 25))
            ->withQueryString();

        $campaigns = Campaign::where('company_id', $companyId)
            ->select('id', 'name')
            ->get();

        return Inertia::render('Calls/Index', [
            'calls' => $calls,
            'campaigns' => $campaigns,
            'filters' => $request->only(['status', 'search', 'sort', 'direction', 'campaign_id', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Call $call)
    {
        $call->load(['campaign', 'contact', 'events']);

        return Inertia::render('Calls/Show', [
            'call' => $call,
        ]);
    }

    /**
     * Initiate a manual call.
     */
    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'contact_id' => 'required|exists:contacts,id',
        ]);

        $campaign = Campaign::findOrFail($validated['campaign_id']);
        $contact = \App\Models\Contact::findOrFail($validated['contact_id']);

        try {
            $call = $this->callingService->initiateCall($campaign, $contact);
            
            return response()->json([
                'success' => true,
                'call_id' => $call->id,
                'message' => 'Call initiated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Handle webhook from call gateway.
     */
    public function webhook(Request $request)
    {
        $data = $request->all();

        // Just log for now
        Log::info('Livekit Webhook Received:', $data);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Get call analytics for a specific period.
     */
    public function analytics(Request $request)
    {
        $companyId = $request->input('company_id');
        $dateFrom = $request->input('date_from', now()->subDays(30));
        $dateTo = $request->input('date_to', now());

        $calls = Call::whereHas('campaign', fn($q) => $q->where('company_id', $companyId))
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        $analytics = [
            'total_calls' => $calls->count(),
            'answered_calls' => $calls->where('status', 'answered')->count(),
            'voicemail_calls' => $calls->where('status', 'voicemail')->count(),
            'failed_calls' => $calls->where('status', 'failed')->count(),
            'busy_calls' => $calls->where('status', 'busy')->count(),
            'no_answer_calls' => $calls->where('status', 'no_answer')->count(),
            'total_duration' => $calls->sum('duration'),
            'total_cost' => $calls->sum('cost'),
            'avg_duration' => $calls->where('status', 'answered')->avg('duration'),
            'success_rate' => $calls->count() > 0 
                ? ($calls->whereIn('status', ['answered', 'voicemail'])->count() / $calls->count()) * 100 
                : 0,
        ];

        // Daily breakdown
        $dailyStats = $calls->selectRaw('DATE(created_at) as date, COUNT(*) as count, 
                                        SUM(CASE WHEN status = "answered" THEN 1 ELSE 0 END) as answered,
                                        SUM(duration) as total_duration,
                                        SUM(cost) as total_cost')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        return Inertia::render('Calls/Analytics', [
            'analytics' => $analytics,
            'dailyStats' => $dailyStats,
            'filters' => $request->only(['date_from', 'date_to']),
        ]);
    }

    /**
     * Download call recording.
     */
    public function downloadRecording(Call $call)
    {
        if (!$call->recording_url) {
            return back()->with('error', 'No recording available for this call.');
        }

        // This would download the file from the storage or redirect to the recording URL
        return redirect($call->recording_url);
    }

    /**
     * Retry a failed call.
     */
    public function retry(Call $call)
    {
        if ($call->status !== 'failed') {
            return back()->with('error', 'Only failed calls can be retried.');
        }

        if ($call->retry_count >= $call->campaign->max_retries) {
            return back()->with('error', 'Maximum retry attempts reached.');
        }

        try {
            $newCall = $this->callingService->retryCall($call);
            
            return redirect()->route('calls.show', $newCall)
                ->with('success', 'Call retry initiated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Bulk actions on calls.
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:retry,export,delete',
            'call_ids' => 'required|array',
            'call_ids.*' => 'exists:calls,id',
        ]);

        $calls = Call::whereIn('id', $validated['call_ids'])->get();

        switch ($validated['action']) {
            case 'retry':
                $retried = 0;
                foreach ($calls as $call) {
                    if ($call->status === 'failed' && $call->retry_count < $call->campaign->max_retries) {
                        $this->callingService->retryCall($call);
                        $retried++;
                    }
                }
                return back()->with('success', "{$retried} calls queued for retry.");

            case 'export':
                // This would generate a CSV export
                return back()->with('success', 'Call export initiated.');

            case 'delete':
                $calls->each->delete();
                return back()->with('success', 'Selected calls deleted successfully.');
        }
    }
}
