<template>
  <div>
    <h1>Search Tracks</h1>
    
    <!-- Simple form for track search -->
    <form @submit.prevent="searchTracks">
      <input v-model="query" placeholder="Search for tracks..." />
      <button type="submit" :disabled="isLoading">Search</button>
    </form>

    <div v-if="isLoading">Searching...</div>
    
    <!-- Show error message if any -->
    <div v-if="error" class="error">{{ error }}</div>

    <!-- Debug section -->
    <div v-if="debugMode" class="debug">
      <h3>Debug Info:</h3>
      <p>Has Songs: {{ !!songs.length }}</p>
      <p>Songs Length: {{ songs.length }}</p>
      <p>Search Status: {{ searchStatus }}</p>
      <pre>{{ JSON.stringify(songs, null, 2) }}</pre>
    </div>

    <!-- Display results -->
    <ul v-if="songs && songs.length" class="results-list">
      <li v-for="track in songs" :key="track.id">
        <strong>{{ track.name }}</strong> by {{ track.artists?.[0]?.name || 'Unknown Artist' }}
      </li>
    </ul>
    
    <div v-else-if="hasSearched && !songs.length" class="no-results">
      No tracks found for your search.
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

// Debug mode toggle
const debugMode = ref(true);

// State management
const query = ref('');
const songs = ref([]);
const error = ref('');
const isLoading = ref(false);
const hasSearched = ref(false);
const searchStatus = ref('Not started');

// Search tracks function using Axios
const searchTracks = async () => {
  // Form validation
  if (!query.value.trim()) {
    error.value = 'Please enter a search term';
    return;
  }
  
  // Reset states
  error.value = '';
  isLoading.value = true;
  searchStatus.value = 'Searching...';
  
  try {
    // Make API request
    const response = await axios.post('/api/spotify/search', {
      query: query.value
    });
    
    console.log('API response:', response);
    
    // Handle successful response
    if (response.data.songs) {
      songs.value = response.data.songs;
      searchStatus.value = 'Search completed';
    } else {
      songs.value = [];
      searchStatus.value = 'No results found';
    }
    
    hasSearched.value = true;
    
  } catch (err) {
    // Handle errors
    console.error('Search error:', err);
    error.value = err.response?.data?.error || 'An error occurred while searching';
    songs.value = [];
    searchStatus.value = 'Search failed';
  } finally {
    // Always turn off loading state
    isLoading.value = false;
  }
};
</script>

<style scoped>
.error {
  color: red;
  margin-top: 10px;
}

.no-results {
  margin-top: 15px;
  font-style: italic;
}

.debug {
  margin-top: 20px;
  padding: 10px;
  background-color: #f5f5f5;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.debug pre {
  white-space: pre-wrap;
  word-wrap: break-word;
  max-height: 300px;
  overflow: auto;
}

.results-list {
  margin-top: 20px;
  padding-left: 20px;
}

.results-list li {
  margin-bottom: 8px;
}
</style>
