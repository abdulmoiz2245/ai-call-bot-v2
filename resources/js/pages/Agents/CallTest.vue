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
        
      </div>
    </div>
  

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto p-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Agent Configuration Panel -->
        <div class="lg:col-span-1 space-y-6">

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
                  <div v-if="connectionInfo.room">Room: {{ connectionInfo.room }}</div>
                  <div v-if="connectionInfo.quality">Quality: {{ connectionInfo.quality }}</div>
                  <div v-if="connectionInfo.participants">Participants: {{ connectionInfo.participants }}</div>
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
/* =================================================================
 * COMMENTED OUT ORIGINAL WEBSOCKET CODE - REPLACED WITH LIVEKIT
 * =================================================================
 
import { ref, computed, onUnmounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { ArrowLeft } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'
import echo from '@/echo'
import { MicVAD } from "@ricky0123/vad-web"

// ... All the original WebSocket/VAD/audio processing code has been commented out ...

*/

// ===============================================
// NEW LIVEKIT INTEGRATION
// ===============================================

import { ref, computed, onUnmounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { ArrowLeft } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'
import { 
  Room, 
  RoomEvent, 
  TrackPublication,
  RemoteTrackPublication,
  RemoteAudioTrack,
  RemoteTrack,
  RemoteParticipant,
  Track,
  ConnectionState,
  DataPacket_Kind,
  Participant,
  DisconnectReason,
  createLocalAudioTrack
} from 'livekit-client'

interface Props {
  agent: {
    id: number
    name: string
    description: string
    voice_id: string
    language?: string
    company_name: string
    system_prompt: string
    greeting_message: string
    is_elevenlabs_connected: boolean
    elevenlabs_agent_id?: string
    variables: Record<string, string>
  }
  variables: Record<string, string>
  greeting_audio?: {
    audio_base64: string
    greeting_text: string
    voice_id: string
    cached: boolean
    ready: boolean
  } | null
  greeting_ready: boolean
}

const props = defineProps<Props>()

// LiveKit state
const room = ref<Room | null>(null)
const callStatus = ref<'idle' | 'connecting' | 'connected' | 'ended'>('idle')
const callDuration = ref(0)
const connectionInfo = ref<any>(null)
const callInterval = ref<number | null>(null)
const currentTranscript = ref<string>('')
const agentMessages = ref<string[]>([])

// Computed properties
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
    case 'idle': return 'Click Start Call'
    case 'connecting': return 'Establishing connection...'
    case 'connected': return 'Connected - Speak to test your agent'
    case 'ended': return 'Connection closed'
    default: return ''
  }
})

// Methods
function goBack() {
  router.visit('/agents')
}

async function startCall() {
  try {
    callStatus.value = 'connecting'
    console.log('Starting LiveKit test call...')
    
    // Start LiveKit test call via Laravel backend
    const response = await fetch(`/agents/${props.agent.id}/livekit/test-call`, {
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
    console.log('LiveKit test response:', data)
    
    if (!data.success) {
      throw new Error(data.message || 'Failed to start LiveKit test call')
    }
    
    // Connect to LiveKit room using the correct data structure
    await connectToRoom(
      data.session_data.room_name, 
      data.session_data.tokens.caller, 
      data.session_data.url
    )
    
  } catch (error) {
    console.error('Failed to start LiveKit call:', error)
    callStatus.value = 'idle'
    alert('Failed to start call: ' + (error as Error).message)
  }
}

async function connectToRoom(roomName: string, token: string, wsUrl: string) {
  try {
    // Request microphone permission first
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
      // Close the stream as we just needed permission
      stream.getTracks().forEach(track => track.stop())
      console.log('Microphone permission granted')
    } catch (permError) {
      console.error('Microphone permission denied:', permError)
      throw new Error('Microphone permission is required for voice calls')
    }

    // Create room instance with simplified options
    room.value = new Room({
      adaptiveStream: true,
      dynacast: true,
    })
    
    // Set up room event listeners
    setupRoomEventListeners()
    
    // Connect to room
    console.log('Connecting to LiveKit room:', roomName)
    await room.value.connect(wsUrl, token)
    
    console.log('Connected to LiveKit room successfully')
    
    // Try to enable microphone with a delay to avoid race conditions
    setTimeout(async () => {
      try {
        // Use a simpler approach to create and publish audio tracks
        const audioTrack = await createLocalAudioTrack({
          echoCancellation: true,
          noiseSuppression: true,
          autoGainControl: true
        })
        
        await room.value?.localParticipant.publishTrack(audioTrack)
        console.log('Microphone track created and published successfully')
        
        // Set microphone enabled
        await room.value?.localParticipant.setMicrophoneEnabled(true)
        console.log('Microphone enabled successfully')

        await room.value?.localParticipant.setMetadata(JSON.stringify({
          status: "busy",
          name: "Agent Moiz"
        }));

        
      } catch (micError) {
        console.warn('Failed to enable microphone:', micError)
        
        // Fallback: try just setting microphone enabled without custom options
        try {
          await room.value?.localParticipant.setMicrophoneEnabled(true)
          console.log('Microphone enabled with fallback method')
        } catch (fallbackError) {
          console.error('Fallback microphone enable also failed:', fallbackError)
        }
      }
    }, 500)
    
    callStatus.value = 'connected'
    startCallTimer()
    
    connectionInfo.value = {
      platform: 'LiveKit',
      room: roomName,
      quality: 'HD',
      participants: room.value.numParticipants
    }
    
  } catch (error) {
    console.error('Failed to connect to LiveKit room:', error)
    callStatus.value = 'idle'
    throw error
  }
}

function setupRoomEventListeners() {
  if (!room.value) return
  
  // Connection state changes
  room.value.on(RoomEvent.ConnectionStateChanged, (state: ConnectionState) => {
    console.log('LiveKit connection state:', state)
    
    if (state === ConnectionState.Connected) {
      console.log('LiveKit room connected')
    } else if (state === ConnectionState.Disconnected) {
      console.log('LiveKit room disconnected')
      if (callStatus.value === 'connected') {
        callStatus.value = 'ended'
        stopCallTimer()
      }
    }
  })
  
  // Participant events
  room.value.on(RoomEvent.ParticipantConnected, (participant: Participant) => {
    console.log('Participant connected:', participant.identity)
    console.log("Metadata:", participant.metadata)
    // Update connection info
    if (connectionInfo.value) {
      connectionInfo.value.participants = room.value!.numParticipants
    }
  })
  
  room.value.on(RoomEvent.ParticipantDisconnected, (participant: Participant) => {
    console.log('Participant disconnected:', participant.identity)
    
    // Update connection info
    if (connectionInfo.value) {
      connectionInfo.value.participants = room.value!.numParticipants
    }
  })
  
  // Track events
  room.value.on(RoomEvent.TrackSubscribed, (track: RemoteTrack, publication: RemoteTrackPublication, participant: RemoteParticipant) => {
    console.log('Track subscribed:', {
      kind: track.kind,
      participant: participant.identity,
      source: track.source
    })
    
    // Handle audio tracks (agent speech)
    if (track.kind === Track.Kind.Audio && track instanceof RemoteAudioTrack) {
      const audioElement = track.attach()
      audioElement.play()
      console.log('Playing agent audio track')
    }
  })
  
  room.value.on(RoomEvent.TrackUnsubscribed, (track: RemoteTrack, publication: RemoteTrackPublication, participant: RemoteParticipant) => {
    console.log('Track unsubscribed:', {
      kind: track.kind,
      participant: participant.identity
    })
  })
  
  // Data events (for transcripts and metadata)
  room.value.on(RoomEvent.DataReceived, (payload: Uint8Array, participant?: Participant, kind?: DataPacket_Kind) => {
    try {
      const data = JSON.parse(new TextDecoder().decode(payload))
      console.log('Data received:', data)
      
      if (data.type === 'transcript') {
        currentTranscript.value = data.text || ''
        console.log('User transcript:', data.text)
      } else if (data.type === 'agent_response') {
        agentMessages.value.push(data.text || '')
        console.log('Agent response:', data.text)
      } else if (data.type === 'greeting') {
        console.log('Agent greeting:', data.text)
        agentMessages.value.push(`Greeting: ${data.text}`)
      }
    } catch (error) {
      console.error('Failed to parse data message:', error)
    }
  })
  
  // Error handling
  room.value.on(RoomEvent.Disconnected, (reason?: DisconnectReason) => {
    console.log('Room disconnected:', reason)
    if (callStatus.value === 'connected') {
      callStatus.value = 'ended'
      stopCallTimer()
    }
  })
  
  room.value.on(RoomEvent.Reconnecting, () => {
    console.log('Room reconnecting...')
  })
  
  room.value.on(RoomEvent.Reconnected, () => {
    console.log('Room reconnected')
  })
}

async function endCall() {
  try {
    callStatus.value = 'ended'
    stopCallTimer()
    
    if (room.value) {
      console.log('Disconnecting from LiveKit room...')
      await room.value.disconnect()
      room.value = null
    }
    
    // Reset state after delay
    setTimeout(() => {
      callStatus.value = 'idle'
      callDuration.value = 0
      connectionInfo.value = null
      currentTranscript.value = ''
      agentMessages.value = []
    }, 3000)
    
  } catch (error) {
    console.error('Error ending call:', error)
    // Reset state anyway
    callStatus.value = 'idle'
    room.value = null
  }
}

function cancelCall() {
  endCall()
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

// Helper function to get language display name
function getLanguageName(languageCode: string): string {
  const languageNames: Record<string, string> = {
    'en': 'English',
    'es': 'Spanish',
    'fr': 'French', 
    'de': 'German',
    'it': 'Italian',
    'pt': 'Portuguese',
    'pl': 'Polish',
    'hi': 'Hindi',
    'ar': 'Arabic',
    'zh': 'Chinese',
    'ja': 'Japanese',
    'ko': 'Korean',
    'ru': 'Russian',
    'tr': 'Turkish',
    'nl': 'Dutch',
    'sv': 'Swedish',
    'da': 'Danish',
    'no': 'Norwegian',
    'fi': 'Finnish'
  }
  
  return languageNames[languageCode?.toLowerCase()] || languageCode?.toUpperCase() || 'Unknown'
}

// Cleanup
onUnmounted(() => {
  stopCallTimer()
  if (room.value) {
    room.value.disconnect()
    room.value = null
  }
})
</script>

