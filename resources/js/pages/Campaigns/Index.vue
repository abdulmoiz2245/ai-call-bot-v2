<template>

    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Campaigns" />
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                <h1 class="text-3xl font-bold tracking-tight">Campaigns</h1>
                <p class="text-muted-foreground">
                    Manage your call campaigns and track their performance.
                </p>
                </div>
                <Button @click="router.visit(route('campaigns.create'))">
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
            </div>

            <!-- Filters -->
            <Card>
                <CardContent class="pt-6">
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-64">
                    <Input
                        v-model="localFilters.search"
                        placeholder="Search campaigns..."
                        class="max-w-sm"
                        @input="debouncedFilter"
                    />
                    </div>
                    <Select v-model="localFilters.status">
                    <SelectTrigger class="w-40">
                        <SelectValue placeholder="All Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Status</SelectItem>
                        <SelectItem value="draft">Draft</SelectItem>
                        <SelectItem value="active">Active</SelectItem>
                        <SelectItem value="paused">Paused</SelectItem>
                        <SelectItem value="completed">Completed</SelectItem>
                    </SelectContent>
                    </Select>
                    <Button @click="applyFilters" variant="outline">
                    Apply Filters
                    </Button>
                    <Button @click="clearFilters" variant="ghost">
                    Clear
                    </Button>
                </div>
                </CardContent>
            </Card>

            <!-- Campaigns List -->
            <Card>
                <CardContent class="p-0">
                <div class="relative w-full overflow-auto">
                    <Table>
                    <TableHeader>
                        <TableRow>
                        <TableHead class="w-12">
                            <Checkbox
                            :checked="selectedCampaigns.length === campaigns.data.length"
                            @update:checked="toggleAllCampaigns"
                            />
                        </TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Agent</TableHead>
                        <TableHead>Calls</TableHead>
                        <TableHead>Success Rate</TableHead>
                        <TableHead>Created</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="campaign in campaigns.data" :key="campaign.id">
                        <TableCell>
                            <Checkbox
                            :checked="selectedCampaigns.includes(campaign.id)"
                            @update:checked="toggleCampaign(campaign.id)"
                            />
                        </TableCell>
                        <TableCell>
                            <div class="font-medium">{{ campaign.name }}</div>
                            <div class="text-sm text-muted-foreground">
                            {{ campaign.description }}
                            </div>
                        </TableCell>
                        <TableCell>
                            <Badge :variant="getStatusVariant(campaign.status)">
                            {{ campaign.status }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            {{ campaign.agent?.name || 'No Agent' }}
                        </TableCell>
                        <TableCell>
                            {{ campaign.calls_count || 0 }}
                        </TableCell>
                        <TableCell>
                            {{ formatPercent(campaign.success_rate) }}
                        </TableCell>
                        <TableCell>
                            {{ formatDate(campaign.created_at) }}
                        </TableCell>
                        <TableCell class="text-right">
                            <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" class="h-8 w-8 p-0">
                                <span class="sr-only">Open menu</span>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    class="h-4 w-4"
                                >
                                    <circle cx="12" cy="12" r="1" />
                                    <circle cx="19" cy="12" r="1" />
                                    <circle cx="5" cy="12" r="1" />
                                </svg>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem @click="router.visit(route('campaigns.show', campaign.id))">
                                View Details
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="router.visit(route('campaigns.edit', campaign.id))">
                                Edit
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                v-if="campaign.status === 'draft'"
                                @click="activateCampaign(campaign.id)"
                                >
                                Activate
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                v-if="campaign.status === 'active'"
                                @click="pauseCampaign(campaign.id)"
                                >
                                Pause
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                v-if="campaign.status === 'paused'"
                                @click="activateCampaign(campaign.id)"
                                >
                                Resume
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                v-if="['active', 'paused'].includes(campaign.status)"
                                @click="completeCampaign(campaign.id)"
                                >
                                Complete
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                v-if="campaign.status === 'draft'"
                                @click="deleteCampaign(campaign.id)"
                                class="text-destructive"
                                >
                                Delete
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                            </DropdownMenu>
                        </TableCell>
                        </TableRow>
                    </TableBody>
                    </Table>
                </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                Showing {{ campaigns.from }} to {{ campaigns.to }} of {{ campaigns.total }} results
                </div>
                <div class="flex items-center space-x-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!campaigns.prev_page_url"
                    @click="campaigns.prev_page_url && router.visit(campaigns.prev_page_url)"
                >
                    Previous
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!campaigns.next_page_url"
                    @click="campaigns.next_page_url && router.visit(campaigns.next_page_url)"
                >
                    Next
                </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/layouts/AppLayout.vue'

// Simple debounce function
function debounce<T extends (...args: any[]) => any>(func: T, wait: number): T {
  let timeout: number
  return ((...args: any[]) => {
    clearTimeout(timeout)
    timeout = setTimeout(() => func(...args), wait)
  }) as T
}
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Card,
  CardContent,
} from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
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

interface Campaign {
  id: number
  name: string
  description?: string
  status: string
  agent?: Agent
  calls_count: number
  success_rate: number
  created_at: string
}

interface PaginatedCampaigns {
  data: Campaign[]
  from: number
  to: number
  total: number
  prev_page_url?: string
  next_page_url?: string
}

interface Props {
  campaigns: PaginatedCampaigns
  filters: {
    search?: string
    status?: string
    sort?: string
    direction?: string
  }
}

const props = defineProps<Props>()

// Breadcrumbs
const breadcrumbs = [
  { title: 'Dashboard', href: route('dashboard') },
  { title: 'Campaigns', href: '#' }
]

const selectedCampaigns = ref<number[]>([])
const localFilters = reactive({
  search: props.filters.search || '',
  status: props.filters.status || '',
})

const debouncedFilter = debounce(applyFilters, 300)

function applyFilters() {
  router.visit(route('campaigns.index'), {
    data: localFilters,
    preserveState: true,
    preserveScroll: true,
  })
}

function clearFilters() {
  localFilters.search = ''
  localFilters.status = ''
  applyFilters()
}

function toggleCampaign(campaignId: number) {
  const index = selectedCampaigns.value.indexOf(campaignId)
  if (index > -1) {
    selectedCampaigns.value.splice(index, 1)
  } else {
    selectedCampaigns.value.push(campaignId)
  }
}

function toggleAllCampaigns(checked: boolean) {
  if (checked) {
    selectedCampaigns.value = props.campaigns.data.map(c => c.id)
  } else {
    selectedCampaigns.value = []
  }
}

function activateCampaign(campaignId: number) {
  router.post(route('campaigns.activate', campaignId))
}

function pauseCampaign(campaignId: number) {
  router.post(route('campaigns.pause', campaignId))
}

function completeCampaign(campaignId: number) {
  router.post(route('campaigns.complete', campaignId))
}

function deleteCampaign(campaignId: number) {
  if (confirm('Are you sure you want to delete this campaign?')) {
    router.delete(route('campaigns.destroy', campaignId))
  }
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

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString()
}

function formatPercent(value: number): string {
  return value ? `${value.toFixed(1)}%` : '0%'
}
</script>
