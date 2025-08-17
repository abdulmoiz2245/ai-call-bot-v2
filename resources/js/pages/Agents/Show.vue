<template>
    <Head :title="agent.name" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <Link :href="route('agents.index')" as="button">
                <Button variant="ghost" size="sm">
                    <ArrowLeft class="w-4 h-4 mr-2" />
                    Back to Agents
                </Button>
                </Link>
                <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-3xl font-bold tracking-tight">{{ agent.name }}</h1>
                    <Badge :variant="agent.is_active ? 'default' : 'secondary'">
                    {{ agent.is_active ? 'Active' : 'Inactive' }}
                    </Badge>
                </div>
                <p class="text-muted-foreground">
                    {{ agent.description || 'No description provided' }}
                </p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <Button @click="testAgent" variant="outline">
                <Play class="w-4 h-4 mr-2" />
                Test Agent
                </Button>
                <Button @click="cloneAgent" variant="outline">
                <Copy class="w-4 h-4 mr-2" />
                Clone
                </Button>
                <Link :href="route('agents.edit', agent.id)" as="button">
                <Button>
                    <Edit class="w-4 h-4 mr-2" />
                    Edit Agent
                </Button>
                </Link>
                <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="sm">
                    <MoreVertical class="w-4 h-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem @click="toggleStatus">
                    <Power class="w-4 h-4 mr-2" />
                    {{ agent.is_active ? 'Deactivate' : 'Activate' }}
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @click="deleteAgent" class="text-red-600">
                    <Trash2 class="w-4 h-4 mr-2" />
                    Delete Agent
                    </DropdownMenuItem>
                </DropdownMenuContent>
                </DropdownMenu>
            </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white rounded-lg border p-6">
                <div class="flex items-center">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                    <MessageSquare class="w-4 h-4 text-blue-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-muted-foreground">Total Campaigns</p>
                    <p class="text-2xl font-bold">{{ stats.total_campaigns }}</p>
                </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border p-6">
                <div class="flex items-center">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                    <Activity class="w-4 h-4 text-green-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-muted-foreground">Active Campaigns</p>
                    <p class="text-2xl font-bold">{{ stats.active_campaigns }}</p>
                </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border p-6">
                <div class="flex items-center">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                    <Phone class="w-4 h-4 text-purple-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-muted-foreground">Total Calls</p>
                    <p class="text-2xl font-bold">{{ stats.total_calls }}</p>
                </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border p-6">
                <div class="flex items-center">
                <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                    <TrendingUp class="w-4 h-4 text-orange-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-muted-foreground">Success Rate</p>
                    <p class="text-2xl font-bold">{{ Math.round(stats.success_rate) }}%</p>
                </div>
                </div>
            </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Agent Configuration -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Info -->
                <div class="bg-white rounded-lg border">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Agent Configuration</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label class="text-sm font-medium text-muted-foreground">Voice</Label>
                        <p class="text-sm">{{ getVoiceName(agent.voice_id) }}</p>
                    </div>
                    <div>
                        <Label class="text-sm font-medium text-muted-foreground">Created</Label>
                        <p class="text-sm">{{ formatDate(agent.created_at) }}</p>
                    </div>
                    <div>
                        <Label class="text-sm font-medium text-muted-foreground">Last Updated</Label>
                        <p class="text-sm">{{ formatDate(agent.updated_at) }}</p>
                    </div>
                    <div>
                        <Label class="text-sm font-medium text-muted-foreground">Created By</Label>
                        <p class="text-sm">{{ agent.creator?.name || 'Unknown' }}</p>
                    </div>
                    </div>
                </div>
                </div>

                <!-- System Prompt -->
                <div class="bg-white rounded-lg border">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">System Prompt</h2>
                </div>
                <div class="p-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm whitespace-pre-wrap">{{ agent.system_prompt || 'No system prompt defined' }}</p>
                    </div>
                </div>
                </div>

                <!-- Messages -->
                <div class="bg-white rounded-lg border">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Messages</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                    <Label class="text-sm font-medium text-muted-foreground">Greeting Message</Label>
                    <div class="mt-1 p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm">{{ agent.greeting_message || 'No greeting message defined' }}</p>
                    </div>
                    </div>
                    <div>
                    <Label class="text-sm font-medium text-muted-foreground">Closing Message</Label>
                    <div class="mt-1 p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm">{{ agent.closing_message || 'No closing message defined' }}</p>
                    </div>
                    </div>
                </div>
                </div>

                <!-- Voice Settings -->
                <div class="bg-white rounded-lg border">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Voice Settings</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-3 gap-4">
                    <div>
                        <Label class="text-sm font-medium text-muted-foreground">Speed</Label>
                        <p class="text-sm capitalize">{{ agent.voice_settings?.speed || 'Normal' }}</p>
                    </div>
                    <div>
                        <Label class="text-sm font-medium text-muted-foreground">Pitch</Label>
                        <p class="text-sm capitalize">{{ agent.voice_settings?.pitch || 'Normal' }}</p>
                    </div>
                    <div>
                        <Label class="text-sm font-medium text-muted-foreground">Stability</Label>
                        <p class="text-sm capitalize">{{ agent.voice_settings?.stability || 'Balanced' }}</p>
                    </div>
                    </div>
                </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="p-6">
                <!-- Campaigns Using This Agent -->
                <div class="bg-white rounded-lg border">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Campaigns</h2>
                </div>
                <div class="p-6">
                    <div v-if="campaigns.length === 0" class="text-center py-4">
                    <MessageSquare class="w-8 h-8 text-muted-foreground mx-auto mb-2" />
                    <p class="text-sm text-muted-foreground">No campaigns using this agent</p>
                    </div>
                    <div v-else class="space-y-3">
                    <div
                        v-for="campaign in campaigns"
                        :key="campaign.id"
                        class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        <div>
                        <p class="font-medium text-sm">{{ campaign.name }}</p>
                        <Badge :variant="getCampaignStatusVariant(campaign.status)" class="text-xs">
                            {{ campaign.status }}
                        </Badge>
                        </div>
                        <Link :href="route('campaigns.show', campaign.id)" as="button">
                        <Button variant="ghost" size="sm">
                            <ExternalLink class="w-4 h-4" />
                        </Button>
                        </Link>
                    </div>
                    </div>
                </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg border">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Quick Actions</h2>
                </div>
                <div class="p-6 space-y-3">
                    <Button @click="testAgent" variant="outline" class="w-full">
                    <Play class="w-4 h-4 mr-2" />
                    Test Agent
                    </Button>
                    <Button @click="cloneAgent" variant="outline" class="w-full">
                    <Copy class="w-4 h-4 mr-2" />
                    Clone Agent
                    </Button>
                    <Link :href="route('campaigns.create', { agent_id: agent.id })" as="button" class="w-full">
                    <Button variant="outline" class="w-full">
                        <Plus class="w-4 h-4 mr-2" />
                        Create Campaign
                    </Button>
                    </Link>
                </div>
                </div>
            </div>
            </div>

            <!-- Test Agent Dialog -->
            <Dialog v-model:open="showTestDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                <DialogTitle>Test {{ agent.name }}</DialogTitle>
                <DialogDescription>
                    Test the agent with a sample conversation
                </DialogDescription>
                </DialogHeader>
                <div class="space-y-4">
                <div>
                    <Label for="test-message">Test Message</Label>
                    <Textarea
                    id="test-message"
                    v-model="testMessage"
                    placeholder="Hi, I'm interested in your services..."
                    rows="3"
                    />
                </div>
                <div class="flex justify-end space-x-2">
                    <Button @click="showTestDialog = false" variant="outline">
                    Cancel
                    </Button>
                    <Button @click="runTest" :disabled="!testMessage.trim()">
                    <Play class="w-4 h-4 mr-2" />
                    Test Agent
                    </Button>
                </div>
                </div>
            </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Badge } from '@/components/ui/badge'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
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
  Activity,
  ArrowLeft,
  Copy,
  Edit,
  ExternalLink,
  MessageSquare,
  MoreVertical,
  Phone,
  Play,
  Plus,
  Power,
  Trash2,
  TrendingUp,
} from 'lucide-vue-next'

interface Agent {
  id: number
  name: string
  description?: string
  voice_id: string
  voice_settings?: {
    speed?: string
    pitch?: string
    stability?: string
  }
  system_prompt?: string
  greeting_message?: string
  closing_message?: string
  is_active: boolean
  created_at: string
  updated_at: string
  creator?: {
    name: string
  }
}

interface Campaign {
  id: number
  name: string
  status: string
}

interface Stats {
  total_campaigns: number
  active_campaigns: number
  total_calls: number
  success_rate: number
}

const props = defineProps<{
  agent: Agent
  campaigns: Campaign[]
  stats: Stats
  voices: Array<{
    id: string
    name: string
    language: string
    gender: string
  }>
}>()

// Breadcrumbs
const breadcrumbs = [
  { title: 'Dashboard', href: route('dashboard') },
  { title: 'Agents', href: route('agents.index') },
  { title: props.agent.name, href: '#' }
]

// Reactive state
const showTestDialog = ref(false)
const testMessage = ref('')

// Voice mapping
const voices = {
  'voice-1': 'Sarah',
  'voice-2': 'John',
  'voice-3': 'Emma',
  'voice-4': 'David',
}

// Methods
const testAgent = () => {
  testMessage.value = ''
  showTestDialog.value = true
}

const runTest = () => {
  if (!testMessage.value.trim()) return

  router.post(route('agents.test', props.agent.id), {
    test_message: testMessage.value,
  }, {
    onSuccess: () => {
      showTestDialog.value = false
      testMessage.value = ''
    },
  })
}

const cloneAgent = () => {
  router.post(route('agents.clone', props.agent.id), {}, {
    onSuccess: () => {
      // Success message will be shown via session flash
    },
  })
}

const toggleStatus = () => {
  router.post(route('agents.toggle-status', props.agent.id), {}, {
    onSuccess: () => {
      // The page will refresh with updated status
    },
  })
}

const deleteAgent = () => {
  if (confirm(`Are you sure you want to delete "${props.agent.name}"? This action cannot be undone.`)) {
    router.delete(route('agents.destroy', props.agent.id))
  }
}

const getVoiceName = (voiceId: string): string => {
  const voice = props.voices.find(v => v.id === voiceId)
  return voice ? `${voice.name} (${voice.gender}, ${voice.language})` : voiceId
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

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>
