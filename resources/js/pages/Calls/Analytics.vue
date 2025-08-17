<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Call Analytics" />
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Call Analytics</h1>
                <p class="text-muted-foreground">
                Detailed analytics for call performance and statistics
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <Button @click="exportAnalytics" variant="outline">
                <Download class="mr-2 h-4 w-4" />
                Export
                </Button>
                <Button @click="refreshAnalytics">
                <RefreshCw class="mr-2 h-4 w-4" />
                Refresh
                </Button>
            </div>
            </div>

            <!-- Date Range Filter -->
            <Card>
            <CardHeader>
                <CardTitle>Date Range</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex items-center space-x-4">
                <div class="grid grid-cols-2 gap-4 flex-1">
                    <div>
                    <Label for="start_date">Start Date</Label>
                    <Input
                        id="start_date"
                        type="date"
                        v-model="filters.start_date"
                        @change="updateAnalytics"
                    />
                    </div>
                    <div>
                    <Label for="end_date">End Date</Label>
                    <Input
                        id="end_date"
                        type="date"
                        v-model="filters.end_date"
                        @change="updateAnalytics"
                    />
                    </div>
                </div>
                <div class="flex space-x-2">
                    <Button @click="setDateRange('today')" variant="outline" size="sm">
                    Today
                    </Button>
                    <Button @click="setDateRange('week')" variant="outline" size="sm">
                    This Week
                    </Button>
                    <Button @click="setDateRange('month')" variant="outline" size="sm">
                    This Month
                    </Button>
                </div>
                </div>
            </CardContent>
            </Card>

            <!-- Overview Stats -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Calls</CardTitle>
                <Phone class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                <div class="text-2xl font-bold">{{ analytics.total_calls?.toLocaleString() || 0 }}</div>
                <p class="text-xs text-muted-foreground">
                    <span :class="getTrendClass(analytics.calls_change)">
                    {{ analytics.calls_change > 0 ? '+' : '' }}{{ analytics.calls_change }}%
                    </span>
                    from last period
                </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Success Rate</CardTitle>
                <TrendingUp class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                <div class="text-2xl font-bold">{{ analytics.success_rate?.toFixed(1) || 0 }}%</div>
                <p class="text-xs text-muted-foreground">
                    <span :class="getTrendClass(analytics.success_change)">
                    {{ analytics.success_change > 0 ? '+' : '' }}{{ analytics.success_change?.toFixed(1) }}%
                    </span>
                    from last period
                </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Avg Duration</CardTitle>
                <Clock class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                <div class="text-2xl font-bold">{{ formatDuration(analytics.avg_duration) }}</div>
                <p class="text-xs text-muted-foreground">
                    <span :class="getTrendClass(analytics.duration_change)">
                    {{ analytics.duration_change > 0 ? '+' : '' }}{{ analytics.duration_change?.toFixed(1) }}%
                    </span>
                    from last period
                </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Answer Rate</CardTitle>
                <PhoneCall class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                <div class="text-2xl font-bold">{{ analytics.answer_rate?.toFixed(1) || 0 }}%</div>
                <p class="text-xs text-muted-foreground">
                    <span :class="getTrendClass(analytics.answer_change)">
                    {{ analytics.answer_change > 0 ? '+' : '' }}{{ analytics.answer_change?.toFixed(1) }}%
                    </span>
                    from last period
                </p>
                </CardContent>
            </Card>
            </div>

            <!-- Call Status Distribution -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                <CardTitle>Call Status Distribution</CardTitle>
                </CardHeader>
                <CardContent>
                <div class="space-y-4">
                    <div v-for="status in analytics.status_distribution" :key="status.status" class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <Badge :variant="getStatusVariant(status.status)">
                        {{ status.status }}
                        </Badge>
                        <span class="text-sm text-muted-foreground">{{ status.count }} calls</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-20 h-2 bg-muted rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full"
                            :class="getStatusColor(status.status)"
                            :style="{ width: `${status.percentage}%` }"
                        ></div>
                        </div>
                        <span class="text-sm font-medium w-12">{{ status.percentage.toFixed(1) }}%</span>
                    </div>
                    </div>
                </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                <CardTitle>Top Performing Campaigns</CardTitle>
                </CardHeader>
                <CardContent>
                <div class="space-y-4">
                    <div v-for="campaign in analytics.top_campaigns" :key="campaign.id" class="flex items-center justify-between">
                    <div>
                        <div class="font-medium">{{ campaign.name }}</div>
                        <div class="text-sm text-muted-foreground">{{ campaign.calls_count }} calls</div>
                    </div>
                    <div class="text-right">
                        <div class="font-medium">{{ campaign.success_rate.toFixed(1) }}%</div>
                        <div class="text-sm text-muted-foreground">success rate</div>
                    </div>
                    </div>
                </div>
                </CardContent>
            </Card>
            </div>

            <!-- Hourly Distribution -->
            <Card>
            <CardHeader>
                <CardTitle>Call Volume by Hour</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid grid-cols-24 gap-1 h-20">
                <div
                    v-for="hour in analytics.hourly_distribution"
                    :key="hour.hour"
                    class="bg-muted rounded-sm flex items-end justify-center relative group"
                    :style="{ height: '100%' }"
                >
                    <div
                    class="bg-primary rounded-sm w-full transition-all duration-200"
                    :style="{ height: `${hour.percentage}%` }"
                    ></div>
                    <div class="absolute -bottom-6 text-xs text-muted-foreground">
                    {{ hour.hour }}:00
                    </div>
                    <div class="absolute -top-8 bg-popover border rounded px-2 py-1 text-xs opacity-0 group-hover:opacity-100 transition-opacity z-10">
                    {{ hour.calls }} calls
                    </div>
                </div>
                </div>
            </CardContent>
            </Card>

            <!-- Agent Performance -->
            <Card v-if="analytics.agent_performance?.length">
            <CardHeader>
                <CardTitle>Agent Performance</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                    <tr class="border-b">
                        <th class="text-left p-2">Agent</th>
                        <th class="text-right p-2">Calls</th>
                        <th class="text-right p-2">Success Rate</th>
                        <th class="text-right p-2">Avg Duration</th>
                        <th class="text-right p-2">Answer Rate</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="agent in analytics.agent_performance" :key="agent.id" class="border-b">
                        <td class="p-2">
                        <div class="font-medium">{{ agent.name }}</div>
                        <div class="text-sm text-muted-foreground">{{ agent.voice_id }}</div>
                        </td>
                        <td class="p-2 text-right">{{ agent.calls_count }}</td>
                        <td class="p-2 text-right">
                        <Badge :variant="agent.success_rate >= 50 ? 'default' : 'secondary'">
                            {{ agent.success_rate.toFixed(1) }}%
                        </Badge>
                        </td>
                        <td class="p-2 text-right">{{ formatDuration(agent.avg_duration) }}</td>
                        <td class="p-2 text-right">{{ agent.answer_rate.toFixed(1) }}%</td>
                    </tr>
                    </tbody>
                </table>
                </div>
            </CardContent>
            </Card>
        </div>
    </AppLayout>    
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { 
  Phone, 
  PhoneCall, 
  TrendingUp, 
  Clock, 
  Download, 
  RefreshCw 
} from 'lucide-vue-next'

interface CallAnalytics {
  total_calls: number
  success_rate: number
  answer_rate: number
  avg_duration: number
  calls_change: number
  success_change: number
  answer_change: number
  duration_change: number
  status_distribution: Array<{
    status: string
    count: number
    percentage: number
  }>
  hourly_distribution: Array<{
    hour: number
    calls: number
    percentage: number
  }>
  top_campaigns: Array<{
    id: number
    name: string
    calls_count: number
    success_rate: number
  }>
  agent_performance: Array<{
    id: number
    name: string
    voice_id: string
    calls_count: number
    success_rate: number
    answer_rate: number
    avg_duration: number
  }>
}

interface Props {
  analytics: CallAnalytics
}

const props = defineProps<Props>()

const filters = ref({
  start_date: new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0]
})

const setDateRange = (range: string) => {
  const today = new Date()
  const start = new Date()
  
  switch (range) {
    case 'today':
      filters.value.start_date = today.toISOString().split('T')[0]
      filters.value.end_date = today.toISOString().split('T')[0]
      break
    case 'week':
      start.setDate(today.getDate() - 7)
      filters.value.start_date = start.toISOString().split('T')[0]
      filters.value.end_date = today.toISOString().split('T')[0]
      break
    case 'month':
      start.setDate(today.getDate() - 30)
      filters.value.start_date = start.toISOString().split('T')[0]
      filters.value.end_date = today.toISOString().split('T')[0]
      break
  }
  
  updateAnalytics()
}

const updateAnalytics = () => {
  router.get(route('calls.analytics'), filters.value, {
    preserveState: true,
    preserveScroll: true
  })
}

const refreshAnalytics = () => {
  router.reload({ only: ['analytics'] })
}

const exportAnalytics = () => {
  window.location.href = route('analytics.export', filters.value)
}

const formatDuration = (seconds: number): string => {
  if (!seconds) return '0s'
  
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = Math.floor(seconds % 60)
  
  if (hours > 0) {
    return `${hours}h ${minutes}m ${secs}s`
  } else if (minutes > 0) {
    return `${minutes}m ${secs}s`
  } else {
    return `${secs}s`
  }
}

const getTrendClass = (change: number): string => {
  if (change > 0) return 'text-green-600'
  if (change < 0) return 'text-red-600'
  return 'text-muted-foreground'
}

const getStatusVariant = (status: string): string => {
  switch (status) {
    case 'completed':
      return 'default'
    case 'answered':
      return 'secondary'
    case 'failed':
      return 'destructive'
    case 'no_answer':
      return 'outline'
    case 'busy':
      return 'outline'
    default:
      return 'secondary'
  }
}

const getStatusColor = (status: string): string => {
  switch (status) {
    case 'completed':
      return 'bg-green-500'
    case 'answered':
      return 'bg-blue-500'
    case 'failed':
      return 'bg-red-500'
    case 'no_answer':
      return 'bg-yellow-500'
    case 'busy':
      return 'bg-orange-500'
    default:
      return 'bg-gray-500'
  }
}
</script>
