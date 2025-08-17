<template>
  
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Agents" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">AI Agents</h1>
                <p class="text-muted-foreground">
                Manage your AI agents and their configurations
                </p>
            </div>
            <Link :href="route('agents.create')" as="button">
                <Button>
                <Plus class="w-4 h-4 mr-2" />
                Create Agent
                </Button>
            </Link>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground w-4 h-4" />
                <Input
                v-model="search"
                placeholder="Search agents..."
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
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="inactive">Inactive</SelectItem>
                </SelectContent>
            </Select>
            <Select v-model="filters.voice" @update:model-value="applyFilters">
                <SelectTrigger>
                <SelectValue placeholder="All Voices" />
                </SelectTrigger>
                <SelectContent>
                <SelectItem value="all">All Voices</SelectItem>
                <SelectItem value="voice-1">Sarah</SelectItem>
                <SelectItem value="voice-2">John</SelectItem>
                <SelectItem value="voice-3">Emma</SelectItem>
                <SelectItem value="voice-4">David</SelectItem>
                </SelectContent>
            </Select>
            <Select v-model="filters.sort" @update:model-value="applyFilters">
                <SelectTrigger>
                <SelectValue placeholder="Sort by" />
                </SelectTrigger>
                <SelectContent>
                <SelectItem value="created_at">Newest First</SelectItem>
                <SelectItem value="name">Name</SelectItem>
                <SelectItem value="updated_at">Recently Updated</SelectItem>
                </SelectContent>
            </Select>
            </div>

            <!-- Agents Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
                v-for="agent in agents.data"
                :key="agent.id"
                class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow"
            >
                <!-- Agent Header -->
                <div class="flex items-start justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <Bot class="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                    <h3 class="font-semibold text-lg">{{ agent.name }}</h3>
                    <Badge :variant="agent.is_active ? 'default' : 'secondary'">
                        {{ agent.is_active ? 'Active' : 'Inactive' }}
                    </Badge>
                    </div>
                </div>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="sm">
                        <MoreVertical class="w-4 h-4" />
                    </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                    <DropdownMenuItem @click="viewAgent(agent.id)">
                        <Eye class="w-4 h-4 mr-2" />
                        View Details
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="editAgent(agent.id)">
                        <Edit class="w-4 h-4 mr-2" />
                        Edit Agent
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="testAgent(agent)">
                        <Play class="w-4 h-4 mr-2" />
                        Test Agent
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="cloneAgent(agent.id)">
                        <Copy class="w-4 h-4 mr-2" />
                        Clone Agent
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @click="toggleAgentStatus(agent)">
                        <Power class="w-4 h-4 mr-2" />
                        {{ agent.is_active ? 'Deactivate' : 'Activate' }}
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="deleteAgent(agent)" class="text-red-600">
                        <Trash2 class="w-4 h-4 mr-2" />
                        Delete Agent
                    </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
                </div>

                <!-- Agent Description -->
                <p class="text-sm text-muted-foreground mb-4 line-clamp-2">
                {{ agent.description || 'No description provided' }}
                </p>

                <!-- Agent Details -->
                <div class="space-y-2 mb-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">Voice:</span>
                    <span class="font-medium">{{ getVoiceName(agent.voice_id) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">Campaigns:</span>
                    <span class="font-medium">{{ agent.campaigns_count || 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">Created:</span>
                    <span class="font-medium">{{ formatDate(agent.created_at) }}</span>
                </div>
                </div>

                <!-- Actions -->
                <div class="flex space-x-2">
                <Button @click="viewAgent(agent.id)" variant="outline" size="sm" class="flex-1">
                    <Eye class="w-4 h-4 mr-2" />
                    View
                </Button>
                <Button @click="editAgent(agent.id)" size="sm" class="flex-1">
                    <Edit class="w-4 h-4 mr-2" />
                    Edit
                </Button>
                </div>
            </div>
            </div>

            <!-- Empty State -->
            <div v-if="agents.data.length === 0" class="text-center py-12">
            <Bot class="w-16 h-16 text-muted-foreground mx-auto mb-4" />
            <h3 class="text-lg font-semibold mb-2">No agents found</h3>
            <p class="text-muted-foreground mb-4">
                Create your first AI agent to start making calls
            </p>
            <Link :href="route('agents.create')" as="button">
                <Button>
                <Plus class="w-4 h-4 mr-2" />
                Create Agent
                </Button>
            </Link>
            </div>

            <!-- Pagination -->
            <div v-if="agents.last_page > 1" class="flex items-center justify-between">
            <div class="text-sm text-muted-foreground">
                Showing {{ agents.from || 0 }} to {{ agents.to || 0 }} of {{ agents.total }} results
            </div>
            <div class="flex items-center space-x-2">
                <Button
                @click="goToPage(agents.current_page - 1)"
                :disabled="!agents.prev_page_url"
                variant="outline"
                size="sm"
                >
                <ChevronLeft class="w-4 h-4" />
                Previous
                </Button>
                <Button
                @click="goToPage(agents.current_page + 1)"
                :disabled="!agents.next_page_url"
                variant="outline"
                size="sm"
                >
                Next
                <ChevronRight class="w-4 h-4" />
                </Button>
            </div>
            </div>

            <!-- Test Agent Dialog -->
            <Dialog v-model:open="showTestDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                <DialogTitle>Test Agent</DialogTitle>
                <DialogDescription>
                    Test {{ selectedAgent?.name }} with a sample message
                </DialogDescription>
                </DialogHeader>
                <div class="space-y-4">
                <div>
                    <Label for="test-message">Test Message</Label>
                    <Textarea
                    id="test-message"
                    v-model="testMessage"
                    placeholder="Enter a message to test the agent..."
                    rows="3"
                    />
                </div>
                <div class="flex justify-end space-x-2">
                    <Button @click="showTestDialog = false" variant="outline">
                    Cancel
                    </Button>
                    <Button @click="runAgentTest" :disabled="!testMessage.trim()">
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
import { ref, reactive, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
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
  Bot,
  ChevronLeft,
  ChevronRight,
  Copy,
  Edit,
  Eye,
  MoreVertical,
  Play,
  Plus,
  Power,
  Search,
  Trash2,
} from 'lucide-vue-next'

interface Agent {
  id: number
  name: string
  description?: string
  voice_id: string
  is_active: boolean
  campaigns_count?: number
  created_at: string
  updated_at: string
}

interface AgentsPagination {
  data: Agent[]
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
  agents: AgentsPagination
  filters: {
    search?: string
    status?: string
    voice?: string
    sort?: string
    direction?: string
  }
}>()

// Reactive state
const search = ref(props.filters.search || '')
const filters = reactive({
  status: props.filters.status || '',
  voice: props.filters.voice || '',
  sort: props.filters.sort || 'created_at',
  direction: props.filters.direction || 'desc',
})

const showTestDialog = ref(false)
const selectedAgent = ref<Agent | null>(null)
const testMessage = ref('')

// Voice mapping
const voices = {
  'voice-1': 'Sarah',
  'voice-2': 'John',
  'voice-3': 'Emma',
  'voice-4': 'David',
}

// Methods
const debounceSearch = (() => {
  let timeout: number
  return () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
      applyFilters()
    }, 300)
  }
})()

const applyFilters = () => {
  router.get(route('agents.index'), {
    search: search.value,
    ...filters,
  }, {
    preserveState: true,
    replace: true,
  })
}

const goToPage = (page: number) => {
  router.get(route('agents.index'), {
    page,
    search: search.value,
    ...filters,
  }, {
    preserveState: true,
    replace: true,
  })
}

const viewAgent = (id: number) => {
  router.visit(route('agents.show', id))
}

const editAgent = (id: number) => {
  router.visit(route('agents.edit', id))
}

const testAgent = (agent: Agent) => {
  selectedAgent.value = agent
  testMessage.value = ''
  showTestDialog.value = true
}

const runAgentTest = () => {
  if (!selectedAgent.value || !testMessage.value.trim()) return

  router.post(route('agents.test', selectedAgent.value.id), {
    test_message: testMessage.value,
  }, {
    onSuccess: () => {
      showTestDialog.value = false
      testMessage.value = ''
      selectedAgent.value = null
    },
  })
}

const cloneAgent = (id: number) => {
  router.post(route('agents.clone', id), {}, {
    onSuccess: () => {
      // Success message will be shown via session flash
    },
  })
}

const toggleAgentStatus = (agent: Agent) => {
  router.post(route('agents.toggle-status', agent.id), {}, {
    onSuccess: () => {
      // Update local state
      agent.is_active = !agent.is_active
    },
  })
}

const deleteAgent = (agent: Agent) => {
  if (confirm(`Are you sure you want to delete "${agent.name}"? This action cannot be undone.`)) {
    router.delete(route('agents.destroy', agent.id))
  }
}

const getVoiceName = (voiceId: string): string => {
  return voices[voiceId as keyof typeof voices] || voiceId
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
