<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Dashboard" />
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
                <p class="text-muted-foreground">
                Welcome back, {{ user?.name }}! Here's an overview of your Call-Bot platform.
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <Button @click="refreshData" variant="outline">
                <RefreshCw class="mr-2 h-4 w-4" />
                Refresh
                </Button>
            </div>
            </div>

            <!-- Company Info Card (for company users) -->
            <Card v-if="user?.company" class="bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200">
            <CardContent class="p-6">
                <div class="flex items-center space-x-4">
                <div class="p-3 bg-blue-500 rounded-lg">
                    <Building2 class="h-6 w-6 text-white" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-blue-900">{{ user.company.name }}</h3>
                    <p class="text-blue-700">{{ user.company.company_type?.name }} Company</p>
                    <p class="text-sm text-blue-600">{{ user.role.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}</p>
                </div>
                </div>
            </CardContent>
            </Card>

            <!-- Super Admin Overview -->
            <div v-if="user?.role === 'PARENT_SUPER_ADMIN'" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Companies</CardTitle>
                <Building2 class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                <div class="text-2xl font-bold">{{ stats?.total_companies || 0 }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ stats?.active_companies || 0 }} active
                </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Users</CardTitle>
                <Users class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                <div class="text-2xl font-bold">{{ stats?.total_users || 0 }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ stats?.active_users || 0 }} active
                </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Campaigns</CardTitle>
                <Megaphone class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                <div class="text-2xl font-bold">{{ stats?.total_campaigns || 0 }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ stats?.active_campaigns || 0 }} active
                </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Calls</CardTitle>
                <Phone class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                <div class="text-2xl font-bold">{{ stats?.total_calls || 0 }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ stats?.calls_today || 0 }} today
                </p>
                </CardContent>
            </Card>
            </div>

            <!-- Company Overview -->
            <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Active Campaigns</CardTitle>
                <Megaphone class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                <div class="text-2xl font-bold">{{ stats?.active_campaigns || 0 }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ stats?.draft_campaigns || 0 }} in draft
                </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Contacts</CardTitle>
                <Users class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                <div class="text-2xl font-bold">{{ stats?.total_contacts || 0 }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ stats?.new_contacts || 0 }} new this week
                </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Calls Made</CardTitle>
                <Phone class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                <div class="text-2xl font-bold">{{ stats?.total_calls || 0 }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ stats?.success_rate || 0 }}% success rate
                </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">AI Agents</CardTitle>
                <Bot class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                <div class="text-2xl font-bold">{{ stats?.total_agents || 0 }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ stats?.active_agents || 0 }} active
                </p>
                </CardContent>
            </Card>
            </div>

            <!-- E-commerce Orders Section (only for e-commerce companies) -->
            <Card v-if="user?.company?.company_type?.slug === 'ecommerce'">
            <CardHeader>
                <CardTitle class="flex items-center">
                <ShoppingCart class="mr-2 h-5 w-5" />
                Recent Orders
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="recentOrders?.length" class="space-y-4">
                <div v-for="order in recentOrders" :key="order.id" class="flex items-center justify-between p-3 border rounded-lg">
                    <div>
                    <div class="font-medium">{{ order.order_number }}</div>
                    <div class="text-sm text-muted-foreground">{{ order.customer_name }}</div>
                    </div>
                    <div class="text-right">
                    <div class="font-medium">${{ order.total_amount }}</div>
                    <Badge :variant="getOrderStatusVariant(order.status)">{{ order.status }}</Badge>
                    </div>
                </div>
                <div class="pt-4 border-t">
                    <Button @click="$inertia.visit(route('orders.index'))" variant="outline" class="w-full">
                    View All Orders
                    </Button>
                </div>
                </div>
                <div v-else class="text-center py-8 text-muted-foreground">
                <ShoppingCart class="mx-auto h-12 w-12 mb-4 opacity-50" />
                <p>No orders found</p>
                <Button @click="$inertia.visit(route('orders.create'))" class="mt-4">
                    Create First Order
                </Button>
                </div>
            </CardContent>
            </Card>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Recent Campaigns -->
            <Card>
                <CardHeader>
                <CardTitle class="flex items-center">
                    <Megaphone class="mr-2 h-5 w-5" />
                    Recent Campaigns
                </CardTitle>
                </CardHeader>
                <CardContent>
                <div v-if="recentCampaigns?.length" class="space-y-4">
                    <div v-for="campaign in recentCampaigns" :key="campaign.id" class="flex items-center justify-between p-3 border rounded-lg">
                    <div>
                        <div class="font-medium">{{ campaign.name }}</div>
                        <div class="text-sm text-muted-foreground">{{ campaign.agent?.name }}</div>
                    </div>
                    <Badge :variant="getCampaignStatusVariant(campaign.status)">{{ campaign.status }}</Badge>
                    </div>
                    <div class="pt-4 border-t">
                    <Button @click="$inertia.visit(route('campaigns.index'))" variant="outline" class="w-full">
                        View All Campaigns
                    </Button>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-muted-foreground">
                    <Megaphone class="mx-auto h-12 w-12 mb-4 opacity-50" />
                    <p>No campaigns found</p>
                    <Button @click="$inertia.visit(route('campaigns.create'))" class="mt-4">
                    Create First Campaign
                    </Button>
                </div>
                </CardContent>
            </Card>

            <!-- Recent Calls -->
            <Card>
                <CardHeader>
                <CardTitle class="flex items-center">
                    <Phone class="mr-2 h-5 w-5" />
                    Recent Calls
                </CardTitle>
                </CardHeader>
                <CardContent>
                <div v-if="recentCalls?.length" class="space-y-4">
                    <div v-for="call in recentCalls" :key="call.id" class="flex items-center justify-between p-3 border rounded-lg">
                    <div>
                        <div class="font-medium">{{ call.contact?.first_name }} {{ call.contact?.last_name }}</div>
                        <div class="text-sm text-muted-foreground">{{ call.campaign?.name }}</div>
                    </div>
                    <div class="text-right">
                        <Badge :variant="getCallStatusVariant(call.status)">{{ call.status }}</Badge>
                        <div class="text-xs text-muted-foreground mt-1">{{ formatDate(call.created_at) }}</div>
                    </div>
                    </div>
                    <div class="pt-4 border-t">
                    <Button @click="$inertia.visit(route('calls.index'))" variant="outline" class="w-full">
                        View All Calls
                    </Button>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-muted-foreground">
                    <Phone class="mx-auto h-12 w-12 mb-4 opacity-50" />
                    <p>No calls found</p>
                    <Button @click="$inertia.visit(route('calls.initiate'))" class="mt-4">
                    Initiate First Call
                    </Button>
                </div>
                </CardContent>
            </Card>
            </div>

            <!-- Quick Actions -->
            <Card>
            <CardHeader>
                <CardTitle>Quick Actions</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Button @click="$inertia.visit(route('campaigns.create'))" class="h-24 flex-col space-y-2">
                    <Megaphone class="h-6 w-6" />
                    <span>New Campaign</span>
                </Button>
                <Button @click="$inertia.visit(route('contacts.create'))" variant="outline" class="h-24 flex-col space-y-2">
                    <UserPlus class="h-6 w-6" />
                    <span>Add Contact</span>
                </Button>
                <Button @click="$inertia.visit(route('agents.create'))" variant="outline" class="h-24 flex-col space-y-2">
                    <Bot class="h-6 w-6" />
                    <span>Create Agent</span>
                </Button>
                <Button @click="$inertia.visit(route('analytics.index'))" variant="outline" class="h-24 flex-col space-y-2">
                    <BarChart3 class="h-6 w-6" />
                    <span>View Analytics</span>
                </Button>
                </div>
            </CardContent>
            </Card>
        </div>
    </AppLayout>    
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { type BreadcrumbItem } from '@/types';

import { 
  Building2, 
  Users, 
  Megaphone, 
  Phone, 
  Bot, 
  ShoppingCart,
  UserPlus,
  BarChart3,
  RefreshCw
} from 'lucide-vue-next'

interface Props {
  user: any
  stats: any
  recentCampaigns?: any[]
  recentCalls?: any[]
  recentOrders?: any[]
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const props = defineProps<Props>()

const refreshData = () => {
  router.reload({ only: ['stats', 'recentCampaigns', 'recentCalls', 'recentOrders'] })
}

const getCampaignStatusVariant = (status: string): string => {
  switch (status) {
    case 'active':
      return 'default'
    case 'completed':
      return 'secondary'
    case 'paused':
      return 'outline'
    case 'draft':
      return 'secondary'
    default:
      return 'secondary'
  }
}

const getCallStatusVariant = (status: string): string => {
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

const getOrderStatusVariant = (status: string): string => {
  switch (status) {
    case 'delivered':
      return 'default'
    case 'shipped':
      return 'secondary'
    case 'processing':
      return 'outline'
    case 'pending':
      return 'secondary'
    case 'cancelled':
      return 'destructive'
    default:
      return 'secondary'
  }
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString()
}
</script>
