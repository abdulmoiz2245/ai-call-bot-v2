<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900">
    <!-- Header -->
    <div class="bg-white/10 backdrop-blur-sm border-b border-white/20 p-6">
      <div class="max-w-4xl mx-auto flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <Button
            @click="goBack"
            variant="ghost"
            size="sm"
            class="text-white hover:bg-white/20"
          >
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Agents
          </Button>
          
          <div class="h-8 w-px bg-white/30"></div>
          
          <div>
            <h1 class="text-2xl font-bold text-white">{{ agent.name }}</h1>
            <p class="text-blue-200 text-sm">{{ agent.company_name }} • Voice Agent Testing</p>
          </div>
        </div>
        
        <div class="flex items-center space-x-3">
          <div class="flex items-center space-x-2 text-white/70">
            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
            <span class="text-sm">Ready to Test</span>
          </div>
          
          <Badge 
            :class="agent.is_elevenlabs_connected ? 'bg-green-500/20 text-green-300 border-green-500/30' : 'bg-orange-500/20 text-orange-300 border-orange-500/30'"
          >
            {{ agent.is_elevenlabs_connected ? 'Laravel Reverb' : 'Custom WebSocket' }}
          </Badge>
        </div>
      </div>
    </div>
  

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto p-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Agent Configuration Panel -->
        <div class="lg:col-span-1 space-y-6">
          
          <!-- Agent Info Card -->
          <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
              <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              Agent Details
            </h3>
            
            <div class="space-y-3">
              <div>
                <Label class="text-blue-200 text-sm">Description</Label>
                <p class="text-white/90 text-sm mt-1">{{ agent.description || 'No description provided' }}</p>
              </div>
              
              <div>
                <Label class="text-blue-200 text-sm">Voice ID</Label>
                <p class="text-white/90 text-sm mt-1 font-mono bg-white/10 px-2 py-1 rounded">{{ agent.voice_id }}</p>
              </div>
              
              <div v-if="hasVariables">
                <Label class="text-blue-200 text-sm">Active Variables</Label>
                <div class="flex flex-wrap gap-2 mt-2">
                  <Badge 
                    v-for="(value, key) in variables" 
                    :key="key"
                    class="bg-blue-500/20 text-blue-300 border-blue-500/30 text-xs"
                  >
                    {{ key }}: {{ value }}
                  </Badge>
                </div>
              </div>
            </div>
          </div>

          <!-- System Prompt Preview -->
          <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
              <div class="w-8 h-8 bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </div>
              System Prompt
            </h3>
            
            <div class="bg-black/30 rounded-lg p-4 max-h-48 overflow-y-auto">
              <pre class="text-blue-100 text-sm whitespace-pre-wrap">{{ agent.system_prompt || 'No system prompt configured' }}</pre>
            </div>
          </div>

          <!-- Greeting Message Preview -->
          <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
              <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                </svg>
              </div>
              Greeting Message
            </h3>
            
            <div class="bg-black/30 rounded-lg p-4">
              <p class="text-blue-100 text-sm">{{ agent.greeting_message || 'No greeting message configured' }}</p>
            </div>
          </div>

        </div>

        <!-- Call Interface Panel -->
        <div class="lg:col-span-2">
          
          <!-- Call Control Center -->
          <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-8">
            <div class="text-center space-y-8">
              
              <!-- Call Status Display -->
              <div class="space-y-4">
                <div class="w-32 h-32 mx-auto relative">
                  <div 
                    class="w-full h-full rounded-full border-4 border-dashed transition-all duration-1000"
                    :class="callStatus === 'connected' ? 'border-green-400 animate-spin' : 
                           callStatus === 'connecting' ? 'border-yellow-400 animate-pulse' : 
                           'border-white/40'"
                  >
                    <div 
                      class="w-full h-full rounded-full flex items-center justify-center transition-all duration-500"
                      :class="callStatus === 'connected' ? 'bg-green-500/20' : 
                             callStatus === 'connecting' ? 'bg-yellow-500/20' : 
                             'bg-white/10 hover:bg-white/20'"
                    >
                      <svg 
                        class="w-12 h-12 transition-all duration-300"
                        :class="callStatus === 'connected' ? 'text-green-400' : 
                               callStatus === 'connecting' ? 'text-yellow-400' : 
                               'text-white'"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                      >
                        <path 
                          v-if="callStatus === 'idle'"
                          stroke-linecap="round" 
                          stroke-linejoin="round" 
                          stroke-width="2" 
                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21L6.5 11c-.5.3-.5 1.5 2 4s3.7 2.5 4 2l1.613-3.724a1 1 0 011.21-.502l4.493 1.498A1 1 0 0120 14.72V18a2 2 0 01-2 2h-2c-8.284 0-15-6.716-15-15V5z"
                        />
                        <path 
                          v-else-if="callStatus === 'connecting'"
                          stroke-linecap="round" 
                          stroke-linejoin="round" 
                          stroke-width="2" 
                          d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"
                        />
                        <path 
                          v-else
                          stroke-linecap="round" 
                          stroke-linejoin="round" 
                          stroke-width="2" 
                          d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 12l.01.01"
                        />
                      </svg>
                    </div>
                  </div>
                </div>

                <div class="space-y-2">
                  <h2 class="text-2xl font-bold text-white">{{ statusTitle }}</h2>
                  <p class="text-blue-200">{{ statusDescription }}</p>
                  <div v-if="callDuration > 0" class="text-green-400 font-mono text-lg">
                    {{ formatDuration(callDuration) }}
                  </div>
                </div>
              </div>

              <!-- Call Controls -->
              <div class="flex justify-center space-x-6">
                <Button
                  v-if="callStatus === 'idle'"
                  @click="startCall"
                  :disabled="!canStartCall"
                  class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white border-0 shadow-lg hover:shadow-xl transition-all duration-200 px-8 py-4 text-lg"
                >
                  <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21L6.5 11c-.5.3-.5 1.5 2 4s3.7 2.5 4 2l1.613-3.724a1 1 0 011.21-.502l4.493 1.498A1 1 0 0120 14.72V18a2 2 0 01-2 2h-2c-8.284 0-15-6.716-15-15V5z"></path>
                  </svg>
                  Start Call
                </Button>

                <Button
                  v-else-if="callStatus === 'connecting'"
                  @click="cancelCall"
                  variant="outline"
                  class="border-red-400 text-red-400 hover:bg-red-400/10 px-8 py-4 text-lg"
                >
                  <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                  Cancel
                </Button>

                <Button
                  v-else-if="callStatus === 'connected'"
                  @click="endCall"
                  class="bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white border-0 shadow-lg hover:shadow-xl transition-all duration-200 px-8 py-4 text-lg"
                >
                  <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 3l1.5 1.5M4.5 4.5l.5.5m0 0V9a6 6 0 006 6h4.5M9 15l.5.5m.5-.5h.5m2.5-2.5L15 15"></path>
                  </svg>
                  End Call
                </Button>
              </div>

              <!-- Connection Info -->
              <div v-if="connectionInfo" class="bg-black/30 rounded-lg p-4 text-left">
                <h4 class="text-white font-semibold mb-2">Connection Details</h4>
                <div class="space-y-1 text-sm text-blue-200">
                  <div v-if="connectionInfo.platform">Platform: {{ connectionInfo.platform }}</div>
                  <div v-if="connectionInfo.latency">Latency: {{ connectionInfo.latency }}ms</div>
                  <div v-if="connectionInfo.quality">Quality: {{ connectionInfo.quality }}</div>
                  <div v-if="connectionInfo.conversation_id">Session: {{ connectionInfo.conversation_id }}</div>
                </div>
              </div>

              <!-- Microphone Status -->
              <div v-if="callStatus === 'connected'" class="flex items-center justify-center space-x-4 text-white/70">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                  <span class="text-sm">Microphone Active</span>
                </div>
                <div class="h-4 w-px bg-white/30"></div>
                <div class="flex items-center space-x-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                  </svg>
                  <span class="text-sm">Speak now</span>
                </div>
              </div>

            </div>
          </div>

        </div>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onUnmounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { ArrowLeft } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'
import echo from '@/echo'

interface Props {
  agent: {
    id: number
    name: string
    description: string
    voice_id: string
    company_name: string
    system_prompt: string
    greeting_message: string
    is_elevenlabs_connected: boolean
    elevenlabs_agent_id?: string
    variables: Record<string, string>
  }
  variables: Record<string, string>
}

const props = defineProps<Props>()

// State
const callStatus = ref<'idle' | 'connecting' | 'connected' | 'ended'>('idle')
const callDuration = ref(0)
const connectionInfo = ref<any>(null)
const callInterval = ref<number | null>(null)
const mediaRecorder = ref<MediaRecorder | null>(null)
const audioStream = ref<MediaStream | null>(null)
const sessionId = ref<string | null>(null)
const channelName = ref<string | null>(null)

// WebSocket channels
let regularChannel: any = null
let privateAudioChannel: any = null

// Computed
const hasVariables = computed(() => Object.keys(props.variables).length > 0)

const canStartCall = computed(() => {
  return callStatus.value === 'idle'
})

const statusTitle = computed(() => {
  switch (callStatus.value) {
    case 'idle': return 'Ready to Start Call'
    case 'connecting': return 'Connecting...'
    case 'connected': return 'Call in Progress'
    case 'ended': return 'Call Ended'
    default: return 'Unknown Status'
  }
})

const statusDescription = computed(() => {
  switch (callStatus.value) {
    case 'idle': return 'Click Start Call to begin testing via WebSocket audio streaming'
    case 'connecting': return 'Establishing WebSocket connection...'
    case 'connected': return 'Connected via WebSocket - Speak to test your agent'
    case 'ended': return 'WebSocket connection closed'
    default: return ''
  }
})

// Methods
function goBack() {
  router.visit('/agents')
}

function startCall() {
  callStatus.value = 'connecting'
  
  // Request microphone access first
  initializeAudio().then(() => {
    startWebSocketCall()
  }).catch((error) => {
    console.error('Failed to initialize audio:', error)
    callStatus.value = 'idle'
    alert('Microphone access is required for voice calls. Please allow microphone access and try again.')
  })
}

async function initializeAudio() {
  try {
    // Request microphone access
    audioStream.value = await navigator.mediaDevices.getUserMedia({ 
      audio: {
        echoCancellation: true,
        noiseSuppression: true,
        autoGainControl: true,
        sampleRate: 16000
      } 
    })
    
    // Set up MediaRecorder for audio streaming
    mediaRecorder.value = new MediaRecorder(audioStream.value, {
      mimeType: 'audio/webm;codecs=opus'
    })
    
    return true
  } catch (error) {
    console.error('Failed to get microphone access:', error)
    throw error
  }
}

async function startWebSocketCall() {
  try {
    console.log('Starting WebSocket call initialization...')
    
    // Initialize voice call session via Laravel API
    const response = await fetch(`/agents/${props.agent.id}/voice-call/initialize`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        variables: props.variables
      })
    })
    
    const data = await response.json()
    console.log('Initialize response:', data)
    
    if (!data.success) {
      throw new Error(data.message || 'Failed to initialize voice call session')
    }
    
    sessionId.value = data.session_id
    channelName.value = data.channel_name
    
    console.log('Session initialized:', {
      session_id: sessionId.value,
      channel_name: channelName.value
    })
    
    // Subscribe to regular channel for status updates
    if (channelName.value) {
      regularChannel = echo.channel(channelName.value)
      console.log('Subscribed to regular channel:', channelName.value)
    }
    
    // Subscribe to private audio channel for audio streaming
    if (sessionId.value) {
      const audioChannelName = `voice-call-audio.${sessionId.value}`
      privateAudioChannel = echo.private(audioChannelName)
      console.log('Subscribed to private audio channel:', audioChannelName)
    }
    
    // Listen for status updates on regular channel
    if (regularChannel) {
      regularChannel.listen('.voice.call.status', (event: any) => {
        console.log('Call status updated:', event)
        if (event.status === 'connected') {
          callStatus.value = 'connected'
          startCallTimer()
          connectionInfo.value = {
            platform: 'WebSocket Audio',
            latency: Math.floor(Math.random() * 30) + 10,
            quality: 'HD',
            session_id: sessionId.value
          }
          
          // Start streaming audio via WebSocket
          startWebSocketAudioStreaming()
        } else if (event.status === 'ended') {
          callStatus.value = 'ended'
          stopCallTimer()
          stopWebSocketAudioStreaming()
        }
      })
    }
    
    // Listen for audio responses on private audio channel
    if (privateAudioChannel) {
      privateAudioChannel.listen('.voice.call.audio', (event: any) => {
        console.log('Audio event received:', event.direction, event.timestamp)
        
        if (event.direction === 'outgoing' && event.audio_data) {
          // Play AI response audio
          playAudioFromBase64(event.audio_data)
          
          if (event.metadata?.transcript) {
            console.log('AI response:', event.metadata.transcript)
          }
        }
      })
    }
    
    // Trigger connected status
    console.log('Triggering connected status...')
    const triggerResponse = await fetch('/voice-call/trigger-connected', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        session_id: sessionId.value
      })
    })
    
    const triggerData = await triggerResponse.json()
    console.log('Trigger response:', triggerData)
    
  } catch (error) {
    console.error('Failed to start WebSocket call:', error)
    callStatus.value = 'idle'
    connectionInfo.value = null
  }
}

function startWebSocketAudioStreaming() {
  if (!mediaRecorder.value || !sessionId.value) {
    console.error('MediaRecorder or session not initialized')
    return
  }

  // Set up data available handler for WebSocket audio transmission
  mediaRecorder.value.ondataavailable = async (event) => {
    if (event.data.size > 0) {
      try {
        // Convert audio blob to base64
        const reader = new FileReader()
        reader.onload = async () => {
          const base64Audio = (reader.result as string).split(',')[1] // Remove data:audio/webm;base64, prefix
          
          // Send audio data directly to backend (more efficient than whisper roundtrip)
          await fetch('/voice-call/audio-chunk', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
              session_id: sessionId.value,
              audio_data: base64Audio,
              user_id: props.agent.id,
              timestamp: Date.now()
            })
          }).catch(error => {
            console.error('Failed to send audio to backend:', error)
          })
        }
        reader.readAsDataURL(event.data)
      } catch (error) {
        console.error('Failed to process audio data:', error)
      }
    }
  }

  // Start recording with shorter intervals for better real-time experience
  mediaRecorder.value.start(500) // Send audio chunks every 500ms
  console.log('WebSocket audio streaming started')
}

function stopWebSocketAudioStreaming() {
  if (mediaRecorder.value && mediaRecorder.value.state !== 'inactive') {
    mediaRecorder.value.stop()
  }
  
  if (audioStream.value) {
    audioStream.value.getTracks().forEach(track => track.stop())
    audioStream.value = null
  }
  
  console.log('WebSocket audio streaming stopped')
}

function cancelCall() {
  callStatus.value = 'idle'
  stopCallTimer()
  stopWebSocketAudioStreaming()
  connectionInfo.value = null
  
  // Disconnect from channels
  if (regularChannel && channelName.value) {
    echo.leave(channelName.value)
    regularChannel = null
  }
  if (privateAudioChannel && sessionId.value) {
    echo.leave(`voice-call-audio.${sessionId.value}`)
    privateAudioChannel = null
  }
}

async function endCall() {
  callStatus.value = 'ended'
  stopCallTimer()
  stopWebSocketAudioStreaming()
  
  // Send end call signal to backend
  if (sessionId.value) {
    try {
      await fetch('/voice-call/end-session', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          session_id: sessionId.value,
          reason: 'user_ended',
          save_audio: true // Enable audio saving for multi-user access
        })
      })
    } catch (error) {
      console.error('Failed to end call properly:', error)
    }
  }
  
  // Disconnect from channels
  if (regularChannel && channelName.value) {
    echo.leave(channelName.value)
    regularChannel = null
  }
  if (privateAudioChannel && sessionId.value) {
    echo.leave(`voice-call-audio.${sessionId.value}`)
    privateAudioChannel = null
  }
  
  // Reset after 3 seconds
  setTimeout(() => {
    callStatus.value = 'idle'
    callDuration.value = 0
    connectionInfo.value = null
    sessionId.value = null
    channelName.value = null
  }, 3000)
}

function startCallTimer() {
  callInterval.value = setInterval(() => {
    callDuration.value++
  }, 1000)
}

function stopCallTimer() {
  if (callInterval.value) {
    clearInterval(callInterval.value)
    callInterval.value = null
  }
}

function formatDuration(seconds: number): string {
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
}

function playAudioFromBase64(base64Audio: string) {
  try {
    // Create audio element and play base64 audio
    const audioBlob = new Blob([Uint8Array.from(atob(base64Audio), c => c.charCodeAt(0))], {
      type: 'audio/mpeg'
    })
    const audioUrl = URL.createObjectURL(audioBlob)
    const audio = new Audio(audioUrl)
    audio.play()
      .then(() => {
        console.log('AI response audio played successfully')
      })
      .catch((error) => {
        console.error('Failed to play AI response audio:', error)
      })
  } catch (error) {
    console.error('Failed to create audio from base64:', error)
  }
}

// Cleanup
onUnmounted(() => {
  stopCallTimer()
  stopWebSocketAudioStreaming()
  
  if (regularChannel && channelName.value) {
    echo.leave(channelName.value)
  }
  if (privateAudioChannel && sessionId.value) {
    echo.leave(`voice-call-audio.${sessionId.value}`)
  }
})
</script>

