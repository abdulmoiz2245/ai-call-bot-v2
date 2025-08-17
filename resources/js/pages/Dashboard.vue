<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>

  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Dashboard" />
    <div class="p-6">
      <!-- Overview Stats -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Campaigns</CardTitle>
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
              <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
            </svg>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.campaigns.total }}</div>
            <p class="text-xs text-muted-foreground">
              {{ stats.campaigns.active }} active
            </p>
          </CardContent>
        </Card>

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
            <div class="text-2xl font-bold">{{ formatNumber(stats.calls.total) }}</div>
            <p class="text-xs text-muted-foreground">
              {{ formatNumber(stats.calls.today) }} today
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Success Rate</CardTitle>
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
            <div class="text-2xl font-bold">{{ stats.success_rate }}%</div>
            <p class="text-xs text-muted-foreground">
              {{ formatNumber(stats.calls.successful) }} successful calls
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Contacts</CardTitle>
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
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
              <circle cx="9" cy="7" r="4" />
              <path d="m22 21-3-3 3-3" />
            </svg>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ formatNumber(stats.contacts.total) }}</div>
            <p class="text-xs text-muted-foreground">
              {{ formatNumber(stats.contacts.new) }} new contacts
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Recent Activity & Quick Actions -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Campaigns -->
        <Card class="lg:col-span-2">
          <CardHeader>
            <div class="flex items-center justify-between">
              <CardTitle>Recent Campaigns</CardTitle>
              <Button @click="router.visit(route('campaigns.index'))" variant="outline" size="sm">
                View All
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div
                v-for="campaign in stats.recent_campaigns"
                :key="campaign.id"
                class="flex items-center justify-between p-4 border rounded-lg"
              >
                <div class="flex-1">
                  <h3 class="font-medium">{{ campaign.name }}</h3>
                  <p class="text-sm text-muted-foreground">
                    Created {{ formatDate(campaign.created_at) }}
                  </p>
                </div>
                <div class="flex items-center space-x-2">
                  <Badge :variant="getStatusVariant(campaign.status)">
                    {{ campaign.status }}
                  </Badge>
                  <Button
                    @click="router.visit(route('campaigns.show', campaign.id))"
                    variant="ghost"
                    size="sm"
                  >
                    View
                  </Button>
                </div>
              </div>
              <div v-if="stats.recent_campaigns.length === 0" class="text-center py-8">
                <p class="text-muted-foreground">No campaigns yet</p>
                <Button @click="router.visit(route('campaigns.create'))" class="mt-2">
                  Create First Campaign
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Quick Actions -->
        <Card>
          <CardHeader>
            <CardTitle>Quick Actions</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <Button @click="router.visit(route('campaigns.create'))" class="w-full justify-start">
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
                <circle cx="12" cy="12" r="10" />
                <path d="M8 12h8" />
                <path d="M12 8v8" />
              </svg>
              Create Campaign
            </Button>
            
            <Button @click="router.visit(route('contacts.create'))" variant="outline" class="w-full justify-start">
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
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
              </svg>
              Add Contact
            </Button>
            
            <Button @click="router.visit(route('agents.create'))" variant="outline" class="w-full justify-start">
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
                <path d="M12 2a3 3 0 0 0-3 3v2a3 3 0 0 0 3 3 3 3 0 0 0 3-3V5a3 3 0 0 0-3-3z" />
                <path d="M19 10v2a7 7 0 0 1-14 0v-2" />
                <line x1="12" y1="19" x2="12" y2="23" />
                <line x1="8" y1="23" x2="16" y2="23" />
              </svg>
              Create Agent
            </Button>

            <Button @click="router.visit(route('calls.analytics'))" variant="outline" class="w-full justify-start">
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
                <path d="M3 3v18h18" />
                <path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3" />
              </svg>
              View Analytics
            </Button>
          </CardContent>
        </Card>
      </div>

      <!-- Call Activity Chart -->
      <Card>
        <CardHeader>
          <CardTitle>Call Activity (Last 7 Days)</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="h-64 flex items-center justify-center">
            <!-- Placeholder for chart - would implement with a charting library -->
            <div class="text-center">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                class="mx-auto h-12 w-12 text-muted-foreground"
              >
                <path d="M3 3v18h18" />
                <path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3" />
              </svg>
              <p class="mt-2 text-sm text-muted-foreground">
                Charts would be rendered here with a library like Chart.js or D3
              </p>
            </div>
          </div>
        </CardContent>
      </Card>
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
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'

interface Campaign {
  id: number
  name: string
  status: string
  created_at: string
}

interface DashboardStats {
  campaigns: {
    total: number
    active: number
  }
  calls: {
    total: number
    today: number
    successful: number
  }
  contacts: {
    total: number
    new: number
  }
  success_rate: number
  recent_campaigns: Campaign[]
}

interface Props {
  stats: DashboardStats
}

defineProps<Props>()

function formatNumber(num: number): string {
  return new Intl.NumberFormat().format(num)
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString()
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
</script>