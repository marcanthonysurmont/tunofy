<template>
  <div class="playback-container">
    <!-- For owners, show a simple toggle button -->
    <div v-if="props.mix.authorized.isOwner" class="mix-controls">
      <button 
        @click="toggleMixActive" 
        :class="['control-button', isMixActive ? 'active' : 'inactive']"
        :disabled="isLoading || stateUpdating"
      >
        {{ isMixActive ? 'Deactivate Polling' : 'Activate Polling' }}
      </button>
      
      <!-- Add a refresh button for debugging - you can remove this later -->
      <button 
        @click="refreshMixState" 
        class="refresh-button"
        :disabled="isLoading || stateUpdating"
      >
        Refresh State
      </button>
    </div>
    
    <!-- Loading states - Prioritize showing one at a time -->
    <div v-if="stateUpdating" class="loading">
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

    <!-- Debug Info -->
    <div v-if="props.mix.authorized.isOwner" class="debug-info">
      <div class="debug-title" @click="showDebug = !showDebug">
        Debug Info {{ showDebug ? '▲' : '▼' }}
      </div>
      <div v-if="showDebug" class="debug-details">
        <p>Mix Active: {{ isMixActive }}</p>
        <p>Syncing: {{ isSyncingWithSpotify }}</p>
        <p>Has Track: {{ !!currentTrack }}</p>
        <p>Is Playing: {{ isPlaying }}</p>
        <p>Queue Completed: {{ queueCompleted }}</p>
        <p>Loading: {{ isLoading }}</p>
        <p>State Updating: {{ stateUpdating }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
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

// State variables
const isMixActive = ref(props.mix?.is_active || false);
const isLoading = ref(false);
const stateUpdating = ref(false);
const isSyncingWithSpotify = ref(false);
const currentTrack = ref(null);
const isPlaying = ref(false);
const queueCompleted = ref(false);

// Add last event timestamp
const lastEventTimestamp = ref(0);

// New state variable to track last toggle time
const lastToggleTime = ref(Date.now());

// Add debug state
const showDebug = ref(false);

let syncTimeoutId = null;

// Add a force refresh timer
const lastRefreshTime = ref(0);

// Add these for more reliable state tracking
const lastEventTime = ref(0);

// Add a method to periodically refresh the state
const setupAutoRefresh = () => {
  if (!props.mix.authorized.isOwner) return;
  
  // Create an interval that checks if we need to refresh
  const refreshInterval = setInterval(() => {
    // Only refresh if polling is active and it's been more than 10 seconds
    if (isMixActive.value && Date.now() - lastRefreshTime.value > 10000) {
      refreshMixState();
      lastRefreshTime.value = Date.now();
    }
  }, 10000);

  // Clean up on unmount
  onUnmounted(() => {
    clearInterval(refreshInterval);
  });
};

// Modify toggleMixActive to immediately show UI feedback
const toggleMixActive = async () => {
  try {
    // Prevent rapid toggling
    const now = Date.now();
    if (now - lastToggleTime.value < 1000) {
      console.log('Preventing rapid toggle, please wait');
      return;
    }
    
    // Update last toggle time
    lastToggleTime.value = now;
    
    // Set loading states
    isLoading.value = true;
    stateUpdating.value = true;
    queueCompleted.value = false;
    
    // Store the target state
    const targetActive = !isMixActive.value;
    
    // IMMEDIATELY update UI for better responsiveness
    // This gives the perception of instant response
    isMixActive.value = targetActive;
    
    // If activating, immediately show syncing state
    if (targetActive) {
      setSyncingState();
      // Clear any stale data
      currentTrack.value = null;
      isPlaying.value = false;
    } else {
      // If deactivating, immediately clear UI
      clearSyncingState();
      currentTrack.value = null;
      isPlaying.value = false;
    }
    
    console.log(`Optimistically toggled UI to: ${targetActive ? 'active' : 'inactive'}`);
    
    // Now make the actual API call
    const response = await axios.post('/api/spotify/set-mix-active', {
      mix_id: props.mix.id,
      active: targetActive,
      reset_queue: targetActive // Always reset queue when activating
    });
    
    console.log('Toggle response:', response.data);
    
    // Verify our optimistic update was correct
    const serverIsActive = response.data.activation.is_active;
    
    // If server disagrees with our optimistic update, correct it
    if (serverIsActive !== isMixActive.value) {
      console.log(`Correcting optimistic UI update: ${isMixActive.value} → ${serverIsActive}`);
      isMixActive.value = serverIsActive;
    }
    
    // If activating, schedule multiple refreshes to get initial state
    if (serverIsActive) {
      // If playback data was included in response, use it
      if (response.data.playback_data) {
        updatePlayerState(response.data.playback_data);
      }
      
      // Schedule follow-up refreshes
      setTimeout(() => refreshMixState(), 2000);
      setTimeout(() => refreshMixState(), 5000);
    }
  } catch (err) {
    console.error('Error toggling mix active status:', err);
    clearSyncingState();
  } finally {
    // Clear loading states
    setTimeout(() => {
      isLoading.value = false;
      stateUpdating.value = false;
    }, 500);
  }
};

// Enhanced refreshMixState
const refreshMixState = async () => {
  try {
    console.log('Refreshing mix state from server');
    const response = await axios.get('/api/spotify/request-status', {
      params: { mix_id: props.mix.id }
    });
    
    console.log('Status response:', response.data);
    
    // Update activation state from server
    isMixActive.value = response.data.is_active;
    
    // Update playback data if available
    if (response.data.playback_data) {
      // Force UI update with the latest playback data
      updatePlayerState(response.data.playback_data);
      
      // Clear syncing if we have playback data
      clearSyncingState();
    } else if (isMixActive.value) {
      // If active but no playback data yet, ensure syncing state is shown
      setSyncingState();
    } else {
      // If inactive and no data, clear everything
      clearSyncingState();
      currentTrack.value = null;
      isPlaying.value = false;
    }
    
    // Log the state after refresh
    console.log(`Mix state after refresh: active=${isMixActive.value}, playing=${isPlaying.value}, track=${currentTrack.value?.name || 'none'}`);
    
    // Record the refresh time
    lastRefreshTime.value = Date.now();
  } catch (err) {
    console.error('Error refreshing mix state:', err);
  }
};

// Clear the syncing state
const clearSyncingState = () => {
  isSyncingWithSpotify.value = false;
  
  if (syncTimeoutId) {
    clearTimeout(syncTimeoutId);
    syncTimeoutId = null;
  }
};

// Set the syncing state
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

// Update player state
const updatePlayerState = (playbackData) => {
  if (!playbackData) {
    console.log('No playback data provided to updatePlayerState');
    return;
  }
  
  isPlaying.value = playbackData.is_playing === true;
  currentTrack.value = playbackData.item;
  
  // If we have a current track, we're no longer syncing
  if (currentTrack.value) {
    clearSyncingState();
  }
  
  console.log(`Player updated: playing=${isPlaying.value}, track=${currentTrack.value?.name || 'none'}`);
};

// Add event listeners with improved debugging
onMounted(() => {
  if (props.mix) {
    // Initialize state
    isMixActive.value = !!props.mix.is_active;
    console.log(`Initial mix active state: ${isMixActive.value}`);
    
    // Set syncing state if active
    if (isMixActive.value) {
      setSyncingState();
    }
    
    // Load initial data
    refreshMixState();
    
    // Listen for events with improved logging
    Echo.channel(`mix.${props.mix.id}`)
      .listen('.playback-data', (e) => {
        console.log('📢 Received playback-data event:', e);
        
        // Check for out-of-sequence events
        const eventTime = e.timestamp || Date.now();
        if (eventTime < lastEventTime.value) {
          console.log('Ignoring out-of-sequence event');
          return;
        }
        
        // Update our timestamp tracker
        lastEventTime.value = eventTime;
        
        // If we're in an inactive state but receive data for active mix,
        // update our state to match reality
        if (!isMixActive.value && e.playback_data && 
            e.playback_data.item && e.playback_data.is_playing) {
          console.log('Mix appears to be active based on playback data');
          isMixActive.value = true;
        }
        
        // Update player with the new data
        updatePlayerState(e.playback_data);
      })
      .listen('.mix-status-changed', (e) => {
        console.log('📣 Mix status changed:', e);
        
        // Check for out-of-sequence events
        const eventTime = e.timestamp || Date.now();
        if (eventTime < lastEventTime.value) {
          console.log('Ignoring out-of-sequence status event');
          return;
        }
        
        // Update our timestamp tracker
        lastEventTime.value = eventTime;
        
        // Update the UI state
        isMixActive.value = e.isActive;
        
        // Handle specific state transitions
        if (!e.isActive) {
          clearSyncingState();
          currentTrack.value = null;
          isPlaying.value = false;
          
          // If queue completed, show the completion state
          if (e.reason === 'queue_completed') {
            queueCompleted.value = true;
          }
        } else {
          // If activating, show syncing state
          setSyncingState();
          queueCompleted.value = false;
        }
      });
    
    // Set up auto refresh
    setupAutoRefresh();
  }
});

// Add a watch for isMixActive changes to help with debugging
watch(isMixActive, (newValue, oldValue) => {
  console.log(`🔄 Mix active state changed: ${oldValue} -> ${newValue}`);
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

/* Add this to your styles */
.refresh-button {
  margin-top: 10px;
  padding: 4px 8px;
  font-size: 12px;
  color: #333;
  background: #eee;
  border: 1px solid #ddd;
  border-radius: 4px;
}

/* Add these styles */
.debug-info {
  margin-top: 20px;
  font-size: 12px;
  border-top: 1px solid #444;
  padding-top: 10px;
}

.debug-title {
  cursor: pointer;
  color: #888;
  text-align: center;
}

.debug-details {
  background: #333;
  padding: 10px;
  border-radius: 4px;
  margin-top: 5px;
}

.debug-details p {
  margin: 2px 0;
}
</style>