<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Call;
use App\Models\Agent;
use App\Models\Order;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load(['company.companyType']);
        $companyId = $user->company_id;

        if ($user->isSuperAdmin()) {
            // Super admin sees system-wide stats
            $stats = $this->getSystemStats();
            $recentCampaigns = [];
            $recentCalls = [];
            $recentOrders = [];
        } else {
            // Company users see company-specific stats
            $stats = $this->getCompanyStats($companyId);
            
            // Get recent data for the dashboard
            $recentCampaigns = Campaign::where('company_id', $companyId)
                ->with(['agent'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $recentCalls = Call::where('company_id', $companyId)
                ->with(['contact', 'campaign'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Get recent orders if e-commerce company
            $recentOrders = [];
            if ($user->company && $user->company->companyType && $user->company->companyType->slug === 'ecommerce') {
                $recentOrders = Order::where('company_id', $companyId)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            }
        }

        return Inertia::render('Dashboard/Index', [
            'user' => $user,
            'stats' => $stats,
            'recentCampaigns' => $recentCampaigns,
            'recentCalls' => $recentCalls,
            'recentOrders' => $recentOrders,
        ]);
    }

    private function getSystemStats(): array
    {
        return [
            'total_companies' => Company::count(),
            'active_companies' => Company::where('is_active', true)->count(),
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_campaigns' => Campaign::count(),
            'active_campaigns' => Campaign::where('status', 'active')->count(),
            'total_calls' => Call::count(),
            'calls_today' => Call::whereDate('created_at', Carbon::today())->count(),
        ];
    }

    private function getCompanyStats(int $companyId): array
    {
        $campaignsQuery = Campaign::where('company_id', $companyId);
        $callsQuery = Call::where('company_id', $companyId);
        $contactsQuery = Contact::where('company_id', $companyId);

        // Calculate success rate
        $totalCalls = $callsQuery->count();
        $successfulCalls = $callsQuery->whereIn('status', ['answered', 'completed'])->count();
        $successRate = $totalCalls > 0 ? round(($successfulCalls / $totalCalls) * 100, 1) : 0;

        return [
            'active_campaigns' => $campaignsQuery->where('status', 'active')->count(),
            'draft_campaigns' => $campaignsQuery->where('status', 'draft')->count(),
            'completed_campaigns' => $campaignsQuery->where('status', 'completed')->count(),
            
            'total_contacts' => $contactsQuery->count(),
            'new_contacts' => $contactsQuery->where('created_at', '>=', Carbon::now()->subWeek())->count(),
            'dnc_contacts' => $contactsQuery->where('is_dnc', true)->count(),
            
            'total_calls' => $totalCalls,
            'calls_today' => $callsQuery->whereDate('created_at', Carbon::today())->count(),
            'successful_calls' => $successfulCalls,
            'failed_calls' => $callsQuery->whereIn('status', ['failed', 'busy', 'no_answer'])->count(),
            'success_rate' => $successRate,
            
            'total_agents' => Agent::where('company_id', $companyId)->count(),
            'active_agents' => Agent::where('company_id', $companyId)->where('is_active', true)->count(),
            
            'total_cost' => $callsQuery->sum('cost'),
            'cost_today' => $callsQuery->whereDate('created_at', Carbon::today())->sum('cost'),
            
            'call_stats_last_7_days' => $this->getCallStatsLast7Days($companyId),
            'campaign_performance' => $this->getCampaignPerformance($companyId),
        ];
    }

    private function getCallStatsLast7Days(int $companyId): array
    {
        $stats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $calls = Call::where('company_id', $companyId)
                ->whereDate('created_at', $date)
                ->get();
                
            $stats[] = [
                'date' => $date->format('Y-m-d'),
                'total' => $calls->count(),
                'answered' => $calls->where('status', 'answered')->count(),
                'voicemail' => $calls->where('status', 'voicemail')->count(),
                'busy' => $calls->where('status', 'busy')->count(),
                'failed' => $calls->where('status', 'failed')->count(),
                'cost' => $calls->sum('cost'),
            ];
        }
        
        return $stats;
    }

    private function getCampaignPerformance(int $companyId): array
    {
        return Campaign::where('company_id', $companyId)
            ->with(['calls'])
            ->get()
            ->map(function ($campaign) {
                $calls = $campaign->calls;
                $totalCalls = $calls->count();
                
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'total_calls' => $totalCalls,
                    'successful_calls' => $calls->where('status', 'answered')->count(),
                    'completion_rate' => $totalCalls > 0 
                        ? round(($calls->whereIn('status', ['answered', 'voicemail'])->count() / $totalCalls) * 100, 2)
                        : 0,
                    'total_cost' => $calls->sum('cost'),
                    'avg_duration' => $calls->where('status', 'answered')->avg('duration'),
                ];
            })
            ->sortByDesc('completion_rate')
            ->values()
            ->take(10)
            ->toArray();
    }
}
