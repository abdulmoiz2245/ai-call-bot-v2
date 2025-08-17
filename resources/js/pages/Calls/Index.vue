<template>
  
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Calls" />
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Call History</h1>
                <p class="text-muted-foreground">
                View and manage all call records across your campaigns
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <Button @click="exportCalls" variant="outline">
                <Download class="w-4 h-4 mr-2" />
                Export
                </Button>
                <Button @click="refreshData" variant="outline">
                <RefreshCw class="w-4 h-4 mr-2" />
                Refresh
                </Button>
            </div>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground w-4 h-4" />
                <Input
                v-model="search"
                placeholder="Search calls..."
                class="pl-10"
                @input="debounceSearch"
                />
            </div>
            
            <Select v-model="filters.status" @update:model-value="applyFilters">
                <SelectTrigger>
                <SelectValue placeholder="All Statuses" />
                </SelectTrigger>
                <SelectContent>
                <SelectItem value="all">All Statuses</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="calling">Calling</SelectItem>
                <SelectItem value="answered">Answered</SelectItem>
                <SelectItem value="voicemail">Voicemail</SelectItem>
                <SelectItem value="busy">Busy</SelectItem>
                <SelectItem value="no_answer">No Answer</SelectItem>
                <SelectItem value="failed">Failed</SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="filters.campaign_id" @update:model-value="applyFilters">
                <SelectTrigger>
                <SelectValue placeholder="All Campaigns" />
                </SelectTrigger>
                <SelectContent>
                <SelectItem value="all">All Campaigns</SelectItem>
                <SelectItem
                    v-for="campaign in campaigns"
                    :key="campaign.id"
                    :value="campaign.id.toString()"
                >
                    {{ campaign.name }}
                </SelectItem>
                </SelectContent>
            </Select>

            <Input
                v-model="filters.date_from"
                type="date"
                @change="applyFilters"
            />

            <Input
                v-model="filters.date_to"
                type="date"
                @change="applyFilters"
            />

            <Select v-model="filters.sort" @update:model-value="applyFilters">
                <SelectTrigger>
                <SelectValue placeholder="Sort by" />
                </SelectTrigger>
                <SelectContent>
                <SelectItem value="created_at">Newest First</SelectItem>
                <SelectItem value="duration">Duration</SelectItem>
                <SelectItem value="cost">Cost</SelectItem>
                </SelectContent>
            </Select>
            </div>

            <!-- Call Stats Summary -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-white rounded-lg border p-4">
                <div class="flex items-center">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                    <Phone class="w-4 h-4 text-blue-600" />
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-muted-foreground">Total Calls</p>
                    <p class="text-lg font-bold">{{ calls.total?.toLocaleString() }}</p>
                </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border p-4">
                <div class="flex items-center">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                    <PhoneCall class="w-4 h-4 text-green-600" />
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-muted-foreground">Answered</p>
                    <p class="text-lg font-bold">{{ getStatusCount('answered') }}</p>
                </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border p-4">
                <div class="flex items-center">
                <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <Voicemail class="w-4 h-4 text-yellow-600" />
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-muted-foreground">Voicemail</p>
                    <p class="text-lg font-bold">{{ getStatusCount('voicemail') }}</p>
                </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border p-4">
                <div class="flex items-center">
                <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                    <PhoneOff class="w-4 h-4 text-red-600" />
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-muted-foreground">Failed</p>
                    <p class="text-lg font-bold">{{ getStatusCount('failed') + getStatusCount('no_answer') }}</p>
                </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border p-4">
                <div class="flex items-center">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                    <TrendingUp class="w-4 h-4 text-purple-600" />
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-muted-foreground">Success Rate</p>
                    <p class="text-lg font-bold">{{ getSuccessRate() }}%</p>
                </div>
                </div>
            </div>
            </div>

            <!-- Calls Table -->
            <div class="bg-white rounded-lg border">
            <div class="overflow-x-auto">
                <table class="w-full">
                <thead class="border-b">
                    <tr>
                    <th class="text-left p-4 font-medium text-muted-foreground">Contact</th>
                    <th class="text-left p-4 font-medium text-muted-foreground">Campaign</th>
                    <th class="text-left p-4 font-medium text-muted-foreground">Status</th>
                    <th class="text-left p-4 font-medium text-muted-foreground">Duration</th>
                    <th class="text-left p-4 font-medium text-muted-foreground">Cost</th>
                    <th class="text-left p-4 font-medium text-muted-foreground">Date</th>
                    <th class="text-right p-4 font-medium text-muted-foreground">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                    v-for="call in calls.data"
                    :key="call.id"
                    class="border-b hover:bg-gray-50"
                    >
                    <td class="p-4">
                        <div>
                        <p class="font-medium">{{ getContactName(call.contact) }}</p>
                        <p class="text-sm text-muted-foreground">{{ call.contact?.phone }}</p>
                        </div>
                    </td>
                    <td class="p-4">
                        <div>
                        <p class="font-medium text-sm">{{ call.campaign?.name }}</p>
                        <p class="text-xs text-muted-foreground">{{ call.campaign?.agent?.name }}</p>
                        </div>
                    </td>
                    <td class="p-4">
                        <Badge :variant="getCallStatusVariant(call.status)">
                        {{ formatStatus(call.status) }}
                        </Badge>
                    </td>
                    <td class="p-4">
                        <span class="text-sm">{{ formatDuration(call.duration) }}</span>
                    </td>
                    <td class="p-4">
                        <span class="text-sm">${{ (call.cost || 0).toFixed(3) }}</span>
                    </td>
                    <td class="p-4">
                        <div>
                        <p class="text-sm">{{ formatDate(call.created_at) }}</p>
                        <p class="text-xs text-muted-foreground">{{ formatTime(call.created_at) }}</p>
                        </div>
                    </td>
                    <td class="p-4 text-right">
                        <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="sm">
                            <MoreVertical class="w-4 h-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem @click="viewCall(call.id)">
                            <Eye class="w-4 h-4 mr-2" />
                            View Details
                            </DropdownMenuItem>
                            <DropdownMenuItem 
                            v-if="call.recording_url" 
                            @click="playRecording(call)"
                            >
                            <Play class="w-4 h-4 mr-2" />
                            Play Recording
                            </DropdownMenuItem>
                            <DropdownMenuItem 
                            v-if="call.transcript" 
                            @click="viewTranscript(call)"
                            >
                            <FileText class="w-4 h-4 mr-2" />
                            View Transcript
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem 
                            v-if="canRetryCall(call)" 
                            @click="retryCall(call)"
                            >
                            <RotateCcw class="w-4 h-4 mr-2" />
                            Retry Call
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                        </DropdownMenu>
                    </td>
                    </tr>
                </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div v-if="calls.data.length === 0" class="text-center py-12">
                <Phone class="w-16 h-16 text-muted-foreground mx-auto mb-4" />
                <h3 class="text-lg font-semibold mb-2">No calls found</h3>
                <p class="text-muted-foreground mb-4">
                No calls match your current filters
                </p>
                <Button @click="clearFilters" variant="outline">
                Clear Filters
                </Button>
            </div>

            <!-- Pagination -->
            <div v-if="calls.last_page > 1" class="border-t px-4 py-3 flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                Showing {{ calls.from || 0 }} to {{ calls.to || 0 }} of {{ calls.total }} results
                </div>
                <div class="flex items-center space-x-2">
                <Button
                    @click="goToPage(calls.current_page - 1)"
                    :disabled="!calls.prev_page_url"
                    variant="outline"
                    size="sm"
                >
                    <ChevronLeft class="w-4 h-4" />
                    Previous
                </Button>
                <Button
                    @click="goToPage(calls.current_page + 1)"
                    :disabled="!calls.next_page_url"
                    variant="outline"
                    size="sm"
                >
                    Next
                    <ChevronRight class="w-4 h-4" />
                </Button>
                </div>
            </div>
            </div>

            <!-- Recording Player Dialog -->
            <Dialog v-model:open="showRecordingDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                <DialogTitle>Call Recording</DialogTitle>
                <DialogDescription>
                    {{ selectedCall?.contact?.first_name }} {{ selectedCall?.contact?.last_name }} - {{ formatDate(selectedCall?.created_at) }}
                </DialogDescription>
                </DialogHeader>
                <div class="space-y-4">
                <div v-if="selectedCall?.recording_url" class="bg-gray-50 rounded-lg p-4">
                    <audio controls class="w-full">
                    <source :src="selectedCall.recording_url" type="audio/mpeg">
                    Your browser does not support the audio element.
                    </audio>
                </div>
                <div class="flex justify-end">
                    <Button @click="showRecordingDialog = false" variant="outline">
                    Close
                    </Button>
                </div>
                </div>
            </DialogContent>
            </Dialog>

            <!-- Transcript Dialog -->
            <Dialog v-model:open="showTranscriptDialog">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                <DialogTitle>Call Transcript</DialogTitle>
                <DialogDescription>
                    {{ selectedCall?.contact?.first_name }} {{ selectedCall?.contact?.last_name }} - {{ formatDate(selectedCall?.created_at) }}
                </DialogDescription>
                </DialogHeader>
                <div class="space-y-4">
                <div class="bg-gray-50 rounded-lg p-4 max-h-96 overflow-y-auto">
                    <div v-if="selectedCall?.transcript" class="space-y-2">
                    <div
                        v-for="(line, index) in selectedCall.transcript.split('\n')"
                        :key="index"
                        class="text-sm"
                    >
                        {{ line }}
                    </div>
                    </div>
                    <div v-else class="text-center text-muted-foreground">
                    No transcript available
                    </div>
                </div>
                <div class="flex justify-end">
                    <Button @click="showTranscriptDialog = false" variant="outline">
                    Close
                    </Button>
                </div>
                </div>
            </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  ChevronLeft,
  ChevronRight,
  Download,
  Eye,
  FileText,
  MoreVertical,
  Phone,
  PhoneCall,
  PhoneOff,
  Play,
  RefreshCw,
  RotateCcw,
  Search,
  TrendingUp,
  Voicemail,
} from 'lucide-vue-next'

interface Contact {
  id: number
  first_name: string
  last_name: string
  phone: string
}

interface Agent {
  id: number
  name: string
}

interface Campaign {
  id: number
  name: string
  agent?: Agent
}

interface Call {
  id: number
  status: string
  duration?: number
  cost?: number
  recording_url?: string
  transcript?: string
  created_at: string
  contact?: Contact
  campaign?: Campaign
}

interface CallsPagination {
  data: Call[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from?: number
  to?: number
  prev_page_url?: string
  next_page_url?: string
}

const props = defineProps<{
  calls: CallsPagination
  campaigns: Array<{ id: number; name: string }>
  filters: {
    status?: string
    search?: string
    sort?: string
    direction?: string
    campaign_id?: string
    date_from?: string
    date_to?: string
  }
}>()

// Reactive state
const search = ref(props.filters.search || '')
const filters = reactive({
  status: props.filters.status || '',
  campaign_id: props.filters.campaign_id || '',
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
  sort: props.filters.sort || 'created_at',
  direction: props.filters.direction || 'desc',
})

const showRecordingDialog = ref(false)
const showTranscriptDialog = ref(false)
const selectedCall = ref<Call | null>(null)

// Methods
const debounceSearch = (() => {
  let timeout: NodeJS.Timeout
  return () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
      applyFilters()
    }, 300)
  }
})()

const applyFilters = () => {
  router.get(route('calls.index'), {
    search: search.value,
    ...filters,
  }, {
    preserveState: true,
    replace: true,
  })
}

const clearFilters = () => {
  search.value = ''
  filters.status = ''
  filters.campaign_id = ''
  filters.date_from = ''
  filters.date_to = ''
  applyFilters()
}

const goToPage = (page: number) => {
  router.get(route('calls.index'), {
    page,
    search: search.value,
    ...filters,
  }, {
    preserveState: true,
    replace: true,
  })
}

const refreshData = () => {
  router.reload({ only: ['calls'] })
}

const exportCalls = () => {
  router.get(route('calls.export'), {
    search: search.value,
    ...filters,
  })
}

const viewCall = (id: number) => {
  router.visit(route('calls.show', id))
}

const playRecording = (call: Call) => {
  selectedCall.value = call
  showRecordingDialog.value = true
}

const viewTranscript = (call: Call) => {
  selectedCall.value = call
  showTranscriptDialog.value = true
}

const retryCall = (call: Call) => {
  if (confirm('Are you sure you want to retry this call?')) {
    router.post(route('calls.retry', call.id), {}, {
      onSuccess: () => {
        refreshData()
      },
    })
  }
}

const canRetryCall = (call: Call): boolean => {
  return ['failed', 'no_answer', 'busy'].includes(call.status)
}

const getContactName = (contact?: Contact): string => {
  if (!contact) return 'Unknown'
  return `${contact.first_name} ${contact.last_name}`
}

const getCallStatusVariant = (status: string) => {
  switch (status) {
    case 'answered': return 'default'
    case 'voicemail': return 'secondary'
    case 'calling': return 'outline'
    case 'pending': return 'secondary'
    case 'busy': return 'secondary'
    case 'no_answer': return 'outline'
    case 'failed': return 'destructive'
    default: return 'secondary'
  }
}

const formatStatus = (status: string): string => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDuration = (duration?: number): string => {
  if (!duration) return '0s'
  
  const minutes = Math.floor(duration / 60)
  const seconds = duration % 60
  
  if (minutes > 0) {
    return `${minutes}m ${seconds}s`
  }
  return `${seconds}s`
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const formatTime = (dateString: string): string => {
  return new Date(dateString).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getStatusCount = (status: string): number => {
  return props.calls.data.filter(call => call.status === status).length
}

const getSuccessRate = (): number => {
  const total = props.calls.data.length
  if (total === 0) return 0
  
  const successful = props.calls.data.filter(call => 
    ['answered', 'voicemail'].includes(call.status)
  ).length
  
  return Math.round((successful / total) * 100)
}
</script>
