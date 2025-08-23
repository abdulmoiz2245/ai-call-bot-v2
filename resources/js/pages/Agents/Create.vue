<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Create Agent" />

    <div class="p-6">
      <div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center space-x-4">
      <Link :href="route('agents.index')" as="button">
        <Button variant="ghost" size="sm">
          <ArrowLeft class="w-4 h-4 mr-2" />
          Back to Agents
        </Button>
      </Link>
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Create AI Agent</h1>
        <p class="text-muted-foreground">
          Configure your AI agent's personality, voice, and conversation flow
        </p>
      </div>
    </div>

    <!-- Form -->
    <form @submit.prevent="submit" class="space-y-8">
      <!-- Basic Information -->
      <div class="bg-white rounded-lg border p-6">
        <h2 class="text-xl font-semibold mb-4">Basic Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-2">
            <Label for="name">Agent Name</Label>
            <Input
              id="name"
              v-model="form.name"
              :class="{ 'border-red-500': errors.name }"
              placeholder="e.g., Sales Assistant"
              required
            />
            <div v-if="errors.name" class="text-red-500 text-sm">{{ errors.name }}</div>
          </div>
          
          <div class="space-y-2">
            <Label for="role">Agent Role</Label>
            <Select v-model="form.role">
              <SelectTrigger :class="{ 'border-red-500': errors.role }">
                <SelectValue placeholder="Select a role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="sales">Sales Agent</SelectItem>
                <SelectItem value="support">Support Agent</SelectItem>
                <SelectItem value="lead_qualification">Lead Qualification</SelectItem>
                <SelectItem value="appointment_booking">Appointment Booking</SelectItem>
                <SelectItem value="customer_service">Customer Service</SelectItem>
                <SelectItem value="surveys">Survey & Feedback</SelectItem>
              </SelectContent>
            </Select>
            <div v-if="errors.role" class="text-red-500 text-sm">{{ errors.role }}</div>
          </div>

          <div class="space-y-2">
            <Label for="voice">Voice</Label>
            <Select v-model="form.voice_id" required>
              <SelectTrigger :class="{ 'border-red-500': errors.voice_id }">
                <SelectValue placeholder="Select a voice" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="voice in voices"
                  :key="voice.id"
                  :value="voice.id"
                >
                  {{ voice.name }} ({{ voice.gender }}, {{ voice.language }})
                </SelectItem>
              </SelectContent>
            </Select>
            <div v-if="errors.voice_id" class="text-red-500 text-sm">{{ errors.voice_id }}</div>
          </div>

          <div class="space-y-2">
            <Label for="tone">Agent Tone</Label>
            <Select v-model="form.tone">
              <SelectTrigger>
                <SelectValue placeholder="Professional" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="professional">Professional</SelectItem>
                <SelectItem value="friendly">Friendly</SelectItem>
                <SelectItem value="casual">Casual</SelectItem>
                <SelectItem value="formal">Formal</SelectItem>
                <SelectItem value="enthusiastic">Enthusiastic</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="language">Language</Label>
            <Select v-model="form.language">
              <SelectTrigger>
                <SelectValue placeholder="English" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="en">English</SelectItem>
                <SelectItem value="es">Spanish</SelectItem>
                <SelectItem value="fr">French</SelectItem>
                <SelectItem value="de">German</SelectItem>
                <SelectItem value="it">Italian</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <div class="mt-6 space-y-2">
          <Label for="description">Description</Label>
          <Textarea
            id="description"
            v-model="form.description"
            placeholder="Brief description of what this agent does..."
            rows="3"
          />
        </div>

        <div class="mt-6 space-y-2">
          <Label for="persona">Agent Persona</Label>
          <Textarea
            id="persona"
            v-model="form.persona"
            placeholder="Describe the agent's personality and characteristics..."
            rows="3"
          />
          <p class="text-sm text-muted-foreground">
            How should the agent present itself? (e.g., helpful, knowledgeable, empathetic)
          </p>
        </div>
      </div>

      <!-- System Prompt & Personality -->
      <div class="bg-white rounded-lg border p-6">
        <h2 class="text-xl font-semibold mb-4">AI Configuration</h2>
        
        <div class="p-6">
          <div class="space-y-2">
            <Label for="system_prompt">System Prompt</Label>
            <Textarea
              id="system_prompt"
              v-model="form.system_prompt"
              :class="{ 'border-red-500': errors.system_prompt }"
              placeholder="You are a helpful sales assistant. Your role is to..."
              rows="6"
              required
            />
            <p class="text-sm text-muted-foreground">
              Define the agent's role, personality, and behavior guidelines
            </p>
            <div v-if="errors.system_prompt" class="text-red-500 text-sm">{{ errors.system_prompt }}</div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <Label for="greeting_message">Greeting Message</Label>
              <Textarea
                id="greeting_message"
                v-model="form.greeting_message"
                placeholder="Hi, this is Sarah from ABC Company..."
                rows="3"
              />
              <p class="text-sm text-muted-foreground">
                What the agent says when the call connects
              </p>
            </div>

            <div class="space-y-2">
              <Label for="closing_message">Closing Message</Label>
              <Textarea
                id="closing_message"
                v-model="form.closing_message"
                placeholder="Thank you for your time. Have a great day!"
                rows="3"
              />
              <p class="text-sm text-muted-foreground">
                What the agent says before ending the call
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Voice Settings -->
      <div class="bg-white rounded-lg border p-6">
        <h2 class="text-xl font-semibold mb-4">Voice Settings</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="space-y-2">
            <Label for="voice_speed">Speaking Speed</Label>
            <Select v-model="form.voice_settings.speed">
              <SelectTrigger>
                <SelectValue placeholder="Normal" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="slow">Slow</SelectItem>
                <SelectItem value="normal">Normal</SelectItem>
                <SelectItem value="fast">Fast</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="voice_pitch">Voice Pitch</Label>
            <Select v-model="form.voice_settings.pitch">
              <SelectTrigger>
                <SelectValue placeholder="Normal" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="low">Low</SelectItem>
                <SelectItem value="normal">Normal</SelectItem>
                <SelectItem value="high">High</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="voice_stability">Voice Stability</Label>
            <Select v-model="form.voice_settings.stability">
              <SelectTrigger>
                <SelectValue placeholder="Balanced" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="low">Variable</SelectItem>
                <SelectItem value="balanced">Balanced</SelectItem>
                <SelectItem value="high">Stable</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <div class="mt-6">
          <Button @click="testVoice" type="button" variant="outline">
            <Play class="w-4 h-4 mr-2" />
            Test Voice
          </Button>
        </div>
      </div>

      <!-- Conversation Flow -->
      <div class="bg-white rounded-lg border p-6">
        <h2 class="text-xl font-semibold mb-4">Conversation Flow</h2>
        
        <div class="p-6">
          <!-- Transfer Conditions -->
          <div>
            <Label class="text-base font-medium">Transfer to Human Conditions</Label>
            <p class="text-sm text-muted-foreground mb-3">
              When should the agent transfer the call to a human agent?
            </p>
            <div class="space-y-2">
              <div class="flex items-center space-x-2">
                <input
                  id="transfer_request"
                  v-model="transferConditions.request"
                  type="checkbox"
                  class="rounded"
                />
                <Label for="transfer_request">Customer requests to speak with a human</Label>
              </div>
              <div class="flex items-center space-x-2">
                <input
                  id="transfer_complex"
                  v-model="transferConditions.complex"
                  type="checkbox"
                  class="rounded"
                />
                <Label for="transfer_complex">Complex questions or complaints</Label>
              </div>
              <div class="flex items-center space-x-2">
                <input
                  id="transfer_timeout"
                  v-model="transferConditions.timeout"
                  type="checkbox"
                  class="rounded"
                />
                <Label for="transfer_timeout">After 3 failed response attempts</Label>
              </div>
            </div>
          </div>

          <!-- Custom Instructions -->
          <div class="space-y-2">
            <Label for="custom_instructions">Custom Instructions</Label>
            <Textarea
              id="custom_instructions"
              v-model="customInstructions"
              placeholder="Additional specific instructions for this agent..."
              rows="4"
            />
            <p class="text-sm text-muted-foreground">
              Any specific rules, responses, or behaviors unique to this agent
            </p>
          </div>
        </div>
      </div>

      <!-- ElevenLabs Integration (Super Admin Only) -->
      <div v-if="canConnectElevenLabs" class="bg-white rounded-lg border p-6">
        <h2 class="text-xl font-semibold mb-4">ElevenLabs Integration</h2>
        
        <div class="space-y-4">
          <p class="text-sm text-muted-foreground">
            Connect this agent to an existing ElevenLabs conversational AI agent for advanced capabilities.
          </p>
          
          <Button 
            @click="showElevenLabsDialog = true" 
            type="button" 
            variant="outline" 
            class="w-full sm:w-auto"
          >
            <Zap class="w-4 h-4 mr-2" />
            Connect to ElevenLabs Agent
          </Button>
          
          <div v-if="form.is_elevenlabs_connected" class="flex items-center space-x-2 text-green-600">
            <CheckCircle class="w-4 h-4" />
            <span class="text-sm">Connected to ElevenLabs Agent: {{ form.elevenlabs_agent_id }}</span>
          </div>
        </div>
      </div>

      <!-- Status -->
      <div class="bg-white rounded-lg border p-6">
        <h2 class="text-xl font-semibold mb-4">Agent Status</h2>
        
        <div class="flex items-center space-x-2">
          <input
            id="is_active"
            v-model="form.is_active"
            type="checkbox"
            class="rounded"
          />
          <Label for="is_active">
            Activate agent immediately after creation
          </Label>
        </div>
      </div>

      <!-- Submit Actions -->
      <div class="flex items-center justify-between">
        <Link :href="route('agents.index')" as="button">
          <Button type="button" variant="outline">
            Cancel
          </Button>
        </Link>
        
        <div class="flex space-x-3">
          <Button @click="saveAndTest" type="button" variant="outline" :disabled="processing">
            Save & Test
          </Button>
          <Button type="submit" :disabled="processing">
            <Bot class="w-4 h-4 mr-2" />
            Create Agent
          </Button>
        </div>
      </div>
    </form>
      </div>
    </div>

    <!-- ElevenLabs Connection Dialog -->
    <ElevenLabsConnectionDialog 
      v-model:open="showElevenLabsDialog"
      :agent-id="0"
      :eleven-labs-agents="reactiveElevenLabsAgents"
      @connected="handleElevenLabsConnection"
      @refresh-agents="handleRefreshAgents"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
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
  ArrowLeft,
  Bot,
  Play,
  Zap,
  CheckCircle,
} from 'lucide-vue-next'
import ElevenLabsConnectionDialog from '@/components/ElevenLabsConnectionDialog.vue'

interface Voice {
  id: string
  name: string
  language: string
  gender: string
}

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

const props = defineProps<{
  voices: Voice[]
  canConnectElevenLabs?: boolean
  elevenLabsAgents?: ElevenLabsAgent[]
}>()

// Create a reactive copy of ElevenLabs agents that can be updated
const reactiveElevenLabsAgents = ref<ElevenLabsAgent[]>(props.elevenLabsAgents || [])

// Form state
const form = useForm({
  name: '',
  description: '',
  role: '',
  tone: 'professional',
  persona: '',
  voice_id: '',
  language: 'en',
  voice_settings: {
    speed: 'normal',
    pitch: 'normal',
    stability: 'balanced',
  },
  system_prompt: '',
  greeting_message: '',
  closing_message: '',
  transfer_conditions: [] as string[],
  conversation_flow: {} as Record<string, any>,
  scripts: {} as Record<string, string>,
  settings: {} as Record<string, any>,
  is_active: true,
  elevenlabs_agent_id: '',
  is_elevenlabs_connected: false,
})

// Additional reactive state
const transferConditions = reactive({
  request: false,
  complex: false,
  timeout: false,
})

const customInstructions = ref('')
const showElevenLabsDialog = ref(false)
const breadcrumbs = [
  { title: 'Agents', href: '/agents' },
  { title: 'Create', href: '' }
]

// Computed
const processing = computed(() => form.processing)

// Methods
function handleElevenLabsConnection(agentId: string) {
  form.elevenlabs_agent_id = agentId
  form.is_elevenlabs_connected = true
  showElevenLabsDialog.value = false
}

async function handleRefreshAgents(freshAgents: ElevenLabsAgent[]) {
  try {
    // Update the reactive agents list directly
    reactiveElevenLabsAgents.value = freshAgents
  } catch (error) {
    console.error('Failed to refresh ElevenLabs agents:', error)
  }
}
const errors = computed(() => form.errors)

// Methods
const submit = () => {
  // Prepare transfer conditions
  const conditions = []
  if (transferConditions.request) conditions.push('customer_request')
  if (transferConditions.complex) conditions.push('complex_query')
  if (transferConditions.timeout) conditions.push('timeout')
  
  form.transfer_conditions = conditions
  
  // Add custom instructions to conversation flow
  if (customInstructions.value) {
    form.conversation_flow.custom_instructions = customInstructions.value
  }

  form.post(route('agents.store'), {
    onSuccess: () => {
      // Will redirect to agent show page
    },
  })
}

const saveAndTest = () => {
  // First save the agent, then redirect to test page
  const testForm = useForm({ ...form.data() })
  
  // Prepare transfer conditions
  const conditions = []
  if (transferConditions.request) conditions.push('customer_request')
  if (transferConditions.complex) conditions.push('complex_query')
  if (transferConditions.timeout) conditions.push('timeout')
  
  testForm.transfer_conditions = conditions
  
  if (customInstructions.value) {
    testForm.conversation_flow.custom_instructions = customInstructions.value
  }

  testForm.post(route('agents.store'), {
    onSuccess: (page) => {
      // Redirect to agent test page after creation
      // You could create a test route that immediately opens test dialog
    },
  })
}

const testVoice = () => {
  if (!form.voice_id) {
    alert('Please select a voice first')
    return
  }

  // This would integrate with ElevenLabs API to play a voice sample
  // For now, show a placeholder message
  alert('Voice test would play here with the selected voice and settings')
}
</script>
