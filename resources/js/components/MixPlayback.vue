<template>
  <div class="playback-container">
    <!-- For owners, show a simple toggle button -->
    <div v-if="props.mix.authorized.isOwner" class="mix-controls">
      <button 
        @click="toggleMixActive" 
        :class="['control-button', isMixActive ? 'active' : 'inactive']"
        :disabled="isLoading"
      >
        {{ isMixActive ? 'Deactivate Polling' : 'Activate Polling' }}
      </button>
    </div>
    
    <!-- Loading states - Prioritize showing one at a time -->
    <div v-if="isLoading" class="loading">
      Updating state...
    </div>
    
    <!-- Loading state while we're syncing with Spotify -->
    <div v-else-if="isSyncingWithSpotify" class="loading">
      <div class="flex items-center justify-center">
        <svg class="animate-spin h-5 w-5 mr-2 text-spotify-green" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Syncing with Spotify...
      </div>
    </div>
    
    <!-- Add queue completion state - explicit Boolean check -->
    <div v-else-if="queueCompleted === true" class="queue-completed">
      <div class="checkmark">✓</div>
      <div class="completion-message">
        Queue completed! All songs have been played.
      </div>
    </div>
    
    <!-- Content based on active state - explicit Boolean check -->
    <div v-else-if="isMixActive === false" class="not-active">
      Playback polling is inactive for this mix
    </div>
    
    <div v-else-if="!currentTrack" class="not-playing">
      Nothing is currently playing
    </div>
    
    <!-- Now playing view -->
    <div v-else class="now-playing">
      <div class="track-info">
        <img 
          v-if="currentTrack.album?.images?.length" 
          :src="currentTrack.album.images[0].url" 
          class="album-art"
          alt="Album Art"
        />
        
        <div class="text-info">
          <div class="track-name">{{ currentTrack.name }}</div>
          <div class="artist-name">
            {{ currentTrack.artists?.map(a => a.name).join(', ') }}
          </div>
        </div>
      </div>
      
      <div class="play-status" :class="{ 'is-playing': isPlaying }">
        {{ isPlaying ? 'Now Playing' : 'Paused' }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';

// Props
const props = defineProps({
  mix: {
    type: Object,
    required: true
  }
});

// Core state variables
const isMixActive = ref(props.mix?.is_active || false);
const isLoading = ref(false);
const currentTrack = ref(null);
const isPlaying = ref(false);
const isSyncingWithSpotify = ref(false);
const queueCompleted = ref(false);

// Simplified state tracking
const lastEventTime = ref(0);
const lastToggleTime = ref(0);
let syncTimeoutId = null;

// Debug panel toggle (can be removed in production)
const showDebug = ref(false);

// Toggle mix active state
const toggleMixActive = async () => {
  try {
    // Prevent rapid toggling
    if (Date.now() - lastToggleTime.value < 1000) return;
    lastToggleTime.value = Date.now();
    
    // Set loading state
    isLoading.value = true;
    
    // Optimistically update UI
    const targetActive = !isMixActive.value;
    isMixActive.value = targetActive;
    
    // Update UI immediately
    if (targetActive) {
      setSyncingState();
      currentTrack.value = null;
      isPlaying.value = false;
      queueCompleted.value = false;
    } else {
      clearSyncingState();
      currentTrack.value = null;
      isPlaying.value = false;
    }
    
    // Make API call
    const response = await axios.post('/api/spotify/set-mix-active', {
      mix_id: props.mix.id,
      active: targetActive,
      reset_queue: targetActive
    });
    
    // Confirm server status
    isMixActive.value = response.data.activation.is_active;
    
    // If activating, handle initial data
    if (isMixActive.value && response.data.playback_data) {
      updatePlayerState(response.data.playback_data);
    }
  } catch (err) {
    console.error('Error toggling mix active status:', err);
    clearSyncingState();
  } finally {
    setTimeout(() => isLoading.value = false, 500);
  }
};

// Refresh mix state from server
const refreshMixState = async () => {
  try {
    const response = await axios.get('/api/spotify/request-status', {
      params: { mix_id: props.mix.id }
    });
    
    // Update state from server
    isMixActive.value = response.data.is_active;
    
    // Handle playback data
    if (response.data.playback_data) {
      updatePlayerState(response.data.playback_data);
    } else if (isMixActive.value) {
      setSyncingState();
    } else {
      clearSyncingState();
      currentTrack.value = null;
      isPlaying.value = false;
    }
  } catch (err) {
    console.error('Error refreshing mix state:', err);
  }
};

// Syncing state management
const clearSyncingState = () => {
  isSyncingWithSpotify.value = false;
  if (syncTimeoutId) {
    clearTimeout(syncTimeoutId);
    syncTimeoutId = null;
  }
};

const setSyncingState = () => {
  isSyncingWithSpotify.value = true;
  if (syncTimeoutId) clearTimeout(syncTimeoutId);
  // Auto-clear after 10s to prevent getting stuck
  syncTimeoutId = setTimeout(() => isSyncingWithSpotify.value = false, 10000);
};

// Update player with playback data
const updatePlayerState = (playbackData) => {
  if (!playbackData) return;
  
  isPlaying.value = playbackData.is_playing === true;
  currentTrack.value = playbackData.item;
  
  // Clear syncing state if we have track data
  if (currentTrack.value) clearSyncingState();
};

// Setup WebSocket listeners
onMounted(() => {
  if (props.mix) {
    // Initialize state
    isMixActive.value = !!props.mix.is_active;
    if (isMixActive.value) setSyncingState();
    
    // Initial data load
    refreshMixState();
    
    // Listen for real-time events
    Echo.channel(`mix.${props.mix.id}`)
      .listen('.playback-data', (e) => {
        // Ignore out-of-sequence events
        const eventTime = e.timestamp || Date.now();
        if (eventTime < lastEventTime.value) return;
        lastEventTime.value = eventTime;
        
        // Update active state if needed
        if (!isMixActive.value && e.playback_data?.item && e.playback_data.is_playing) {
          isMixActive.value = true;
        }
        
        // Update player
        updatePlayerState(e.playback_data);
      })
      .listen('.mix-status-changed', (e) => {
        // Ignore out-of-sequence events
        const eventTime = e.timestamp || Date.now();
        if (eventTime < lastEventTime.value) return;
        lastEventTime.value = eventTime;
        
        // Update active state
        isMixActive.value = e.isActive;
        
        // Handle state changes
        if (!e.isActive) {
          clearSyncingState();
          currentTrack.value = null;
          isPlaying.value = false;
          if (e.reason === 'queue_completed') queueCompleted.value = true;
        } else {
          setSyncingState();
          queueCompleted.value = false;
        }
      });
  }
});

// Clean up
onUnmounted(() => {
  if (syncTimeoutId) clearTimeout(syncTimeoutId);
  if (props.mix) Echo.leave(`mix.${props.mix.id}`);
});
</script>

<style scoped>
.playback-container {
  background-color: #222;
  border-radius: 8px;
  padding: 16px;
  color: white;
  max-width: 500px;
  margin: 0 auto;
}

.loading, .error, .not-playing, .not-active {
  text-align: center;
  padding: 20px;
  color: #aaa;
}

.queue-completed {
  text-align: center;
  padding: 24px 16px;
  animation: fadeIn 0.5s ease-out;
}

.checkmark {
  color: #1DB954;
  font-size: 32px;
  margin-bottom: 12px;
  background-color: rgba(29, 185, 84, 0.1);
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
}

.completion-message {
  font-size: 1.1rem;
  color: #fff;
  margin-bottom: 8px;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.track-info {
  display: flex;
  align-items: center;
  margin-bottom: 12px;
}

.album-art {
  width: 60px;
  height: 60px;
  border-radius: 4px;
  margin-right: 12px;
}

.text-info {
  flex: 1;
}

.track-name {
  font-weight: bold;
  font-size: 1.1rem;
  margin-bottom: 4px;
}

.artist-name {
  color: #aaa;
  font-size: 0.9rem;
}

.play-status {
  text-align: center;
  font-size: 0.8rem;
  color: #aaa;
  background-color: rgba(0,0,0,0.2);
  padding: 4px 8px;
  border-radius: 12px;
  display: inline-block;
  margin-top: 8px;
}

.play-status.is-playing {
  color: #1DB954; /* Spotify green */
}

.mix-controls {
  text-align: center;
  margin-bottom: 12px;
}

.control-button {
  background-color: #333;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 8px 16px;
  font-size: 0.9rem;
  cursor: pointer;
}

.control-button.active {
  background-color: #1DB954; /* Spotify green */
}

.control-button.inactive {
  background-color: #444;
}

.control-button:hover:not(:disabled) {
  opacity: 0.9;
}

.control-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>