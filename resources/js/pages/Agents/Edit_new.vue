<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${agent.name}`" />
    
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center space-x-4">
            <Link :href="route('agents.show', agent.id)" as="button">
                <Button variant="ghost" size="sm">
                <ArrowLeft class="w-4 h-4 mr-2" />
                Back to Agent
                </Button>
            </Link>
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Edit {{ agent.name }}</h1>
                <p class="text-muted-foreground">
                Modify your AI agent's configuration and behavior
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
                
                <div class="space-y-6">
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

                <div class="mt-6 flex space-x-3">
                <Button @click="testVoice" type="button" variant="outline">
                    <Play class="w-4 h-4 mr-2" />
                    Test Voice
                </Button>
                <Button @click="previewChanges" type="button" variant="outline">
                    <Eye class="w-4 h-4 mr-2" />
                    Preview Changes
                </Button>
                </div>
            </div>

            <!-- Conversation Flow -->
            <div class="bg-white rounded-lg border p-6">
                <h2 class="text-xl font-semibold mb-4">Conversation Flow</h2>
                
                <div class="space-y-6">
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

            <!-- Status -->
            <div class="bg-white rounded-lg border p-6">
                <h2 class="text-xl font-semibold mb-4">Agent Status</h2>
                
                <div class="space-y-4">
                <div class="flex items-center space-x-2">
                    <input
                    id="is_active"
                    v-model="form.is_active"
                    type="checkbox"
                    class="rounded"
                    />
                    <Label for="is_active">
                    Agent is active and can be used in campaigns
                    </Label>
                </div>
                
                <div v-if="!form.is_active" class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex">
                    <AlertTriangle class="w-5 h-5 text-yellow-600 mt-0.5" />
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Agent Deactivated</h3>
                        <p class="text-sm text-yellow-700 mt-1">
                        This agent won't be available for new campaigns when deactivated. 
                        Existing campaigns will continue to work.
                        </p>
                    </div>
                    </div>
                </div>
                </div>
            </div>

            <!-- Submit Actions -->
            <div class="flex items-center justify-between">
                <div class="flex space-x-3">
                <Link :href="route('agents.show', agent.id)" as="button">
                    <Button type="button" variant="outline">
                    Cancel
                    </Button>
                </Link>
                <Button @click="resetForm" type="button" variant="outline">
                    Reset Changes
                </Button>
                </div>
                
                <div class="flex space-x-3">
                <Button @click="saveAndTest" type="button" variant="outline" :disabled="processing">
                    Save & Test
                </Button>
                <Button type="submit" :disabled="processing">
                    <Save class="w-4 h-4 mr-2" />
                    {{ processing ? 'Saving...' : 'Save Changes' }}
                </Button>
                </div>
            </div>
            </form>

            <!-- Danger Zone -->
            <div class="bg-white rounded-lg border border-red-200">
            <div class="px-6 py-4 border-b border-red-200">
                <h2 class="text-lg font-semibold text-red-800">Danger Zone</h2>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-red-800">Delete Agent</h3>
                    <p class="text-sm text-red-600">
                    Permanently delete this agent. This action cannot be undone.
                    </p>
                </div>
                <Button @click="deleteAgent" variant="destructive">
                    <Trash2 class="w-4 h-4 mr-2" />
                    Delete Agent
                </Button>
                </div>
            </div>
            </div>
        </div>
    </AppLayout>    
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  AlertTriangle,
  ArrowLeft,
  Eye,
  Play,
  Save,
  Trash2,
} from 'lucide-vue-next'

interface Agent {
  id: number
  name: string
  description?: string
  role?: string
  tone?: string
  persona?: string
  voice_id: string
  language?: string
  voice_settings?: {
    speed?: string
    pitch?: string
    stability?: string
  }
  system_prompt?: string
  greeting_message?: string
  closing_message?: string
  transfer_conditions?: string[]
  conversation_flow?: {
    custom_instructions?: string
  }
  scripts?: Record<string, string>
  settings?: Record<string, any>
  is_active: boolean
}

interface Voice {
  id: string
  name: string
  language: string
  gender: string
}

const props = defineProps<{
  agent: Agent
  voices: Voice[]
}>()

// Form state
const form = useForm({
  name: props.agent.name,
  description: props.agent.description || '',
  role: props.agent.role || '',
  tone: props.agent.tone || 'professional',
  persona: props.agent.persona || '',
  voice_id: props.agent.voice_id,
  language: props.agent.language || 'en',
  voice_settings: {
    speed: props.agent.voice_settings?.speed || 'normal',
    pitch: props.agent.voice_settings?.pitch || 'normal',
    stability: props.agent.voice_settings?.stability || 'balanced',
  },
  system_prompt: props.agent.system_prompt || '',
  greeting_message: props.agent.greeting_message || '',
  closing_message: props.agent.closing_message || '',
  transfer_conditions: props.agent.transfer_conditions || [],
  conversation_flow: props.agent.conversation_flow || {},
  scripts: props.agent.scripts || {},
  settings: props.agent.settings || {},
  is_active: props.agent.is_active,
})

// Additional reactive state
const transferConditions = reactive({
  request: false,
  complex: false,
  timeout: false,
})

const customInstructions = ref('')

// Original form data for reset functionality
const originalFormData = ref({})

// Computed
const processing = computed(() => form.processing)
const errors = computed(() => form.errors)

// Breadcrumbs
const breadcrumbs = [
  { name: 'Agents', href: route('agents.index') },
  { name: props.agent.name, href: route('agents.show', props.agent.id) },
  { name: 'Edit', href: '#' },
]

// Initialize transfer conditions and custom instructions
onMounted(() => {
  // Store original form data
  originalFormData.value = { ...form.data() }
  
  // Initialize transfer conditions
  if (props.agent.transfer_conditions) {
    transferConditions.request = props.agent.transfer_conditions.includes('customer_request')
    transferConditions.complex = props.agent.transfer_conditions.includes('complex_query')
    transferConditions.timeout = props.agent.transfer_conditions.includes('timeout')
  }
  
  // Initialize custom instructions
  if (props.agent.conversation_flow?.custom_instructions) {
    customInstructions.value = props.agent.conversation_flow.custom_instructions
  }
})

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

  form.put(route('agents.update', props.agent.id), {
    onSuccess: () => {
      // Will redirect to agent show page
    },
  })
}

const saveAndTest = () => {
  // First save the agent, then open test dialog
  const conditions = []
  if (transferConditions.request) conditions.push('customer_request')
  if (transferConditions.complex) conditions.push('complex_query')
  if (transferConditions.timeout) conditions.push('timeout')
  
  form.transfer_conditions = conditions
  
  if (customInstructions.value) {
    form.conversation_flow.custom_instructions = customInstructions.value
  }

  form.put(route('agents.update', props.agent.id), {
    onSuccess: () => {
      // Redirect to agent show page with test dialog
      router.visit(route('agents.show', props.agent.id) + '?test=true')
    },
  })
}

const resetForm = () => {
  if (confirm('Are you sure you want to reset all changes? This will revert to the last saved version.')) {
    // Reset form to original data
    Object.assign(form, originalFormData.value)
    
    // Reset transfer conditions
    transferConditions.request = props.agent.transfer_conditions?.includes('customer_request') || false
    transferConditions.complex = props.agent.transfer_conditions?.includes('complex_query') || false
    transferConditions.timeout = props.agent.transfer_conditions?.includes('timeout') || false
    
    // Reset custom instructions
    customInstructions.value = props.agent.conversation_flow?.custom_instructions || ''
  }
}

const testVoice = () => {
  if (!form.voice_id) {
    alert('Please select a voice first')
    return
  }

  // This would integrate with ElevenLabs API to play a voice sample
  alert('Voice test would play here with the selected voice and settings')
}

const previewChanges = () => {
  // Show a preview of what the agent would say with current settings
  const preview = {
    greeting: form.greeting_message || 'No greeting message set',
    voice: props.voices.find(v => v.id === form.voice_id)?.name || 'Unknown voice',
    settings: form.voice_settings,
  }
  
  alert(`Preview:\nVoice: ${preview.voice}\nGreeting: "${preview.greeting}"\nSettings: ${JSON.stringify(preview.settings, null, 2)}`)
}

const deleteAgent = () => {
  if (confirm(`Are you sure you want to delete "${props.agent.name}"? This action cannot be undone and will affect any campaigns using this agent.`)) {
    router.delete(route('agents.destroy', props.agent.id))
  }
}
</script>
