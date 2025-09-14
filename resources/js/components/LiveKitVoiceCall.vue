<template>
  <div class="livekit-voice-call">
    <div v-if="!isConnected" class="connection-setup">
      <h3>Voice Call Setup</h3>
      <div class="form-group">
        <label for="phone">Phone Number:</label>
        <input 
          id="phone"
          v-model="phoneNumber" 
          type="tel" 
          placeholder="+1234567890"
          class="form-control"
        >
      </div>
      <div class="form-group">
        <label for="caller">Caller Name (Optional):</label>
        <input 
          id="caller"
          v-model="callerName" 
          type="text" 
          placeholder="John Doe"
          class="form-control"
        >
      </div>
      <button 
        @click="startCall" 
        :disabled="!phoneNumber || isConnecting"
        class="btn btn-primary"
      >
        {{ isConnecting ? 'Connecting...' : 'Start Call' }}
      </button>
    </div>

    <div v-else class="call-active">
      <h3>Live Call Active</h3>
      <div class="call-info">
        <p><strong>Phone:</strong> {{ phoneNumber }}</p>
        <p><strong>Caller:</strong> {{ callerName || 'Unknown' }}</p>
        <p><strong>Agent:</strong> {{ agent.name }}</p>
        <p><strong>Language:</strong> {{ agent.language }}</p>
      </div>
      
      <div class="call-controls">
        <button @click="toggleMute" :class="['btn', isMuted ? 'btn-warning' : 'btn-secondary']">
          {{ isMuted ? 'Unmute' : 'Mute' }}
        </button>
        <button @click="endCall" class="btn btn-danger">
          End Call
        </button>
      </div>
      
      <div class="audio-levels">
        <div class="level-meter">
          <label>Microphone:</label>
          <div class="meter">
            <div class="level" :style="{ width: micLevel + '%' }"></div>
          </div>
        </div>
        <div class="level-meter">
          <label>Speaker:</label>
          <div class="meter">
            <div class="level" :style="{ width: speakerLevel + '%' }"></div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Room, RoomEvent, LocalTrack, RemoteTrack, Track } from 'livekit-client'

interface Agent {
  id: number
  name: string
  language: string
  voice_id: string
}

const props = defineProps<{
  agent: Agent
}>()

// Reactive state
const phoneNumber = ref('')
const callerName = ref('')
const isConnected = ref(false)
const isConnecting = ref(false)
const isMuted = ref(false)
const micLevel = ref(0)
const speakerLevel = ref(0)
const error = ref('')

// LiveKit room instance
let room: Room | null = null
let callId: number | null = null

// Audio analysis
let micAnalyser: AnalyserNode | null = null
let speakerAnalyser: AnalyserNode | null = null
let animationFrame: number | null = null

async function startCall() {
  if (!phoneNumber.value) {
    error.value = 'Phone number is required'
    return
  }

  isConnecting.value = true
  error.value = ''

  try {
    // Start LiveKit call session
    const response = await fetch(`/agents/${props.agent.id}/livekit/start-call`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        phone_number: phoneNumber.value,
        caller_name: callerName.value
      })
    })

    const data = await response.json()

    if (!data.success) {
      throw new Error(data.message || 'Failed to start call')
    }

    callId = data.call_id
    const sessionData = data.session_data

    // Connect to LiveKit room
    room = new Room()
    
    // Set up event listeners
    room.on(RoomEvent.Connected, onRoomConnected)
    room.on(RoomEvent.Disconnected, onRoomDisconnected)
    room.on(RoomEvent.TrackSubscribed, onTrackSubscribed)
    room.on(RoomEvent.TrackUnsubscribed, onTrackUnsubscribed)

    // Connect to the room
    await room.connect(sessionData.url, sessionData.tokens.caller)

  } catch (err: any) {
    error.value = err.message || 'Failed to start call'
    isConnecting.value = false
  }
}

async function endCall() {
  if (room) {
    await room.disconnect()
  }

  if (callId) {
    try {
      await fetch('/livekit/end-call', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
          call_id: callId
        })
      })
    } catch (err) {
      console.error('Failed to end call:', err)
    }
  }

  cleanup()
}

function toggleMute() {
  if (room && room.localParticipant) {
    const audioPublication = room.localParticipant.getTrackPublication(Track.Source.Microphone)
    if (audioPublication && audioPublication.track) {
      audioPublication.track.mute()
      isMuted.value = audioPublication.track.isMuted
    }
  }
}

// LiveKit event handlers
async function onRoomConnected() {
  console.log('Connected to LiveKit room')
  isConnected.value = true
  isConnecting.value = false

  // Enable camera and microphone
  try {
    await room?.localParticipant.enableCameraAndMicrophone()
    setupAudioAnalysis()
  } catch (err) {
    console.error('Failed to enable audio/video:', err)
    error.value = 'Failed to enable microphone'
  }
}

function onRoomDisconnected() {
  console.log('Disconnected from LiveKit room')
  cleanup()
}

function onTrackPublished(track: LocalTrack, participant: any) {
  console.log('Track published:', track)
}

function onTrackUnpublished(track: LocalTrack, participant: any) {
  console.log('Track unpublished:', track)
}

function onTrackSubscribed(track: RemoteTrack, participant: any) {
  console.log('Track subscribed:', track)
  
  if (track.kind === Track.Kind.Audio) {
    // Play remote audio
    const audioElement = track.attach()
    document.body.appendChild(audioElement)
  }
}

function onTrackUnsubscribed(track: RemoteTrack, participant: any) {
  console.log('Track unsubscribed:', track)
  track.detach()
}

function setupAudioAnalysis() {
  if (!room?.localParticipant) return

  const micPublication = room.localParticipant.getTrackPublication(Track.Source.Microphone)
  if (micPublication && micPublication.track && micPublication.track.mediaStreamTrack) {
    const audioContext = new AudioContext()
    const source = audioContext.createMediaStreamSource(new MediaStream([micPublication.track.mediaStreamTrack]))
    micAnalyser = audioContext.createAnalyser()
    source.connect(micAnalyser)
    
    startAudioLevelMonitoring()
  }
}

function startAudioLevelMonitoring() {
  const updateLevels = () => {
    if (micAnalyser) {
      const dataArray = new Uint8Array(micAnalyser.frequencyBinCount)
      micAnalyser.getByteFrequencyData(dataArray)
      const average = dataArray.reduce((a, b) => a + b) / dataArray.length
      micLevel.value = (average / 255) * 100
    }

    animationFrame = requestAnimationFrame(updateLevels)
  }
  
  updateLevels()
}

function cleanup() {
  isConnected.value = false
  isConnecting.value = false
  isMuted.value = false
  callId = null
  
  if (animationFrame) {
    cancelAnimationFrame(animationFrame)
    animationFrame = null
  }
  
  micAnalyser = null
  speakerAnalyser = null
  room = null
}

onUnmounted(() => {
  cleanup()
})
</script>

<style scoped>
.livekit-voice-call {
  max-width: 500px;
  margin: 0 auto;
  padding: 20px;
  border: 1px solid #ddd;
  border-radius: 8px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 14px;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  margin-right: 10px;
}

.btn-primary {
  background-color: #007bff;
  color: white;
}

.btn-secondary {
  background-color: #6c757d;
  color: white;
}

.btn-warning {
  background-color: #ffc107;
  color: black;
}

.btn-danger {
  background-color: #dc3545;
  color: white;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.call-info {
  background-color: #f8f9fa;
  padding: 15px;
  border-radius: 4px;
  margin-bottom: 15px;
}

.call-info p {
  margin: 5px 0;
}

.call-controls {
  text-align: center;
  margin-bottom: 20px;
}

.audio-levels {
  margin-top: 20px;
}

.level-meter {
  margin-bottom: 10px;
}

.level-meter label {
  display: block;
  margin-bottom: 5px;
  font-size: 12px;
}

.meter {
  width: 100%;
  height: 20px;
  background-color: #e9ecef;
  border-radius: 10px;
  overflow: hidden;
}

.level {
  height: 100%;
  background-color: #28a745;
  transition: width 0.1s ease;
}

.error-message {
  color: #dc3545;
  background-color: #f8d7da;
  padding: 10px;
  border-radius: 4px;
  margin-top: 15px;
}
</style>
