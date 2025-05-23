<template>
    <transition name="fade-with-slide">
        <button
            v-if="showButton"
            @click="scrollToTop"
            class="fixed top-3 right-3 py-2 px-4 bg-primary text-white rounded flex items-center gap-x-1 cursor-pointer z-[99998]"
        >
            <ArrowUpCircleIcon class="size-5 text-white" />
            <span class="text-sm font-medium">Scroll to top</span>
        </button>
    </transition>

    <div>
        <!-- header row -->
        <div
            class="mt-8 border-b border-zinc-800 flex items-center w-full min-w-0"
        >
            <div
                class="py-3.5 pr-1 sm:pr-3 text-left text-sm font-semibold text-zinc-200 w-6 sm:w-10 flex-shrink-0"
            >
                #
            </div>
            <div
                class="px-1 sm:px-3 py-3.5 text-left text-sm font-semibold text-zinc-200 flex-1 min-w-0 overflow-hidden"
            >
                Song
            </div>
            <div
                class="px-1 sm:px-3 py-3.5 text-left text-sm font-semibold text-zinc-200 w-14 sm:w-20 flex-shrink-0"
            >
                Time
            </div>
            <div
                v-if="windowWidth >= 640"
                class="px-1 sm:px-3 py-3.5 text-right text-sm font-semibold text-zinc-200 w-24 flex-shrink-0"
            >
                Added by
            </div>
            <div
                v-if="windowWidth < 640"
                class="px-1 sm:px-3 py-3.5 text-left text-sm font-semibold text-zinc-200 w-12 sm:w-16 flex-shrink-0"
            >
                By
            </div>
            <div
                v-if="authorization.canRemoveSong"
                class="px-1 sm:px-3 py-3.5 text-right text-sm font-semibold text-zinc-200 w-12 sm:w-16 flex-shrink-0"
            >
                Actions
            </div>
        </div>

        <!-- songs w/ virtual list implementation -->
        <div
            v-if="renderedSongs.length > 0"
            :class="isFetching ? 'mb-4' : 'mb-32'"
        >
            <RecycleScroller
                class="scroller"
                :items="renderedSongs"
                :item-size="windowWidth < 640 ? 64 : 80"
                key-field="id"
                v-slot="{ item, index }"
                page-mode
            >
                <div class="flex items-center w-full min-w-0">
                    <div
                        class="py-2 sm:py-4 pr-1 sm:pr-3 text-sm text-zinc-100 w-8 sm:w-10 flex-shrink-0"
                    >
                        {{ index + 1 }}
                    </div>
                    <div
                        class="px-1 sm:px-3 py-2 sm:py-4 text-sm text-zinc-100 flex-1 min-w-0 overflow-hidden"
                    >
                        <div class="flex items-center gap-2 sm:gap-3">
                            <img
                                v-lazy="{
                                    src: item.image_url,
                                    error: '/images/default-song.png',
                                    loading: '/images/default-song.png',
                                }"
                                alt="cover"
                                class="w-8 h-8 sm:w-10 sm:h-10 rounded flex-shrink-0"
                            />
                            <div class="min-w-0 flex-1 overflow-hidden">
                                <div class="font-medium truncate text-sm">
                                    {{ item.name }}
                                </div>
                                <div
                                    class="text-zinc-400 text-xs sm:text-sm truncate"
                                >
                                    {{ item.artist }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="px-1 sm:px-3 py-2 sm:py-4 text-sm text-zinc-400 w-14 sm:w-20 flex-shrink-0"
                    >
                        {{ msToMinutes(item.duration_ms) }}
                    </div>
                    <div
                        class="px-1 sm:px-3 py-2 sm:py-4 text-sm text-zinc-400 w-12 sm:w-24 flex-shrink-0"
                    >
                        <div
                            class="flex items-center"
                            :class="
                                windowWidth < 640
                                    ? 'justify-start'
                                    : 'justify-end'
                            "
                        >
                            <img
                                v-tippy="{ content: item.user.name }"
                                v-lazy="{
                                    src: item.user.avatar_url,
                                    error: '/images/default-avatar.jpg',
                                    loading: '/images/default-avatar.jpg',
                                }"
                                alt="cover"
                                class="size-6 sm:size-7 rounded-full flex-shrink-0"
                            />
                        </div>
                    </div>
                    <div
                        class="py-2 sm:py-4 pl-1 sm:pl-3 text-right w-12 sm:w-16 flex-shrink-0"
                        v-if="authorization.canRemoveSong"
                    >
                        <div class="flex items-center justify-end">
                            <button
                                v-tippy="{ content: 'Delete song' }"
                                @click="deleteSong(item.id)"
                                class="pl-1 sm:px-2 cursor-pointer"
                            >
                                <TrashIcon
                                    class="size-5 sm:size-6 text-zinc-400 hover:text-zinc-500"
                                />
                            </button>
                        </div>
                    </div>
                </div>
            </RecycleScroller>
        </div>
        <div v-if="isFetching" class="mb-32 flex justify-center w-full">
            <SpinningCircle />
        </div>
        <div v-if="renderedSongs.length === 0" class="mb-32 mt-8">
            <p
                class="text-white text-left text-base"
                v-if="authorization.canAddSong"
            >
                No songs found. Search for a song and add it!
            </p>
            <p v-else class="text-white text-center sm:text-left">
                No songs found..
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { RecycleScroller } from "vue-virtual-scroller";
import "vue-virtual-scroller/dist/vue-virtual-scroller.css";
import { TrashIcon, ArrowUpCircleIcon } from "@heroicons/vue/24/outline";
import axios from "axios";
import { useTimeUtils } from "@/composables/useTimeUtils";
import SpinningCircle from "@/components/spinners/SpinningCircle.vue";
import throttle from "lodash/throttle";
const { msToMinutes } = useTimeUtils();

const page = usePage();
const props = computed(() => page.props);
const songs = computed(() => props.value.songs);
const authorization = computed(() => page.props.mix.authorized);
const showButton = ref(false);
const renderedSongs = ref(songs.value.data);

const windowWidth = ref(window.innerWidth);

const isFetching = ref(false);
const nextFetchURL = ref(songs.value.links.next);
async function fetchMoreSongs() {
    if (isFetching.value || nextFetchURL.value === null) {
        return;
    }
    isFetching.value = true;
    console.log("Fetching more songs...", isFetching.value);

    try {
        const response = await axios.get(nextFetchURL.value, {
            headers: { Accept: "application/json" },
        });
        console.log("Fetched more songs:", response.data);
        renderedSongs.value = [...renderedSongs.value, ...response.data.data];
        nextFetchURL.value = response.data.links.next;
    } finally {
        isFetching.value = false;
    }
}

const isDeleting = ref(false);
function deleteSong(id) {
    if (isDeleting.value) {
        return;
    }
    isDeleting.value = true;
    router.delete(route("mix.remove-song", id), {
        preserveScroll: true,
        onSuccess: () => {
            renderedSongs.value = renderedSongs.value.filter(
                (song) => song.id !== id
            );
            //added for edge case
            //if user loads page, doesnt scroll and delete items it previously would corrupt the infinite scroll
            //by checking scroll upon deletion, we solve this edge case
            checkScroll();
        },
        onError: (error) => {
            console.log("Error deleting song:", error);
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
}

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: "smooth" });
}

function checkScroll() {
    showButton.value = window.scrollY > 600;

    const scrollPosition = window.scrollY + window.innerHeight;

    //300px from bottom
    const nearBottom =
        document.documentElement.scrollHeight - scrollPosition < 300;

    if (nearBottom) {
        fetchMoreSongs();
    }
}

function updateWindowWidth() {
    windowWidth.value = window.innerWidth;
}

//function that is executed when the user scrolls, throttled 200ms
const throttledCheckScroll = throttle(checkScroll, 200);

onMounted(() => {
    window.addEventListener("resize", updateWindowWidth);
    window.addEventListener("scroll", throttledCheckScroll);
});
onBeforeUnmount(() => {
    window.removeEventListener("resize", updateWindowWidth);
    window.removeEventListener("scroll", throttledCheckScroll);
});
</script>

<style>
.scroller {
    height: auto;
    width: 100%;
}

.fade-with-slide-enter-active,
.fade-with-slide-leave-active {
    transition: opacity 0.3s, transform 0.3s;
}

.fade-with-slide-enter-from,
.fade-with-slide-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
