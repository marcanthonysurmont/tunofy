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
    
    <!-- Loading state while we're syncing with Spotify -->
    <div v-if="isSyncingWithSpotify" class="loading">
      <div class="flex items-center justify-center">
        <svg class="animate-spin h-5 w-5 mr-2 text-spotify-green" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Syncing with Spotify...
      </div>
    </div>
    
    <!-- Content based on active state -->
    <div v-else-if="!isMixActive" class="not-active">
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
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

// Props
const props = defineProps({
  mix: {
    type: Object,
    required: true
  },
  isOwner: {
    type: Boolean,
    default: false
  }
});

// State variables with consistent naming and purpose
const currentTrack = ref(null);
const isPlaying = ref(false);
const isMixActive = ref(props.mix?.is_active || false);
const isLoading = ref(false);  // Used only for button loading state
const isSyncingWithSpotify = ref(false);
let syncTimeoutId = null;

// Update player state with consistent handling
const updatePlayerState = (data) => {
  console.log('Updating player state with:', data);
  
  if (!data || !data.item) {
    currentTrack.value = null;
    isPlaying.value = false;
    return;
  }
  
  // Update with the track info
  currentTrack.value = data.item;
  isPlaying.value = data.is_playing;
  
  // When we have a valid playing track, we're no longer syncing
  if (data.is_playing && data.item) {
    console.log('Valid playing track detected, ending sync state');
    clearSyncingState();
  }
  
  console.log('Player state updated:', { isPlaying: isPlaying.value, track: currentTrack.value?.name });
};

// Initial data load with clear error handling
const loadInitialData = async () => {
  try {
    // Get mix status with playback data in a single request
    const response = await axios.get('/api/spotify/request-status', {
      params: { 
        mix_id: props.mix.id,
      }
    });
    
    // Set the active state immediately
    isMixActive.value = response.data.is_active;
    
    // If mix is active, show syncing UI
    if (isMixActive.value) {
      setSyncingState();
    }
    
    // If mix is active and playback data was included, use it
    if (isMixActive.value && response.data.playback_data) {
      const playbackData = { ...response.data.playback_data };
      
      if (playbackData.item && playbackData.is_playing) {
        clearSyncingState();
      }
      
      updatePlayerState(playbackData);
    }
  } catch (err) {
    console.error('Error loading initial data:', err);
    clearSyncingState();
  }
};

// Toggle mix active status with clear states
const toggleMixActive = async () => {
  try {
    isLoading.value = true;
    
    // When activating, set syncing flag immediately
    if (!isMixActive.value) {
      setSyncingState();
    }
    
    // Make the API call
    const response = await axios.post('/api/spotify/set-mix-active', {
      mix_id: props.mix.id,
      active: !isMixActive.value
    });
    
    console.log('Toggle response:', response.data);
    
    // Update local state based on server response
    isMixActive.value = response.data.activation.is_active;
    
    // If deactivating, clear the UI
    if (!isMixActive.value) {
      clearSyncingState();
      currentTrack.value = null;
      isPlaying.value = false;
    }
    // If playback data is available and we're activating, use it
    else if (response.data.playback_data) {
      updatePlayerState(response.data.playback_data);
    }
  } catch (err) {
    console.error('Error toggling mix active status:', err);
    clearSyncingState();
  } finally {
    isLoading.value = false;
  }
};

// Clean helper functions for sync state
const setSyncingState = () => {
  isSyncingWithSpotify.value = true;
  
  // Clear any existing timeout
  if (syncTimeoutId) {
    clearTimeout(syncTimeoutId);
  }
  
  // Set a new timeout
  syncTimeoutId = setTimeout(() => {
    if (isSyncingWithSpotify.value) {
      console.log('Sync timeout reached, showing current state');
      isSyncingWithSpotify.value = false;
    }
  }, 10000); // 10 seconds max sync time
};

const clearSyncingState = () => {
  isSyncingWithSpotify.value = false;
  
  if (syncTimeoutId) {
    clearTimeout(syncTimeoutId);
    syncTimeoutId = null;
  }
};

// Lifecycle hooks
onMounted(() => {
  if (props.mix) {
    // Start with syncing state if mix is active
    if (props.mix.is_active) {
      setSyncingState();
    }
    
    // Load initial data
    loadInitialData();
    
    // Listen for playback data updates
    Echo.channel(`mix.${props.mix.id}`)
      .listen('.playback-data', (e) => {
        console.log('📢 Received playback-data event:', e);
        
        if (e.playback_data.status === 'no_active_playback') {
          // Clear current track
          currentTrack.value = null;
          isPlaying.value = false;
          console.log('No active playback');
        } else {
          // Normal playback data
          updatePlayerState(e.playback_data);
          
          // Make sure to mark the mix as active when we receive playback data
          if (!isMixActive.value) {
            console.log('Setting mix to active based on incoming playback data');
            isMixActive.value = true;
          }
        }
      })
      .listen('.mix-status-changed', (e) => {
        console.log('Mix status changed:', e.isActive);
        isMixActive.value = e.isActive;
        
        // If mix was just activated, show syncing state
        if (e.isActive) {
          setSyncingState();
        } else {
          // If deactivated, clear syncing state and track info
          clearSyncingState();
          currentTrack.value = null;
          isPlaying.value = false;
        }
      });
  }
});

onUnmounted(() => {
  // Clean up subscriptions and timers when component is destroyed
  if (syncTimeoutId) {
    clearTimeout(syncTimeoutId);
  }
  
  if (props.mix) {
    Echo.leave(`mix.${props.mix.id}`);
  }
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