<template>
    <Head :title="`Tunofy | ${nameOfMix}`" />
    <AppLayout>
        <TabNav :tabs="tabs" @tab-changed="setActiveTab" />
        <Suspense
            :key="localActiveTab"
            suspensible
            @resolve="onSuspenseResolve"
        >
            <template #fallback v-if="showFallback">
                <Transition
                    name="fade-with-slide"
                    appear
                    mode="out-in"
                    :duration="300"
                >
                    <SkeletonOverviewSubPage
                        v-if="localActiveTab === 'Overview'"
                    />
                    <SkeletonPresetsSubPage
                        v-else-if="localActiveTab === 'Presets'"
                    />
                    <SkeletonDefault v-else />
                </Transition>
            </template>
            <template #default>
                <Transition
                    name="fade-with-slide"
                    appear
                    mode="out-in"
                    :duration="300"
                >
                    <div
                        :key="localActiveTab"
                        class="relative"
                        :class="
                            localActiveTab === 'Overview' ? 'mb-0' : 'mb-32'
                        "
                    >
                        <component :is="currentAsyncComponent" />
                    </div>
                </Transition>
            </template>
        </Suspense>
        <MixController :mix="mix" />
        <CustomThemeContainer />
    </AppLayout>
</template>

<script setup>
import {
    ref,
    computed,
    defineAsyncComponent,
    onBeforeMount,
    onBeforeUnmount,
    watch,
    onMounted,
} from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import TabNav from "@/components/navigation/TabNav.vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import SkeletonOverviewSubPage from "@/components/skeletons/SkeletonOverviewSubPage.vue";
import SkeletonPresetsSubPage from "@/components/skeletons/SkeletonPresetsSubPage.vue";
import SkeletonDefault from "@/components/skeletons/SkeletonDefault.vue";
import MixController from "@/components/playback/MixController.vue";
import CustomThemeContainer from "@/components/themes/CustomThemeContainer.vue";
import { usePlaylistStore } from "@/stores/StorePlaylistContent.js";
import emitter from "@/eventBus.js";

const OverviewSubPageAsync = defineAsyncComponent(() =>
    import("@/components/subpages/overview/OverviewSubPage.vue")
);
const PresetsSubPageAsync = defineAsyncComponent(() =>
    import("@/components/subpages/presets/PresetsSubPage.vue")
);
const StatsSubPageAsync = defineAsyncComponent(() =>
    import("@/components/subpages/stats/StatsSubPage.vue")
);
const VotingSubPageAsync = defineAsyncComponent(() =>
    import("@/components/subpages/voting/VotingSubPage.vue")
);
const ManageSubPageAsync = defineAsyncComponent(() =>
    import("@/components/subpages/manage/ManageSubPage.vue")
);

const asyncComponents = {
    Overview: OverviewSubPageAsync,
    Voting: VotingSubPageAsync,
    Stats: StatsSubPageAsync,
    Presets: PresetsSubPageAsync,
    Manage: ManageSubPageAsync,
};

const currentAsyncComponent = computed(
    () => asyncComponents[localActiveTab.value]
);

const playlistStore = usePlaylistStore();

const tabs = ref([
    { name: "Overview", active: true, id: "overview" },
    {
        name: "Voting",
        active: false,
        id: "voting",
        votingActive: true,
    },
    { name: "Stats", active: false, id: "stats" },
    { name: "Presets", active: false, id: "presets" },
    { name: "Manage", active: false, id: "manage" },
]);

const page = usePage();
const props = computed(() => page.props);
const songs = computed(() => props.value.songs);
const nameOfMix = computed(() => page.props.mix?.name || "Mix");
const mix = computed(() => page.props.mix || null);
const votableSongs = computed(
    () => Object.values(page.props.votableSongs) || {}
);

//not using computed here because inertia fucks with it otherwise and we cant unmount ws
const mixId = ref(page.props.mix?.id || null);

//if there are votable songs, set the votingActive property to true
//this makes sure an exclamation mark icon is shown in the voting tab
watch(
    () => votableSongs.value,
    (newVal) => {
        if (newVal.length > 0) {
            tabs.value[1].votingActive = true;
        } else {
            tabs.value[1].votingActive = false;
        }
    },
    { immediate: true }
);

watch(
    () => page.props.mix?.slug,
    (newSlug) => {
        //when slug changes, reset the playlist songs
        playlistStore.reset();
        if (playlistStore.renderedSongs.length === 0) {
            playlistStore.setSongs(songs.value.data);
            playlistStore.setNextFetchURL(songs.value.links.next);
        }
    }
);

const showFallback = ref(false);
let fallbackTimer = null;

function startFallbackTimer() {
    if (fallbackTimer) {
        clearTimeout(fallbackTimer);
    }

    fallbackTimer = setTimeout(() => {
        showFallback.value = true;
    }, 200);
}

function onSuspenseResolve() {
    if (fallbackTimer) {
        clearTimeout(fallbackTimer);
        fallbackTimer = null;
    }
    showFallback.value = false;
}

onBeforeUnmount(() => {
    if (fallbackTimer) {
        clearTimeout(fallbackTimer);
    }
});

//start the fallback timer on initial load
startFallbackTimer();

// Track currently active tab in local state first
const localActiveTab = ref("Overview");

// Initialize based on URL or props
onBeforeMount(() => {
    const tabFromProps = page.props.activeTab;
    if (tabFromProps) {
        localActiveTab.value =
            tabFromProps.charAt(0).toUpperCase() + tabFromProps.slice(1);
    }

    tabs.value.forEach((tab) => {
        tab.active =
            tab.name.toLowerCase() === localActiveTab.value.toLowerCase();
    });
});

// Add a variable to track if we're currently in a transition
const isTransitioning = ref(false);

const setActiveTab = (tabName) => {
    // Don't do anything if we're already on this tab or transitioning
    if (
        localActiveTab.value.toLowerCase() === tabName.toLowerCase() ||
        isTransitioning.value
    ) {
        return;
    }

    // Set the flag to prevent multiple rapid transitions
    isTransitioning.value = true;

    // Update local state immediately to trigger the UI change
    localActiveTab.value = tabName;

    // Reset the fallback state and start the timer for the new tab
    showFallback.value = false;
    startFallbackTimer();

    // Update tab state for visual feedback in the tab bar
    tabs.value.forEach((tab) => {
        tab.active = tab.name === tabName;
    });

    // Use browser's History API to update URL without page reload
    if (mix.value?.slug) {
        // If Overview tab, just use the mix slug without tab in URL
        const newUrl =
            tabName.toLowerCase() === "overview"
                ? `/${mix.value.slug}`
                : `/${mix.value.slug}/${tabName.toLowerCase()}`;

        // Update URL without triggering a page reload
        window.history.pushState({ tab: tabName.toLowerCase() }, "", newUrl);
    }

    // Reset transition flag after animation completes
    setTimeout(() => {
        isTransitioning.value = false;
    }, 300);
};

// Handle browser back/forward buttons
window.addEventListener("popstate", (event) => {
    const urlParts = window.location.pathname.split("/");
    // Check if there is a tab in the URL (urlParts would have 3 segments if there's a tab)
    const hasTabInUrl = urlParts.length > 2;

    if (hasTabInUrl) {
        const tabFromUrl = urlParts[urlParts.length - 1];
        const tabName =
            tabFromUrl.charAt(0).toUpperCase() + tabFromUrl.slice(1);
        localActiveTab.value = tabName;
    } else {
        // If no tab in URL, we're on the Overview tab
        localActiveTab.value = "Overview";
    }

    // Reset the fallback state and start the timer for the tab change
    showFallback.value = false;
    startFallbackTimer();

    // Update tab active states
    tabs.value.forEach((tab) => {
        tab.active =
            tab.name.toLowerCase() === localActiveTab.value.toLowerCase();
    });
});

function handleSongAddedEvent(song) {
    //currently commented out because for adding a song we'll just use the websocket event
    // if (playlistStore.nextFetchURL === null) {
    //     console.log("Adding song locally", song);
    //     playlistStore.addSong(song);
    // }
}

onMounted(() => {
    emitter.on("song-added", handleSongAddedEvent);
    Echo.channel(`mix.${props.value.mix.id}`)
        .listen(".vote-updated", () => {
            router.reload({ only: ["allPendingSongs", "success", "danger"] });
            console.log("Vote updated");
        })
        .listen(".queue-state-updated", () => {
            router.reload({
                only: ["allPendingSongs", "votableSongs", "success", "danger"],
            });
            console.log("Queue state updated");
        })
        .listen(".song.added", (e) => {
            //if there are no more pagination links --> add locally
            if (playlistStore.nextFetchURL === null) {
                playlistStore.addSong(e.song);
            }
            router.reload({
                only: [
                    "mixDuration",
                    "mix",
                    "your_mixes",
                    "joined_mixes",
                    "success",
                    "danger",
                ],
            });
        })
        .listen(".song.deleted", (e) => {
            playlistStore.removeSong(e.song.id);
            router.reload({
                only: [
                    "mixDuration",
                    "mix",
                    "your_mixes",
                    "joined_mixes",
                    "success",
                    "danger",
                ],
            });
        })
        .listen(".stat.updated", () => {
            router.reload({
                only: ["mixStats", "success", "error"],
            });
        })
        .listen(".theme.updated", () => {
            router.reload({ only: ["themes", "success", "error"] });
        })
        .listen(".playlist.imported", async () => {
            console.log("Playlist imported");
        });
});

onBeforeUnmount(() => {
    Echo.leave(`mix.${mixId.value}`);
    emitter.off("song-added", handleSongAddedEvent);
    //reset the playlist songs
    playlistStore.reset();
});
</script>
