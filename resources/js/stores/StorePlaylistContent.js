import { defineStore } from "pinia";
import { ref, watch } from "vue";

export const usePlaylistStore = defineStore("playlist", () => {
    const renderedSongs = ref([]);
    const nextFetchURL = ref(null);
    const isFetching = ref(false);
    const lastKnownFetchURL = ref(null);      

    function setSongs(songs) {
        renderedSongs.value = songs;
    }

    function addSongs(songs) {
        //merge and deduplicate by song id
        const allSongs = [...renderedSongs.value, ...songs];
        const seen = new Set();
        renderedSongs.value = allSongs.filter(song => {
            if (seen.has(song.id)) return false;
            seen.add(song.id);
            return true;
        });
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

    function incrementFetchPage() {
        console.log(lastKnownFetchURL.value);
        const url = new URL(lastKnownFetchURL.value, window.location.origin);
        const pageParam = url.searchParams.get("page");
        const newPage = pageParam ? parseInt(pageParam) + 1 : 1;
        url.searchParams.set("page", newPage);
        nextFetchURL.value = url.toString();
        console.log("Incremented fetch URL to:", nextFetchURL.value);
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
        incrementFetchPage,
        setIsFetching,
        reset
    };
});