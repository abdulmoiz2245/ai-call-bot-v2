<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Call;
use App\Models\Campaign;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Display analytics dashboard.
     */
    public function index(Request $request)
    {
        $companyId = $request->input('company_id');
        $timeRange = $request->input('time_range', 'last_7_days');
        
        // Get date range based on selection
        [$startDate, $endDate] = $this->getDateRange($timeRange);
        [$prevStartDate, $prevEndDate] = $this->getPreviousDateRange($timeRange, $startDate, $endDate);

        // Get basic stats
        $stats = $this->getAnalyticsStats($companyId, $startDate, $endDate, $prevStartDate, $prevEndDate);
        
        // Get call status distribution
        $callStatusData = $this->getCallStatusDistribution($companyId, $startDate, $endDate);
        
        // Get top performing agents
        $topAgents = $this->getTopPerformingAgents($companyId, $startDate, $endDate);
        
        // Get campaign performance
        $campaignPerformance = $this->getCampaignPerformance($companyId, $startDate, $endDate);
        
        // Get recent calls
        $recentCalls = $this->getRecentCalls($companyId, 10);
        
        // Get active calls (simulated for demo)
        $activeCalls = $this->getActiveCalls($companyId);

        return Inertia::render('Analytics/Index', [
            'stats' => $stats,
            'callStatusData' => $callStatusData,
            'topAgents' => $topAgents,
            'campaignPerformance' => $campaignPerformance,
            'recentCalls' => $recentCalls,
            'activeCalls' => $activeCalls,
        ]);
    }

    /**
     * Export analytics data.
     */
    public function export(Request $request)
    {
        $companyId = $request->input('company_id');
        $timeRange = $request->input('time_range', 'last_7_days');
        $format = $request->input('format', 'csv');
        
        [$startDate, $endDate] = $this->getDateRange($timeRange);
        
        // Get detailed call data for export
        $calls = Call::query()
            ->when($companyId, fn($q) => $q->whereHas('campaign', fn($q) => $q->where('company_id', $companyId)))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['contact', 'campaign', 'campaign.agent'])
            ->get();

        $filename = "call-analytics-{$timeRange}-" . now()->format('Y-m-d') . ".{$format}";

        if ($format === 'csv') {
            return $this->exportToCsv($calls, $filename);
        }

        return response()->json(['error' => 'Invalid format'], 400);
    }

    /**
     * Get analytics stats with comparison to previous period.
     */
    private function getAnalyticsStats($companyId, $startDate, $endDate, $prevStartDate, $prevEndDate): array
    {
        // Current period stats
        $currentStats = Call::query()
            ->when($companyId, fn($q) => $q->whereHas('campaign', fn($q) => $q->where('company_id', $companyId)))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_calls,
                SUM(CASE WHEN status = "answered" THEN 1 ELSE 0 END) as answered_calls,
                AVG(CASE WHEN status = "answered" THEN duration ELSE NULL END) as avg_duration
            ')
            ->first();

        // Previous period stats
        $previousStats = Call::query()
            ->when($companyId, fn($q) => $q->whereHas('campaign', fn($q) => $q->where('company_id', $companyId)))
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->selectRaw('
                COUNT(*) as total_calls,
                SUM(CASE WHEN status = "answered" THEN 1 ELSE 0 END) as answered_calls,
                AVG(CASE WHEN status = "answered" THEN duration ELSE NULL END) as avg_duration
            ')
            ->first();

        $totalCalls = $currentStats->total_calls ?? 0;
        $answeredCalls = $currentStats->answered_calls ?? 0;
        $avgDuration = $currentStats->avg_duration ?? 0;
        $answerRate = $totalCalls > 0 ? ($answeredCalls / $totalCalls) * 100 : 0;

        $prevTotalCalls = $previousStats->total_calls ?? 0;
        $prevAnsweredCalls = $previousStats->answered_calls ?? 0;
        $prevAvgDuration = $previousStats->avg_duration ?? 0;
        $prevAnswerRate = $prevTotalCalls > 0 ? ($prevAnsweredCalls / $prevTotalCalls) * 100 : 0;

        return [
            'total_calls' => $totalCalls,
            'answered_calls' => $answeredCalls,
            'answer_rate' => $answerRate,
            'avg_duration' => round($avgDuration),
            'total_calls_change' => $this->calculatePercentageChange($prevTotalCalls, $totalCalls),
            'answered_calls_change' => $this->calculatePercentageChange($prevAnsweredCalls, $answeredCalls),
            'answer_rate_change' => $this->calculatePercentageChange($prevAnswerRate, $answerRate),
            'avg_duration_change' => $this->calculatePercentageChange($prevAvgDuration, $avgDuration),
        ];
    }

    /**
     * Get call status distribution.
     */
    private function getCallStatusDistribution($companyId, $startDate, $endDate): array
    {
        $statusData = Call::query()
            ->when($companyId, fn($q) => $q->whereHas('campaign', fn($q) => $q->where('company_id', $companyId)))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $totalCalls = $statusData->sum('count');
        $colors = [
            'answered' => '#10b981',
            'voicemail' => '#3b82f6',
            'busy' => '#f59e0b',
            'no_answer' => '#6b7280',
            'failed' => '#ef4444',
        ];

        return $statusData->map(function ($item) use ($totalCalls, $colors) {
            return [
                'name' => ucfirst(str_replace('_', ' ', $item->status)),
                'count' => $item->count,
                'percentage' => $totalCalls > 0 ? round(($item->count / $totalCalls) * 100, 1) : 0,
                'color' => $colors[$item->status] ?? '#6b7280',
            ];
        })->toArray();
    }

    /**
     * Get top performing agents.
     */
    private function getTopPerformingAgents($companyId, $startDate, $endDate): array
    {
        return Agent::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->withCount(['campaigns'])
            ->with(['campaigns' => function ($query) use ($startDate, $endDate) {
                $query->withCount(['calls' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                }]);
                $query->withCount(['calls as answered_calls_count' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate])
                      ->where('status', 'answered');
                }]);
            }])
            ->get()
            ->map(function ($agent) {
                $totalCalls = $agent->campaigns->sum('calls_count');
                $answeredCalls = $agent->campaigns->sum('answered_calls_count');
                
                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'campaigns_count' => $agent->campaigns_count,
                    'total_calls' => $totalCalls,
                    'success_rate' => $totalCalls > 0 ? ($answeredCalls / $totalCalls) * 100 : 0,
                ];
            })
            ->sortByDesc('success_rate')
            ->take(5)
            ->values()
            ->toArray();
    }

    /**
     * Get campaign performance data.
     */
    private function getCampaignPerformance($companyId, $startDate, $endDate): array
    {
        return Campaign::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->withCount(['calls' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->withCount(['calls as answered_calls_count' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                      ->where('status', 'answered');
            }])
            ->withAvg(['calls as avg_duration' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                      ->where('status', 'answered');
            }], 'duration')
            ->orderByDesc('calls_count')
            ->take(10)
            ->get()
            ->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'total_calls' => $campaign->calls_count,
                    'answer_rate' => $campaign->calls_count > 0 ? 
                        ($campaign->answered_calls_count / $campaign->calls_count) * 100 : 0,
                    'avg_duration' => round($campaign->avg_duration ?? 0),
                ];
            })
            ->toArray();
    }

    /**
     * Get recent calls.
     */
    private function getRecentCalls($companyId, $limit = 10): array
    {
        return Call::query()
            ->when($companyId, fn($q) => $q->whereHas('campaign', fn($q) => $q->where('company_id', $companyId)))
            ->with(['contact', 'campaign'])
            ->orderByDesc('created_at')
            ->take($limit)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'contact_name' => $call->contact->first_name . ' ' . $call->contact->last_name,
                    'campaign_name' => $call->campaign->name,
                    'status' => $call->status,
                    'created_at' => $call->created_at->toISOString(),
                ];
            })
            ->toArray();
    }

    /**
     * Get active calls (simulated for demo).
     */
    private function getActiveCalls($companyId): array
    {
        // In a real implementation, this would check for ongoing calls
        // For demo purposes, we'll return an empty array
        return [];
    }

    /**
     * Get date range based on time range selection.
     */
    private function getDateRange(string $timeRange): array
    {
        $endDate = now();
        
        switch ($timeRange) {
            case 'today':
                $startDate = now()->startOfDay();
                break;
            case 'yesterday':
                $startDate = now()->subDay()->startOfDay();
                $endDate = now()->subDay()->endOfDay();
                break;
            case 'last_7_days':
                $startDate = now()->subDays(6)->startOfDay();
                break;
            case 'last_30_days':
                $startDate = now()->subDays(29)->startOfDay();
                break;
            case 'last_90_days':
                $startDate = now()->subDays(89)->startOfDay();
                break;
            default:
                $startDate = now()->subDays(6)->startOfDay();
        }

        return [$startDate, $endDate];
    }

    /**
     * Get previous date range for comparison.
     */
    private function getPreviousDateRange(string $timeRange, Carbon $startDate, Carbon $endDate): array
    {
        $duration = $endDate->diffInDays($startDate) + 1;
        $prevEndDate = $startDate->copy()->subDay();
        $prevStartDate = $prevEndDate->copy()->subDays($duration - 1);

        return [$prevStartDate, $prevEndDate];
    }

    /**
     * Calculate percentage change.
     */
    private function calculatePercentageChange($previous, $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Export calls data to CSV.
     */
    private function exportToCsv($calls, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($calls) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Call ID',
                'Contact Name',
                'Contact Phone',
                'Campaign',
                'Agent',
                'Status',
                'Duration (seconds)',
                'Cost',
                'Created At',
            ]);

            foreach ($calls as $call) {
                fputcsv($file, [
                    $call->id,
                    $call->contact->first_name . ' ' . $call->contact->last_name,
                    $call->contact->phone,
                    $call->campaign->name,
                    $call->campaign->agent->name ?? 'Unknown',
                    $call->status,
                    $call->duration ?? 0,
                    $call->cost ?? 0,
                    $call->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
