<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Call Details - ${getContactName(call.contact)}`" />
  
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <Link :href="route('calls.index')" as="button">
                <Button variant="ghost" size="sm">
                    <ArrowLeft class="w-4 h-4 mr-2" />
                    Back to Calls
                </Button>
                </Link>
                <div>
                <h1 class="text-3xl font-bold tracking-tight">Call Details</h1>
                <p class="text-muted-foreground">
                    {{ getContactName(call.contact) }} • {{ formatDateTime(call.created_at) }}
                </p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <Badge :variant="getCallStatusVariant(call.status)" class="text-sm">
                {{ formatStatus(call.status) }}
                </Badge>
                <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="outline" size="sm">
                    <MoreVertical class="w-4 h-4 mr-2" />
                    Actions
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem 
                    v-if="call.recording_url" 
                    @click="downloadRecording"
                    >
                    <Download class="w-4 h-4 mr-2" />
                    Download Recording
                    </DropdownMenuItem>
                    <DropdownMenuItem 
                    v-if="canRetryCall" 
                    @click="retryCall"
                    >
                    <RotateCcw class="w-4 h-4 mr-2" />
                    Retry Call
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @click="viewContact">
                    <User class="w-4 h-4 mr-2" />
                    View Contact
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="viewCampaign">
                    <MessageSquare class="w-4 h-4 mr-2" />
                    View Campaign
                    </DropdownMenuItem>
                </DropdownMenuContent>
                </DropdownMenu>
            </div>
            </div>

            <!-- Call Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white rounded-lg border p-6">
                <div class="flex items-center">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                    <Phone class="w-4 h-4 text-blue-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-muted-foreground">Status</p>
                    <p class="text-lg font-bold">{{ formatStatus(call.status) }}</p>
                </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border p-6">
                <div class="flex items-center">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                    <Clock class="w-4 h-4 text-green-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-muted-foreground">Duration</p>
                    <p class="text-lg font-bold">{{ formatDuration(call.duration) }}</p>
                </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border p-6">
                <div class="flex items-center">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                    <DollarSign class="w-4 h-4 text-purple-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-muted-foreground">Cost</p>
                    <p class="text-lg font-bold">${{ (call.cost || 0).toFixed(3) }}</p>
                </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border p-6">
                <div class="flex items-center">
                <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                    <Calendar class="w-4 h-4 text-orange-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-muted-foreground">Date & Time</p>
                    <p class="text-lg font-bold">{{ formatTime(call.created_at) }}</p>
                </div>
                </div>
            </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Call Recording -->
                <div v-if="call.recording_url" class="bg-white rounded-lg border">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Call Recording</h2>
                </div>
                <div class="p-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                    <audio controls class="w-full">
                        <source :src="call.recording_url" type="audio/mpeg">
                        Your browser does not support the audio element.
                    </audio>
                    </div>
                    <div class="mt-4 flex justify-between items-center">
                    <p class="text-sm text-muted-foreground">
                        Recording duration: {{ formatDuration(call.duration) }}
                    </p>
                    <Button @click="downloadRecording" variant="outline" size="sm">
                        <Download class="w-4 h-4 mr-2" />
                        Download
                    </Button>
                    </div>
                </div>
                </div>

                <!-- Call Transcript -->
                <div v-if="call.transcript" class="bg-white rounded-lg border">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Call Transcript</h2>
                </div>
                <div class="p-6">
                    <div class="bg-gray-50 rounded-lg p-4 max-h-96 overflow-y-auto">
                    <div class="space-y-3">
                        <div
                        v-for="(line, index) in transcriptLines"
                        :key="index"
                        class="text-sm"
                        :class="getTranscriptLineClass(line)"
                        >
                        {{ line }}
                        </div>
                    </div>
                    </div>
                </div>
                </div>

                <!-- Call Events Timeline -->
                <div class="bg-white rounded-lg border">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Call Timeline</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                    <div
                        v-for="event in callEvents"
                        :key="event.id"
                        class="flex items-start space-x-3"
                    >
                        <div
                        class="w-2 h-2 rounded-full mt-2"
                        :class="getEventColor(event.type)"
                        ></div>
                        <div class="flex-1">
                        <p class="text-sm font-medium">{{ event.description }}</p>
                        <p class="text-xs text-muted-foreground">{{ formatTime(event.created_at) }}</p>
                        </div>
                    </div>
                    </div>
                </div>
                </div>

                <!-- Call Notes -->
                <div class="bg-white rounded-lg border">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Call Notes</h2>
                </div>
                <div class="p-6">
                    <div v-if="call.notes" class="mb-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm whitespace-pre-wrap">{{ call.notes }}</p>
                    </div>
                    </div>
                    <div v-else class="text-center py-6 text-muted-foreground">
                    <FileText class="w-8 h-8 mx-auto mb-2" />
                    <p class="text-sm">No notes available for this call</p>
                    </div>
                </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="p-6">
                <!-- Contact Information -->
                <div class="bg-white rounded-lg border">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Contact Information</h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center space-x-3 mb-4">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <User class="w-5 h-5 text-blue-600" />
                    </div>
                    <div>
                        <p class="font-medium">{{ getContactName(call.contact) }}</p>
                        <p class="text-sm text-muted-foreground">{{ call.contact?.phone }}</p>
                    </div>
                    </div>
                    <div class="space-y-2">
                    <div v-if="call.contact?.email">
                        <Label class="text-sm font-medium text-muted-foreground">Email</Label>
                        <p class="text-sm">{{ call.contact.email }}</p>
                    </div>
                    <div v-if="call.contact?.company">
                        <Label class="text-sm font-medium text-muted-foreground">Company</Label>
                        <p class="text-sm">{{ call.contact.company }}</p>
                    </div>
                    <div v-if="call.contact?.timezone">
                        <Label class="text-sm font-medium text-muted-foreground">Timezone</Label>
                        <p class="text-sm">{{ call.contact.timezone }}</p>
                    </div>
                    </div>
                    <div class="mt-4">
                    <Button @click="viewContact" variant="outline" size="sm" class="w-full">
                        <User class="w-4 h-4 mr-2" />
                        View Contact Details
                    </Button>
                    </div>
                </div>
                </div>

                <!-- Campaign Information -->
                <div class="bg-white rounded-lg border">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Campaign Details</h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center space-x-3 mb-4">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <MessageSquare class="w-5 h-5 text-green-600" />
                    </div>
                    <div>
                        <p class="font-medium">{{ call.campaign?.name }}</p>
                        <Badge :variant="getCampaignStatusVariant(call.campaign?.status)" class="text-xs">
                        {{ call.campaign?.status }}
                        </Badge>
                    </div>
                    </div>
                    <div class="space-y-2">
                    <div>
                        <Label class="text-sm font-medium text-muted-foreground">AI Agent</Label>
                        <p class="text-sm">{{ call.campaign?.agent?.name || 'Unknown' }}</p>
                    </div>
                    <div>
                        <Label class="text-sm font-medium text-muted-foreground">Campaign Type</Label>
                        <p class="text-sm">{{ call.campaign?.type || 'Standard' }}</p>
                    </div>
                    </div>
                    <div class="mt-4">
                    <Button @click="viewCampaign" variant="outline" size="sm" class="w-full">
                        <MessageSquare class="w-4 h-4 mr-2" />
                        View Campaign Details
                    </Button>
                    </div>
                </div>
                </div>

                <!-- Call Actions -->
                <div class="bg-white rounded-lg border">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Actions</h2>
                </div>
                <div class="p-6 space-y-3">
                    <Button 
                    v-if="canRetryCall" 
                    @click="retryCall" 
                    variant="outline" 
                    class="w-full"
                    >
                    <RotateCcw class="w-4 h-4 mr-2" />
                    Retry Call
                    </Button>
                    <Button 
                    v-if="call.recording_url" 
                    @click="downloadRecording" 
                    variant="outline" 
                    class="w-full"
                    >
                    <Download class="w-4 h-4 mr-2" />
                    Download Recording
                    </Button>
                    <Button @click="addToContacts" variant="outline" class="w-full">
                    <Plus class="w-4 h-4 mr-2" />
                    Add to Campaign
                    </Button>
                </div>
                </div>
            </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import { Label } from '@/components/ui/label'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  ArrowLeft,
  Calendar,
  Clock,
  DollarSign,
  Download,
  FileText,
  MessageSquare,
  MoreVertical,
  Phone,
  Plus,
  RotateCcw,
  User,
} from 'lucide-vue-next'

interface Contact {
  id: number
  first_name: string
  last_name: string
  phone: string
  email?: string
  company?: string
  timezone?: string
}

interface Agent {
  id: number
  name: string
}

interface Campaign {
  id: number
  name: string
  status: string
  type?: string
  agent?: Agent
}

interface CallEvent {
  id: number
  type: string
  description: string
  created_at: string
}

interface Call {
  id: number
  status: string
  duration?: number
  cost?: number
  recording_url?: string
  transcript?: string
  notes?: string
  created_at: string
  contact?: Contact
  campaign?: Campaign
  events?: CallEvent[]
}

const props = defineProps<{
  call: Call
  events: CallEvent[]
}>()

// Computed
const canRetryCall = computed(() => {
  return ['failed', 'no_answer', 'busy'].includes(props.call.status)
})

const transcriptLines = computed(() => {
  if (!props.call.transcript) return []
  return props.call.transcript.split('\n').filter(line => line.trim())
})

const callEvents = computed(() => {
  return props.events || []
})

// Methods
const getContactName = (contact?: Contact): string => {
  if (!contact) return 'Unknown Contact'
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

const getCampaignStatusVariant = (status?: string) => {
  switch (status) {
    case 'active': return 'default'
    case 'paused': return 'secondary'
    case 'completed': return 'outline'
    case 'draft': return 'secondary'
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

const formatDateTime = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatTime = (dateString: string): string => {
  return new Date(dateString).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

const getTranscriptLineClass = (line: string): string => {
  if (line.includes('Agent:') || line.includes('AI:')) {
    return 'text-blue-700 font-medium'
  }
  if (line.includes('Contact:') || line.includes('Customer:')) {
    return 'text-green-700 font-medium'
  }
  return 'text-gray-700'
}

const getEventColor = (eventType: string): string => {
  switch (eventType) {
    case 'call_initiated': return 'bg-blue-500'
    case 'call_answered': return 'bg-green-500'
    case 'call_ended': return 'bg-gray-500'
    case 'call_failed': return 'bg-red-500'
    case 'voicemail_left': return 'bg-yellow-500'
    default: return 'bg-gray-400'
  }
}

const retryCall = () => {
  if (confirm('Are you sure you want to retry this call?')) {
    router.post(route('calls.retry', props.call.id), {}, {
      onSuccess: () => {
        router.reload({ only: ['call', 'events'] })
      },
    })
  }
}

const downloadRecording = () => {
  if (props.call.recording_url) {
    router.get(route('calls.recording', props.call.id))
  }
}

const viewContact = () => {
  if (props.call.contact) {
    router.visit(route('contacts.show', props.call.contact.id))
  }
}

const viewCampaign = () => {
  if (props.call.campaign) {
    router.visit(route('campaigns.show', props.call.campaign.id))
  }
}

const addToContacts = () => {
  // This could open a dialog to add this contact to a new campaign
  alert('Feature to add contact to campaign would be implemented here')
}
</script>
