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
    
    <!-- Simple loading state -->
    <div v-if="isLoading" class="loading">
      Loading...
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

// State - keep it simple
const currentTrack = ref(null);
const isPlaying = ref(false);
const isMixActive = ref(false);
const isLoading = ref(true);

// Simple functions to update player state
const updatePlayerState = (data) => {
  if (!data || !data.item) {
    currentTrack.value = null;
    isPlaying.value = false;
    return;
  }
  
  currentTrack.value = data.item;
  isPlaying.value = data.is_playing;
};

// Initial data load
const loadInitialData = async () => {
  try {
    isLoading.value = true;
    
    // Get mix status with playback data in a single request
    const response = await axios.get('/api/spotify/request-status', {
      params: { 
        mix_id: props.mix.id,
        include_playback: true,  // Add this to request playback data together
        max_age: 10 // Keep the max_age for freshness control
      }
    });
    
    // Set the active state immediately
    isMixActive.value = response.data.is_active;
    
    // If mix is active and playback data was included, use it
    if (isMixActive.value && response.data.playback_data) {
      const playbackData = { ...response.data.playback_data };
      delete playbackData._fromCache;
      delete playbackData._timestamp;
      
      updatePlayerState(playbackData);
    }
  } catch (err) {
    console.error('Error loading initial data:', err);
  } finally {
    isLoading.value = false;
  }
};

// Toggle mix active status
const toggleMixActive = async () => {
  try {
    // Make the API call
    const response = await axios.post('/api/spotify/set-mix-active', {
      mix_id: props.mix.id,
      active: !isMixActive.value
    });
    
    console.log('Toggle response:', response.data);
    
    // Update local state based on server response
    isMixActive.value = response.data.is_active;
    
    // If activating AND server returned playback data, use it!
    if (response.data.is_active && response.data.playback_data) {
      console.log('Using playback data from activation response');
      updatePlayerState(response.data.playback_data);
    } else if (response.data.is_active) {
      // If activated but no data was included, fetch fresh data
      console.log('Fetching fresh data after activation');
      loadInitialData();
    } else {
      // If deactivating, clear the UI
      currentTrack.value = null;
      isPlaying.value = false;
    }
  } catch (err) {
    console.error('Error toggling mix active status:', err);
  }
};

// Lifecycle hooks
onMounted(() => {
  if (props.mix) {
    // Force fresh data on initial page load
    loadInitialData();
    
    // Listen for playback data updates
    Echo.channel(`mix.${props.mix.id}`)
      .listen('.playback-data', (e) => {
        console.log('📢 Received playback-data event:', e);
        
        if (e.playbackData.status === 'no_active_playback') {
          // Clear current track
          currentTrack.value = null;
          isPlaying.value = false;
          console.log('No active playback');
        } else {
          // Normal playback data
          updatePlayerState(e.playbackData);
        }
      })
      .listen('.MixStatusChanged', (e) => {
        isMixActive.value = e.isActive;
        console.log('Mix status changed:', e.isActive);
      });
  }
});

onUnmounted(() => {
  // Clean up subscriptions when component is destroyed
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