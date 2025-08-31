<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="w-[80vw] sm:w-[80vw] md:w-[80vw] lg:w-[80vw] xl:w-[80vw] max-w-none sm:max-w-none md:max-w-none lg:max-w-none xl:max-w-none max-h-[95vh] overflow-hidden bg-gradient-to-br from-slate-50 to-blue-50 border-0 shadow-2xl">
      <!-- Header with ElevenLabs-style gradient -->
      <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 -m-6 mb-6 p-6 text-white">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold flex items-center space-x-3">
            <div class="w-3 h-3 bg-white rounded-full animate-pulse"></div>
            <span>Test Agent: {{ agentName }}</span>
          </DialogTitle>
          <DialogDescription class="text-blue-100 mt-2">
            Configure variables and test your conversational AI agent in real-time
          </DialogDescription>
        </DialogHeader>
      </div>

      <div class="space-y-8 p-2 overflow-y-auto max-h-[calc(95vh-300px)]">
        <!-- Variables Configuration Section -->
        <div v-if="variables.length > 0" class="space-y-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2.016 2.016 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
              </div>
              <div>
                <h3 class="text-xl font-bold text-slate-800">Custom Variables</h3>
                <p class="text-sm text-slate-600">{{ variables.length }} variables detected in your prompts</p>
              </div>
            </div>
            <Badge class="bg-gradient-to-r from-blue-500 to-purple-500 text-white border-0">
              {{ variables.length }} variables
            </Badge>
          </div>
          
          <!-- Variables Grid with ElevenLabs-style cards -->
          <div class="space-y-4">
            <div
              v-for="variable in variables"
              :key="variable"
              class="group relative bg-white/70 backdrop-blur-sm border border-white/50 rounded-xl p-5 hover:bg-white/90 hover:shadow-lg transition-all duration-300"
            >
              <div class="absolute inset-0 bg-gradient-to-r from-blue-500/5 to-purple-500/5 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
              
              <div class="relative flex items-center space-x-4">
                <!-- Variable Label (20% width) -->
                <div class="w-1/7 min-w-0">
                  <Label :for="`var-${variable}`" class="text-sm font-semibold text-slate-700 mb-2 block">
                    {{ formatVariableName(variable) }}
                  </Label>
                  <div class="text-xs text-slate-500 flex items-center space-x-1">
                    <span class="text-slate-400">&#123;&#123;</span>
                    <span class="text-blue-600 font-mono">{{ variable }}</span>
                    <span class="text-slate-400">&#125;&#125;</span>
                  </div>
                </div>
                
                <!-- Input Field (80% width) -->
                <div class="w-4/5">
                  <Input
                    :id="`var-${variable}`"
                    v-model="variableValues[variable]"
                    :placeholder="`Enter ${formatVariableName(variable).toLowerCase()}...`"
                    class="w-full border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 bg-white/50"
                  />
                </div>
              </div>
            </div>
          </div>
          
          <div class="flex items-center space-x-2 text-sm text-slate-600 bg-blue-50 p-4 rounded-lg border border-blue-200">
            <Info class="w-4 h-4 text-blue-500 flex-shrink-0" />
            <span>Variables will be dynamically replaced in your system prompt and greeting message during the conversation.</span>
          </div>
        </div>

        <!-- No Variables State -->
        <div v-else class="text-center py-12">
          <div class="w-16 h-16 bg-gradient-to-r from-slate-200 to-slate-300 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2.016 2.016 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-slate-700 mb-2">No Custom Variables Found</h3>
          <p class="text-slate-500 mb-4">Add variables to your prompts using the &#123;&#123;variable_name&#125;&#125; syntax to see them here.</p>
        </div>

        <!-- Preview Section with ElevenLabs-style cards -->
        <div v-if="previewData" class="space-y-6">
          <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg flex items-center justify-center">
              <Eye class="w-4 h-4 text-white" />
            </div>
            <h3 class="text-xl font-bold text-slate-800">Preview with Variables</h3>
          </div>
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white/70 backdrop-blur-sm border border-white/50 rounded-xl p-6 hover:bg-white/90 transition-all duration-300">
              <div class="flex items-center space-x-2 mb-4">
                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                <Label class="text-sm font-semibold text-slate-700">System Prompt Preview</Label>
              </div>
              <div class="p-4 bg-slate-50 rounded-lg border text-sm text-slate-700 leading-relaxed max-h-48 overflow-y-auto">
                {{ previewData.processedSystemPrompt || 'No system prompt configured' }}
              </div>
            </div>
            
            <div class="bg-white/70 backdrop-blur-sm border border-white/50 rounded-xl p-6 hover:bg-white/90 transition-all duration-300">
              <div class="flex items-center space-x-2 mb-4">
                <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                <Label class="text-sm font-semibold text-slate-700">Greeting Message Preview</Label>
              </div>
              <div class="p-4 bg-slate-50 rounded-lg border text-sm text-slate-700 leading-relaxed max-h-48 overflow-y-auto">
                {{ previewData.processedGreeting || 'No greeting message configured' }}
              </div>
            </div>
          </div>
        </div>

        <!-- Test Result with enhanced styling -->
        <div v-if="testResult" class="space-y-6">
          <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg flex items-center justify-center">
              <CheckCircle class="w-4 h-4 text-white" />
            </div>
            <h3 class="text-xl font-bold text-slate-800">Test Completed</h3>
          </div>
          
          <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center space-x-3">
                <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                <span class="text-sm font-semibold text-green-800">Agent Response Generated</span>
              </div>
              <Badge class="bg-green-100 text-green-800 border-green-200">
                {{ testResult.processing_time }}s
              </Badge>
            </div>
            
            <div class="space-y-4">
              <div class="p-4 bg-white/70 rounded-lg border border-green-200">
                <p class="text-sm text-green-800 leading-relaxed">
                  <strong class="text-green-900">Response:</strong> {{ testResult.message }}
                </p>
              </div>
              
              <div v-if="testResult.voice_sample_url" class="p-4 bg-white/70 rounded-lg border border-green-200">
                <Label class="text-sm font-semibold text-green-800 mb-2 block">Voice Sample</Label>
                <audio controls class="w-full bg-white rounded-lg">
                  <source :src="testResult.voice_sample_url" type="audio/mpeg">
                  Your browser does not support the audio element.
                </audio>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Footer with ElevenLabs-style actions -->
      <DialogFooter class="flex items-center justify-between p-6 -m-6 mt-6 bg-white/50 backdrop-blur-sm border-t border-white/50">
        <div class="flex items-center space-x-3">
          <Button 
            @click="loadVariables" 
            variant="outline" 
            size="sm" 
            :disabled="loadingVariables"
            class="border-slate-300 hover:bg-slate-50 text-slate-700"
          >
            <RefreshCw :class="['w-4 h-4 mr-2', { 'animate-spin': loadingVariables }]" />
            Refresh Variables
          </Button>
          <Button 
            @click="previewVariables" 
            variant="outline" 
            size="sm" 
            :disabled="!hasVariables"
            class="border-blue-300 hover:bg-blue-50 text-blue-700"
          >
            <Eye class="w-4 h-4 mr-2" />
            Preview
          </Button>
        </div>
        
        <div class="flex items-center space-x-3">
          <Button 
            @click="isOpen = false" 
            variant="outline"
            class="border-slate-300 hover:bg-slate-50 text-slate-700"
          >
            Cancel
          </Button>
          <Button 
            @click="startCall" 
            :disabled="testing"
            class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white border-0 shadow-lg hover:shadow-xl transition-all duration-200"
          >
            <Loader2 v-if="testing" class="mr-2 h-4 w-4 animate-spin" />
            <svg v-else class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21L6.5 11c-.5.3-.5 1.5 2 4s3.7 2.5 4 2l1.613-3.724a1 1 0 011.21-.502l4.493 1.498A1 1 0 0120 14.72V18a2 2 0 01-2 2h-2c-8.284 0-15-6.716-15-15V5z"></path>
            </svg>
            {{ testing ? 'Starting Call...' : 'Start Call Test' }}
          </Button>
        </div>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { 
  Loader2, 
  RefreshCw, 
  Eye, 
  Play, 
  CheckCircle, 
  Info 
} from 'lucide-vue-next'

interface Props {
  open: boolean
  agentId: number
  agentName: string
}

interface Emits {
  (e: 'update:open', value: boolean): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value)
})

// State
const testMessage = ref('')
const variables = ref<string[]>([])
const variableValues = ref<Record<string, string>>({})
const loadingVariables = ref(false)
const testing = ref(false)
const testResult = ref<any>(null)
const previewData = ref<any>(null)

// Computed
const hasVariables = computed(() => variables.value.length > 0)

// Watch for dialog open/close
watch(isOpen, (newValue) => {
  if (newValue) {
    loadVariables()
    testResult.value = null
    previewData.value = null
  } else {
    // Reset form
    // testMessage.value = ''
    variableValues.value = {}
    testResult.value = null
    previewData.value = null
  }
})

// Methods
function formatVariableName(variable: string): string {
  return variable
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join(' ')
}

async function loadVariables() {
  loadingVariables.value = true
  try {
    const response = await fetch(`/agents/${props.agentId}/variables`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    const data = await response.json()
    
    if (data.success) {
      // Handle both array and object formats
      let variablesData = data.variables || []
      
      // If variables is an object with numeric keys, convert to array
      if (variablesData && typeof variablesData === 'object' && !Array.isArray(variablesData)) {
        variablesData = Object.values(variablesData)
      }
      
      variables.value = Array.isArray(variablesData) ? variablesData : []
      
      // Initialize variable values - ensure variables.value is an array
      if (Array.isArray(variables.value)) {
        variables.value.forEach(variable => {
          if (!variableValues.value[variable]) {
            variableValues.value[variable] = ''
          }
        })
      }
    } else {
      variables.value = []
    }
  } catch (error) {
    console.error('Failed to load variables:', error)
    variables.value = [] // Ensure variables is always an array
  } finally {
    loadingVariables.value = false
  }
}

async function previewVariables() {
  try {
    const response = await fetch(`/agents/${props.agentId}/test`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        test_message: 'preview',
        variables: variableValues.value,
        preview_only: true
      })
    })
    
    const data = await response.json()
    previewData.value = data
  } catch (error) {
    console.error('Failed to preview variables:', error)
  }
}

function runTest() {
//   if (!testMessage.value.trim()) return

  testing.value = true
  testResult.value = null

  router.post(`/agents/${props.agentId}/test`, {
    // test_message: testMessage.value,
    variables: variableValues.value
  }, {
    onSuccess: (page: any) => {
      // Get test result from session flash data
      testResult.value = page.props.flash?.test_result || null
    },
    onError: (errors) => {
      console.error('Test failed:', errors)
    },
    onFinish: () => {
      testing.value = false
    }
  })
}

function startCall() {
  // Set testing state
  testing.value = true
  
  // Prepare call URL with variables
  const callUrl = `/agents/${props.agentId}/call-test`
  const params = new URLSearchParams()
  
  // Add variables as query parameters
  Object.entries(variableValues.value).forEach(([key, value]) => {
    if (value.trim()) {
      params.append(`variables[${key}]`, value)
    }
  })
  
  const fullUrl = params.toString() ? `${callUrl}?${params.toString()}` : callUrl
  
  // Open in new window/tab
  const callWindow = window.open(fullUrl, '_blank', 'width=800,height=600,menubar=no,toolbar=no,location=no,status=no,scrollbars=no,resizable=yes')
  
  // Reset testing state after a short delay
  setTimeout(() => {
    testing.value = false
  }, 2000)
  
  // Optional: Close dialog after opening call
  // isOpen.value = false
}
</script>
