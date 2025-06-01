<template>
    <div class="mb-12">
        <div
            class="flex md:items-center justify-between md:flex-row flex-col gap-6 md:flex-wrap"
        >
            <div class="relative w-full md:w-auto">
                <nav
                    ref="tabsContainer"
                    class="flex space-x-4 relative overflow-x-auto hide-scrollbar"
                    aria-label="Tabs"
                >
                    <!-- sliding background indicator -->
                    <div
                        ref="activeTabIndicator"
                        class="absolute bg-primary rounded-lg transition-all duration-300 ease-in-out h-full z-0"
                    ></div>

                    <!-- tabs -->
                    <a
                        v-for="(tab, index) in tabs"
                        :key="tab.name"
                        :ref="
                            (el) => {
                                if (el) tabRefs[index] = el;
                            }
                        "
                        :class="[
                            'border-2 rounded-lg flex cursor-pointer items-center justify-center px-3 tab-nav-item-center tab-nav-peeking-item sm:text-xl md:text-2xl font-medium font-headers hover:text-neutral-300 z-10 transition-colors duration-300 ease-in-out relative whitespace-nowrap',
                            tab.active
                                ? 'border-primary text-white'
                                : 'border-tab-stroke-inactive text-white',
                            dyslexiaFontEnabled ? 'pb-2 pt-2' : 'pb-1.5 pt-2.5',
                            // tab.votingActive === false && !tab.active
                            //     ? 'opacity-50 cursor-not-allowed'
                            //     : 'cursor-pointer',
                        ]"
                        :aria-current="tab.active ? 'page' : undefined"
                        @click.prevent="handleTabClick(tab.name, index)"
                        :disabled="tab.votingActive === false"
                    >
                        {{ tab.name }}
                        <span
                            class="absolute -top-1 -right-1"
                            v-if="tab.votingActive"
                        >
                            <ExclamationCircleIcon
                                class="size-6 text-yellow-500"
                            />
                        </span>
                    </a>
                </nav>
            </div>
            <SearchBarSong v-if="isActiveTab(0) && authorization.canAddSong" />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, computed, nextTick, onUnmounted } from "vue";
import SearchBarSong from "@/components/SearchBarSong.vue";
import { usePage } from "@inertiajs/vue3";
import { ExclamationCircleIcon } from "@heroicons/vue/24/solid";

const props = defineProps({
    tabs: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(["tab-changed"]);

const activeTabIndicator = ref(null);
const tabRefs = ref([]);
const tabsContainer = ref(null);

const dyslexiaFontEnabled = ref(
    localStorage.getItem("dyslexiaFontEnabled") === "true"
);

const page = usePage();
const authorization = computed(() => page.props.mix.authorized);
const activeTabIndex = computed(() =>
    props.tabs.findIndex((tab) => tab.active)
);

function isActiveTab(index) {
    return activeTabIndex.value === index;
}

function handleTabClick(tabName, index) {
    // if (tabName === "Voting" && !props.tabs[index].votingActive) {
    //     return;
    // }
    emit("tab-changed", tabName);
    scrollToTab(index);

    nextTick(() => {
        const section = document.getElementById(tabName.toLowerCase());
        if (section) {
            section.scrollIntoView({ behavior: "smooth" });
        }
    });
}

function scrollToTab(index) {
    if (!tabsContainer.value || !tabRefs.value[index]) {
        return;
    }

    const container = tabsContainer.value;
    const tab = tabRefs.value[index];
    const containerRect = container.getBoundingClientRect();
    const tabRect = tab.getBoundingClientRect();

    if (
        tabRect.left < containerRect.left ||
        tabRect.right > containerRect.right
    ) {
        const centerPosition =
            tab.offsetLeft - container.clientWidth / 2 + tab.offsetWidth / 2;
        container.scrollTo({ left: centerPosition, behavior: "smooth" });
    }
}

function updateIndicatorPosition() {
    if (!activeTabIndicator.value || activeTabIndex.value === -1) {
        return;
    }

    const activeTab = tabRefs.value[activeTabIndex.value];
    if (!activeTab) {
        //retry if refs aren't ready yet
        setTimeout(updateIndicatorPosition, 10);
        return;
    }

    activeTabIndicator.value.style.left = `${activeTab.offsetLeft}px`;
    activeTabIndicator.value.style.width = `${activeTab.offsetWidth}px`;

    scrollToTab(activeTabIndex.value);
}

onMounted(() => {
    tabRefs.value = Array(props.tabs.length).fill(null);
    nextTick(() => {
        setTimeout(updateIndicatorPosition, 50);
    });

    document.fonts.ready.then(() => {
        updateIndicatorPosition();
    });

    window.addEventListener("resize", updateIndicatorPosition);
});

onUnmounted(() => {
    window.removeEventListener("resize", updateIndicatorPosition);
});

//watches changes in tabs array = if user selects another tab
watch(
    () => [...props.tabs],
    () => nextTick(updateIndicatorPosition),
    { deep: true }
);
</script>

<style scoped>
@media (max-width: 468px) {
    .tab-nav-peeking-item {
        min-width: 25dvw;
        max-width: 150px;
    }
}
</style>
