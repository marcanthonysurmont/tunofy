import { defineStore } from "pinia";
import { ref } from "vue";

export const usePlaylistStore = defineStore("playlist", () => {
    const renderedSongs = ref([]);
    const nextFetchURL = ref(null);
    const isFetching = ref(false);
    const lastKnownFetchURL = ref(null);

    function setSongs(songs) {
        renderedSongs.value = songs;
    }

    function addSongs(songs) {
        renderedSongs.value = [...renderedSongs.value, ...songs];
    }

    function addSong(song) {
        //cant use push due to reactivity issues
        // renderedSongs.value.push(song);

        //currently testing with new array reference
        renderedSongs.value = [...renderedSongs.value, song];
    }

    function removeSong(id) {
        renderedSongs.value = renderedSongs.value.filter(song => song.id !== id);
    }

    function setNextFetchURL(url) {
        nextFetchURL.value = url;
    }

    function setLastKnownFetchURL(url) {
        lastKnownFetchURL.value = url;
    }

    function setIsFetching(val) {
        isFetching.value = val;
    }

    function reset(){
        renderedSongs.value = [];
        nextFetchURL.value = null;
        isFetching.value = false;
    }

    return {
        renderedSongs,
        nextFetchURL,
        isFetching,
        setSongs,
        addSongs,
        addSong,
        removeSong,
        setNextFetchURL,
        setLastKnownFetchURL,
        setIsFetching,
        reset
    };
});