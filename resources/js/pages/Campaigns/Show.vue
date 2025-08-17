<template>

    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="campaign.name" />

        <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
            <div class="flex items-center space-x-4">
                <h1 class="text-3xl font-bold tracking-tight">{{ campaign.name }}</h1>
                <Badge :variant="getStatusVariant(campaign.status)">
                {{ campaign.status }}
                </Badge>
            </div>
            <p class="text-muted-foreground">
                {{ campaign.description || 'No description provided' }}
            </p>
            </div>
            <div class="flex items-center space-x-2">
            <Button @click="router.visit(route('campaigns.edit', campaign.id))" variant="outline">
                <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                class="mr-2 h-4 w-4"
                >
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
                Edit
            </Button>
            
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                <Button variant="outline">
                    Actions
                    <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    class="ml-2 h-4 w-4"
                    >
                    <path d="m6 9 6 6 6-6" />
                    </svg>
                </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                <DropdownMenuItem
                    v-if="campaign.status === 'draft'"
                    @click="activateCampaign"
                >
                    Activate Campaign
                </DropdownMenuItem>
                <DropdownMenuItem
                    v-if="campaign.status === 'active'"
                    @click="pauseCampaign"
                >
                    Pause Campaign
                </DropdownMenuItem>
                <DropdownMenuItem
                    v-if="campaign.status === 'paused'"
                    @click="activateCampaign"
                >
                    Resume Campaign
                </DropdownMenuItem>
                <DropdownMenuItem
                    v-if="['active', 'paused'].includes(campaign.status)"
                    @click="completeCampaign"
                >
                    Complete Campaign
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem @click="router.visit(route('campaigns.index'))">
                    Back to Campaigns
                </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
            </div>
        </div>

        <!-- Campaign Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Calls</CardTitle>
                <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                class="h-4 w-4 text-muted-foreground"
                >
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                </svg>
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ formatNumber(stats.total_calls) }}</div>
                <p class="text-xs text-muted-foreground">
                {{ stats.attempts }} attempts made
                </p>
            </CardContent>
            </Card>

            <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Connected</CardTitle>
                <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                class="h-4 w-4 text-muted-foreground"
                >
                <path d="M20 6 9 17l-5-5" />
                </svg>
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ formatNumber(stats.connected) }}</div>
                <p class="text-xs text-muted-foreground">
                {{ formatPercent(stats.completion_rate) }} success rate
                </p>
            </CardContent>
            </Card>

            <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Cost</CardTitle>
                <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                class="h-4 w-4 text-muted-foreground"
                >
                <line x1="12" y1="1" x2="12" y2="23" />
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                </svg>
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">${{ formatCurrency(stats.total_cost) }}</div>
                <p class="text-xs text-muted-foreground">
                ${{ formatCurrency(stats.total_cost / Math.max(stats.total_calls, 1)) }} per call
                </p>
            </CardContent>
            </Card>

            <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Avg Duration</CardTitle>
                <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                class="h-4 w-4 text-muted-foreground"
                >
                <circle cx="12" cy="12" r="10" />
                <polyline points="12,6 12,12 16,14" />
                </svg>
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ formatDuration(stats.avg_duration) }}</div>
                <p class="text-xs text-muted-foreground">
                Average call length
                </p>
            </CardContent>
            </Card>
        </div>

        <!-- Campaign Details and Recent Calls -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Campaign Configuration -->
            <Card>
            <CardHeader>
                <CardTitle>Configuration</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                <div class="text-sm font-medium">Agent</div>
                <div class="text-sm text-muted-foreground">
                    {{ campaign.agent?.name || 'No agent assigned' }}
                </div>
                </div>

                <div class="space-y-2">
                <div class="text-sm font-medium">Data Source</div>
                <div class="text-sm text-muted-foreground capitalize">
                    {{ campaign.data_source_type }}
                </div>
                </div>

                <div class="space-y-2">
                <div class="text-sm font-medium">Max Retries</div>
                <div class="text-sm text-muted-foreground">
                    {{ campaign.max_retries }}
                </div>
                </div>

                <div class="space-y-2">
                <div class="text-sm font-medium">Max Concurrency</div>
                <div class="text-sm text-muted-foreground">
                    {{ campaign.max_concurrency }}
                </div>
                </div>

                <div class="space-y-2">
                <div class="text-sm font-medium">Call Order</div>
                <div class="text-sm text-muted-foreground capitalize">
                    {{ campaign.call_order }}
                </div>
                </div>

                <div class="space-y-2">
                <div class="text-sm font-medium">Recording</div>
                <div class="text-sm text-muted-foreground">
                    {{ campaign.record_calls ? 'Enabled' : 'Disabled' }}
                </div>
                </div>

                <div v-if="campaign.caller_id" class="space-y-2">
                <div class="text-sm font-medium">Caller ID</div>
                <div class="text-sm text-muted-foreground">
                    {{ campaign.caller_id }}
                </div>
                </div>

                <div class="space-y-2">
                <div class="text-sm font-medium">Created</div>
                <div class="text-sm text-muted-foreground">
                    {{ formatDate(campaign.created_at) }}
                </div>
                </div>
            </CardContent>
            </Card>

            <!-- Recent Calls -->
            <Card class="lg:col-span-2">
            <CardHeader>
                <div class="flex items-center justify-between">
                <CardTitle>Recent Calls</CardTitle>
                <Button @click="router.visit(route('calls.index', { campaign_id: campaign.id }))" variant="outline" size="sm">
                    View All
                </Button>
                </div>
            </CardHeader>
            <CardContent>
                <div class="space-y-4">
                <div
                    v-for="call in campaign.calls"
                    :key="call.id"
                    class="flex items-center justify-between p-4 border rounded-lg"
                >
                    <div class="flex-1">
                    <div class="font-medium">
                        {{ call.contact?.first_name }} {{ call.contact?.last_name }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                        {{ call.contact?.phone }} • {{ formatDate(call.created_at) }}
                    </div>
                    </div>
                    <div class="flex items-center space-x-2">
                    <Badge :variant="getCallStatusVariant(call.status)">
                        {{ call.status }}
                    </Badge>
                    <Button
                        @click="router.visit(route('calls.show', call.id))"
                        variant="ghost"
                        size="sm"
                    >
                        View
                    </Button>
                    </div>
                </div>
                <div v-if="campaign.calls.length === 0" class="text-center py-8">
                    <p class="text-muted-foreground">No calls made yet</p>
                    <Button
                    v-if="campaign.status === 'draft'"
                    @click="activateCampaign"
                    class="mt-2"
                    >
                    Start Campaign
                    </Button>
                </div>
                </div>
            </CardContent>
            </Card>
        </div>
        </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

interface Agent {
  id: number
  name: string
}

interface Contact {
  id: number
  first_name: string
  last_name: string
  phone: string
}

interface Call {
  id: number
  status: string
  created_at: string
  contact?: Contact
}

interface Campaign {
  id: number
  name: string
  description?: string
  status: string
  agent?: Agent
  data_source_type: string
  max_retries: number
  max_concurrency: number
  call_order: string
  record_calls: boolean
  caller_id?: string
  created_at: string
  calls: Call[]
}

interface CampaignStats {
  total_calls: number
  attempts: number
  connected: number
  voicemail: number
  busy: number
  failed: number
  no_answer: number
  total_cost: number
  avg_duration: number
  completion_rate: number
}

interface Props {
  campaign: Campaign
  stats: CampaignStats
}

const props = defineProps<Props>()

function activateCampaign() {
  router.post(route('campaigns.activate', props.campaign.id))
}

function pauseCampaign() {
  router.post(route('campaigns.pause', props.campaign.id))
}

function completeCampaign() {
  router.post(route('campaigns.complete', props.campaign.id))
}

function getStatusVariant(status: string) {
  const variants: Record<string, string> = {
    draft: 'secondary',
    active: 'default',
    paused: 'destructive',
    completed: 'outline'
  }
  return variants[status] || 'secondary'
}

function getCallStatusVariant(status: string) {
  const variants: Record<string, string> = {
    queued: 'secondary',
    ringing: 'default',
    answered: 'default',
    voicemail: 'outline',
    busy: 'destructive',
    failed: 'destructive',
    no_answer: 'secondary'
  }
  return variants[status] || 'secondary'
}

function formatNumber(num: number): string {
  return new Intl.NumberFormat().format(num)
}

function formatPercent(value: number): string {
  return value ? `${value.toFixed(1)}%` : '0%'
}

function formatCurrency(value: number): string {
  return (value || 0).toFixed(2)
}

function formatDuration(seconds: number): string {
  if (!seconds) return '0s'
  
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = seconds % 60
  
  if (minutes > 0) {
    return `${minutes}m ${remainingSeconds}s`
  }
  return `${remainingSeconds}s`
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString()
}
</script>
