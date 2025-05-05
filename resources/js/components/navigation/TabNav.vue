<template>
    <div class="mb-12">
        <div
            class="flex md:items-center justify-between md:flex-row flex-col gap-6"
        >
            <nav class="flex space-x-4 relative" aria-label="Tabs">
                <!-- The sliding background indicator -->
                <div
                    ref="activeTabIndicator"
                    class="absolute bg-primary rounded-lg transition-all duration-300 ease-in-out"
                    style="height: 100%; z-index: 0"
                ></div>

                <!-- The tabs -->
                <a
                    v-for="(tab, index) in tabs"
                    :key="tab.name"
                    :href="tab.href"
                    :ref="
                        (el) => {
                            if (el) tabElements[index] = el;
                        }
                    "
                    :class="[
                        'border-2',
                        tab.active
                            ? 'border-primary text-white'
                            : 'border-tab-stroke-inactive text-white',
                        'rounded-lg flex items-center justify-center px-3 pb-1.5 pt-2.5 tab-nav-item-center sm:text-xl md:text-2xl font-medium font-headers cursor-pointer hover:text-neutral-300 z-10 transition-colors duration-300 ease-in-out relative',
                    ]"
                    :aria-current="tab.active ? 'page' : undefined"
                    @click.prevent="changeTab(tab.name)"
                >
                    {{ tab.name }}
                </a>
            </nav>
            <SearchBarSong v-if="activeTabIndex === 0" />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, computed, nextTick } from "vue";
import { ChevronDownIcon } from "@heroicons/vue/16/solid";
import SearchBarSong from "@/components/SearchBarSong.vue";

const props = defineProps({
    tabs: {
        type: Array,
        required: true,
    },
});

const activeTabIndicator = ref(null);
const tabElements = ref([]);

const emit = defineEmits(["tab-changed"]);

//find the currently active tab index
const activeTabIndex = computed(() => {
    return props.tabs.findIndex((tab) => tab.active);
});

//notify the parent about the tab change
const changeTab = (tabName) => {
    console.log("Tab changed to:", tabName);
    emit("tab-changed", tabName);
};

//function to position the active tab indicator
const positionIndicator = () => {
    if (!activeTabIndicator.value || activeTabIndex.value === -1) return;

    //wait until the tab element is available
    if (!tabElements.value[activeTabIndex.value]) {
        //try again in a short moment if tab elements aren't ready yet
        setTimeout(positionIndicator, 10);
        return;
    }

    const activeTab = tabElements.value[activeTabIndex.value];

    //set the indicator position and width to match the active tab
    activeTabIndicator.value.style.left = `${activeTab.offsetLeft}px`;
    activeTabIndicator.value.style.width = `${activeTab.offsetWidth}px`;
};

//watch for changes in the active tab
watch(
    () => [...props.tabs],
    () => {
        nextTick(() => {
            positionIndicator();
        });
    },
    { deep: true }
);

onMounted(() => {
    //initialize tabElements array with the correct length
    tabElements.value = Array(props.tabs.length).fill(null);

    //position the indicator after DOM is ready
    //using setTimeout to ensure all styles and layout calculations are complete
    nextTick(() => {
        //short delay to ensure complete rendering
        setTimeout(() => {
            positionIndicator();
        }, 50);
    });
});

//also reposition on window resize to handle any layout changes
window.addEventListener("resize", () => {
    positionIndicator();
});
</script>
