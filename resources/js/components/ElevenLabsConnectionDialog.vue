<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="max-w-2xl">
      <DialogHeader>
        <DialogTitle>Connect to ElevenLabs Agent</DialogTitle>
        <DialogDescription>
          Select an existing ElevenLabs agent to connect with this agent
        </DialogDescription>
      </DialogHeader>

      <div v-if="loading" class="flex items-center justify-center py-8">
        <Loader2 class="h-8 w-8 animate-spin" />
        <span class="ml-2">Loading ElevenLabs agents...</span>
      </div>

      <div v-else-if="elevenLabsAgents.length === 0" class="text-center py-8">
        <AlertCircle class="h-12 w-12 text-yellow-500 mx-auto mb-4" />
        <h3 class="text-lg font-medium mb-2">No ElevenLabs Agents Found</h3>
        <p class="text-muted-foreground">
          Create agents in your ElevenLabs dashboard first, then refresh this dialog.
        </p>
        <Button @click="refreshAgents" variant="outline" class="mt-4">
          <RefreshCw class="mr-2 h-4 w-4" />
          Refresh
        </Button>
      </div>

      <div v-else class="space-y-4">
        <div class="grid gap-3 max-h-96 overflow-y-auto">
          <div
            v-for="agent in elevenLabsAgents"
            :key="agent.agent_id"
            class="border rounded-lg p-4 cursor-pointer transition-colors hover:bg-muted"
            :class="{
              'border-primary bg-primary/5': selectedAgentId === agent.agent_id
            }"
            @click="selectedAgentId = agent.agent_id"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <h4 class="font-medium">{{ agent.name }}</h4>
                <p v-if="agent.prompt?.prompt" class="text-sm text-muted-foreground mt-1 line-clamp-2">
                  {{ agent.prompt.prompt }}
                </p>
                <div class="flex items-center gap-4 mt-2 text-xs text-muted-foreground">
                  <span v-if="agent.voice?.voice_id">Voice: {{ agent.voice.voice_id }}</span>
                  <span v-if="agent.language">Language: {{ agent.language }}</span>
                </div>
              </div>
              <div class="ml-4">
                <div
                  v-if="selectedAgentId === agent.agent_id"
                  class="w-5 h-5 rounded-full bg-primary flex items-center justify-center"
                >
                  <Check class="w-3 h-3 text-primary-foreground" />
                </div>
                <div
                  v-else
                  class="w-5 h-5 rounded-full border-2 border-muted-foreground"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2 p-3 bg-blue-50 rounded-lg border border-blue-200">
          <Info class="h-4 w-4 text-blue-600" />
          <span class="text-sm text-blue-700">
            Settings from this agent will be synced to the selected ElevenLabs agent
          </span>
        </div>
      </div>

      <DialogFooter>
        <Button @click="isOpen = false" variant="outline">Cancel</Button>
        <Button 
          @click="connectAgent" 
          :disabled="!selectedAgentId || connecting"
          class="bg-primary"
        >
          <Loader2 v-if="connecting" class="mr-2 h-4 w-4 animate-spin" />
          {{ connecting ? 'Connecting...' : 'Connect Agent' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Loader2, AlertCircle, RefreshCw, Check, Info } from 'lucide-vue-next'

interface ElevenLabsAgent {
  agent_id: string
  name: string
  prompt?: {
    prompt: string
  }
  voice?: {
    voice_id: string
  }
  language?: string
}

interface Props {
  open: boolean
  agentId: number
  elevenLabsAgents: ElevenLabsAgent[]
}

interface Emits {
  (e: 'update:open', value: boolean): void
  (e: 'connected', agentId: string): void
  (e: 'refresh-agents', agents: ElevenLabsAgent[]): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value)
})

const selectedAgentId = ref<string>('')
const loading = ref(false)
const connecting = ref(false)

// Reset selection when dialog opens
watch(isOpen, (newValue) => {
  if (newValue) {
    selectedAgentId.value = ''
  }
})

async function refreshAgents() {
  loading.value = true
  try {
    // Get CSRF token from meta tag or cookie
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                  document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1]
    
    if (!token) {
      throw new Error('CSRF token not found')
    }

    // Make direct API call to get fresh ElevenLabs agents
    const response = await fetch('/agents/elevenlabs/list', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (!response.ok) {
      if (response.status === 419) {
        throw new Error('Session expired. Please refresh the page and try again.')
      }
      throw new Error(`HTTP ${response.status}: ${response.statusText}`)
    }
    
    const data = await response.json()
    
    if (data.success && data.agents) {
      // Emit the fresh agents data to parent
      emit('refresh-agents', data.agents)
    } else {
      throw new Error(data.message || 'Failed to fetch agents')
    }
  } catch (error) {
    console.error('Failed to refresh agents:', error)
    // Show more specific error message
    const message = error instanceof Error ? error.message : 'Failed to refresh ElevenLabs agents. Please try again.'
    alert(message)
  } finally {
    loading.value = false
  }
}

async function connectAgent() {
  if (!selectedAgentId.value) return
  
  connecting.value = true
  
  try {
    // Use Inertia router which handles CSRF automatically
    router.post(`/agents/${props.agentId}/elevenlabs/connect`, {
      elevenlabs_agent_id: selectedAgentId.value
    }, {
      onSuccess: () => {
        emit('connected', selectedAgentId.value)
        isOpen.value = false
      },
      onError: (errors) => {
        console.error('Connection failed:', errors)
        const errorMessage = Object.values(errors)[0] as string || 'Failed to connect agent'
        alert(errorMessage)
      },
      onFinish: () => {
        connecting.value = false
      }
    })
  } catch (error) {
    console.error('Connection failed:', error)
    alert('Failed to connect agent')
    connecting.value = false
  }
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
