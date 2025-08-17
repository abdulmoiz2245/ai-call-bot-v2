<template>
  
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Call Analytics" />
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Call Analytics</h1>
                <p class="text-muted-foreground">
                Monitor call performance and gain insights into your campaigns
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <Select v-model="selectedTimeRange" @update:model-value="applyFilters">
                <SelectTrigger class="w-48">
                    <SelectValue placeholder="Select time range" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="today">Today</SelectItem>
                    <SelectItem value="yesterday">Yesterday</SelectItem>
                    <SelectItem value="last_7_days">Last 7 Days</SelectItem>
                    <SelectItem value="last_30_days">Last 30 Days</SelectItem>
                    <SelectItem value="last_90_days">Last 90 Days</SelectItem>
                    <SelectItem value="custom">Custom Range</SelectItem>
                </SelectContent>
                </Select>
                <Button @click="exportData" variant="outline">
                <Download class="w-4 h-4 mr-2" />
                Export
                </Button>
            </div>
            </div>

            <!-- Overview Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white rounded-lg border p-6">
                <div class="flex items-center">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                    <Phone class="w-4 h-4 text-blue-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-muted-foreground">Total Calls</p>
                    <p class="text-2xl font-bold">{{ stats.total_calls?.toLocaleString() }}</p>
                    <p class="text-xs text-muted-foreground">
                    <span :class="getChangeClass(stats.total_calls_change)">
                        {{ stats.total_calls_change > 0 ? '+' : '' }}{{ stats.total_calls_change }}%
                    </span>
                    vs previous period
                    </p>
                </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border p-6">
                <div class="flex items-center">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                    <PhoneCall class="w-4 h-4 text-green-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-muted-foreground">Answered Calls</p>
                    <p class="text-2xl font-bold">{{ stats.answered_calls?.toLocaleString() }}</p>
                    <p class="text-xs text-muted-foreground">
                    <span :class="getChangeClass(stats.answered_calls_change)">
                        {{ stats.answered_calls_change > 0 ? '+' : '' }}{{ stats.answered_calls_change }}%
                    </span>
                    vs previous period
                    </p>
                </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border p-6">
                <div class="flex items-center">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                    <TrendingUp class="w-4 h-4 text-purple-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-muted-foreground">Answer Rate</p>
                    <p class="text-2xl font-bold">{{ Math.round(stats.answer_rate) }}%</p>
                    <p class="text-xs text-muted-foreground">
                    <span :class="getChangeClass(stats.answer_rate_change)">
                        {{ stats.answer_rate_change > 0 ? '+' : '' }}{{ stats.answer_rate_change }}%
                    </span>
                    vs previous period
                    </p>
                </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border p-6">
                <div class="flex items-center">
                <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                    <Clock class="w-4 h-4 text-orange-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-muted-foreground">Avg Duration</p>
                    <p class="text-2xl font-bold">{{ formatDuration(stats.avg_duration) }}</p>
                    <p class="text-xs text-muted-foreground">
                    <span :class="getChangeClass(stats.avg_duration_change)">
                        {{ stats.avg_duration_change > 0 ? '+' : '' }}{{ stats.avg_duration_change }}%
                    </span>
                    vs previous period
                    </p>
                </div>
                </div>
            </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Call Volume Chart -->
            <div class="bg-white rounded-lg border p-6">
                <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Call Volume Over Time</h2>
                <Select v-model="callVolumeMetric">
                    <SelectTrigger class="w-32">
                    <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                    <SelectItem value="total">Total Calls</SelectItem>
                    <SelectItem value="answered">Answered</SelectItem>
                    <SelectItem value="voicemail">Voicemail</SelectItem>
                    </SelectContent>
                </Select>
                </div>
                <div class="h-64 flex items-center justify-center text-muted-foreground">
                <BarChart3 class="w-8 h-8 mr-2" />
                <span>Chart would render here with real data</span>
                </div>
            </div>

            <!-- Call Status Distribution -->
            <div class="bg-white rounded-lg border p-6">
                <h2 class="text-lg font-semibold mb-4">Call Status Distribution</h2>
                <div class="space-y-4">
                <div
                    v-for="status in callStatusData"
                    :key="status.name"
                    class="flex items-center justify-between"
                >
                    <div class="flex items-center space-x-3">
                    <div
                        class="w-4 h-4 rounded-full"
                        :style="{ backgroundColor: status.color }"
                    ></div>
                    <span class="text-sm font-medium">{{ status.name }}</span>
                    </div>
                    <div class="text-right">
                    <p class="text-sm font-bold">{{ status.count.toLocaleString() }}</p>
                    <p class="text-xs text-muted-foreground">{{ status.percentage }}%</p>
                    </div>
                </div>
                </div>
            </div>

            <!-- Top Performing Agents -->
            <div class="bg-white rounded-lg border p-6">
                <h2 class="text-lg font-semibold mb-4">Top Performing Agents</h2>
                <div class="space-y-3">
                <div
                    v-for="agent in topAgents"
                    :key="agent.id"
                    class="flex items-center justify-between p-3 border rounded-lg"
                >
                    <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <Bot class="w-4 h-4 text-blue-600" />
                    </div>
                    <div>
                        <p class="font-medium text-sm">{{ agent.name }}</p>
                        <p class="text-xs text-muted-foreground">{{ agent.campaigns_count }} campaigns</p>
                    </div>
                    </div>
                    <div class="text-right">
                    <p class="text-sm font-bold">{{ Math.round(agent.success_rate) }}%</p>
                    <p class="text-xs text-muted-foreground">{{ agent.total_calls }} calls</p>
                    </div>
                </div>
                </div>
            </div>

            <!-- Call Outcome Trends -->
            <div class="bg-white rounded-lg border p-6">
                <h2 class="text-lg font-semibold mb-4">Call Outcome Trends</h2>
                <div class="h-64 flex items-center justify-center text-muted-foreground">
                <LineChart class="w-8 h-8 mr-2" />
                <span>Trend chart would render here</span>
                </div>
            </div>
            </div>

            <!-- Detailed Analytics Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Campaign Performance -->
            <div class="bg-white rounded-lg border">
                <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold">Campaign Performance</h2>
                </div>
                <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b">
                    <tr>
                        <th class="text-left p-3 font-medium text-muted-foreground text-sm">Campaign</th>
                        <th class="text-right p-3 font-medium text-muted-foreground text-sm">Calls</th>
                        <th class="text-right p-3 font-medium text-muted-foreground text-sm">Answer Rate</th>
                        <th class="text-right p-3 font-medium text-muted-foreground text-sm">Avg Duration</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr
                        v-for="campaign in campaignPerformance"
                        :key="campaign.id"
                        class="border-b hover:bg-gray-50"
                    >
                        <td class="p-3">
                        <div>
                            <p class="font-medium text-sm">{{ campaign.name }}</p>
                            <Badge :variant="getCampaignStatusVariant(campaign.status)" class="text-xs">
                            {{ campaign.status }}
                            </Badge>
                        </div>
                        </td>
                        <td class="p-3 text-right text-sm">{{ campaign.total_calls }}</td>
                        <td class="p-3 text-right text-sm">{{ Math.round(campaign.answer_rate) }}%</td>
                        <td class="p-3 text-right text-sm">{{ formatDuration(campaign.avg_duration) }}</td>
                    </tr>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Recent Calls -->
            <div class="bg-white rounded-lg border">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                <h2 class="text-lg font-semibold">Recent Calls</h2>
                <Link :href="route('calls.index')" as="button">
                    <Button variant="ghost" size="sm">
                    View All
                    <ExternalLink class="w-4 h-4 ml-1" />
                    </Button>
                </Link>
                </div>
                <div class="space-y-1">
                <div
                    v-for="call in recentCalls"
                    :key="call.id"
                    class="flex items-center justify-between p-3 border-b last:border-b-0 hover:bg-gray-50"
                >
                    <div class="flex items-center space-x-3">
                    <div
                        class="w-2 h-2 rounded-full"
                        :class="getCallStatusColor(call.status)"
                    ></div>
                    <div>
                        <p class="font-medium text-sm">{{ call.contact_name }}</p>
                        <p class="text-xs text-muted-foreground">{{ call.campaign_name }}</p>
                    </div>
                    </div>
                    <div class="text-right">
                    <p class="text-sm">{{ formatTime(call.created_at) }}</p>
                    <Badge :variant="getCallStatusVariant(call.status)" class="text-xs">
                        {{ call.status }}
                    </Badge>
                    </div>
                </div>
                </div>
            </div>
            </div>

            <!-- Real-time Monitoring -->
            <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Real-time Call Monitoring</h2>
                <div class="flex items-center space-x-2">
                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                <span class="text-sm text-muted-foreground">Live</span>
                </div>
            </div>
            
            <div v-if="activeCalls.length === 0" class="text-center py-8 text-muted-foreground">
                <Phone class="w-8 h-8 mx-auto mb-2" />
                <p class="text-sm">No active calls at the moment</p>
            </div>

            <div v-else class="space-y-3">
                <div
                v-for="call in activeCalls"
                :key="call.id"
                class="flex items-center justify-between p-4 border rounded-lg bg-green-50"
                >
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    <div>
                    <p class="font-medium text-sm">{{ call.contact_name }}</p>
                    <p class="text-xs text-muted-foreground">{{ call.campaign_name }} • {{ call.agent_name }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-bold text-green-700">{{ formatDuration(call.duration) }}</p>
                    <p class="text-xs text-muted-foreground">{{ call.phone }}</p>
                </div>
                </div>
            </div>
            </div>
        </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  BarChart3,
  Bot,
  Clock,
  Download,
  ExternalLink,
  LineChart,
  Phone,
  PhoneCall,
  TrendingUp,
} from 'lucide-vue-next'

interface AnalyticsStats {
  total_calls: number
  answered_calls: number
  answer_rate: number
  avg_duration: number
  total_calls_change: number
  answered_calls_change: number
  answer_rate_change: number
  avg_duration_change: number
}

interface CallStatusData {
  name: string
  count: number
  percentage: number
  color: string
}

interface TopAgent {
  id: number
  name: string
  campaigns_count: number
  success_rate: number
  total_calls: number
}

interface CampaignPerformance {
  id: number
  name: string
  status: string
  total_calls: number
  answer_rate: number
  avg_duration: number
}

interface RecentCall {
  id: number
  contact_name: string
  campaign_name: string
  status: string
  created_at: string
}

interface ActiveCall {
  id: number
  contact_name: string
  campaign_name: string
  agent_name: string
  phone: string
  duration: number
}

const props = defineProps<{
  stats: AnalyticsStats
  callStatusData: CallStatusData[]
  topAgents: TopAgent[]
  campaignPerformance: CampaignPerformance[]
  recentCalls: RecentCall[]
  activeCalls: ActiveCall[]
}>()

// Reactive state
const selectedTimeRange = ref('last_7_days')
const callVolumeMetric = ref('total')

// Methods
const applyFilters = () => {
  router.get(route('analytics.index'), {
    time_range: selectedTimeRange.value,
  }, {
    preserveState: true,
    replace: true,
  })
}

const exportData = () => {
  // This would trigger an export of the analytics data
  router.get(route('analytics.export'), {
    time_range: selectedTimeRange.value,
    format: 'csv',
  })
}

const getChangeClass = (change: number): string => {
  if (change > 0) return 'text-green-600'
  if (change < 0) return 'text-red-600'
  return 'text-muted-foreground'
}

const getCampaignStatusVariant = (status: string) => {
  switch (status) {
    case 'active': return 'default'
    case 'paused': return 'secondary'
    case 'completed': return 'outline'
    case 'draft': return 'secondary'
    default: return 'secondary'
  }
}

const getCallStatusVariant = (status: string) => {
  switch (status) {
    case 'answered': return 'default'
    case 'voicemail': return 'secondary'
    case 'busy': return 'secondary'
    case 'no_answer': return 'outline'
    case 'failed': return 'destructive'
    default: return 'secondary'
  }
}

const getCallStatusColor = (status: string): string => {
  switch (status) {
    case 'answered': return 'bg-green-500'
    case 'voicemail': return 'bg-blue-500'
    case 'busy': return 'bg-yellow-500'
    case 'no_answer': return 'bg-gray-500'
    case 'failed': return 'bg-red-500'
    default: return 'bg-gray-500'
  }
}

const formatDuration = (seconds: number): string => {
  if (!seconds) return '0s'
  
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = seconds % 60
  
  if (minutes > 0) {
    return `${minutes}m ${remainingSeconds}s`
  }
  return `${remainingSeconds}s`
}

const formatTime = (dateString: string): string => {
  return new Date(dateString).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>
