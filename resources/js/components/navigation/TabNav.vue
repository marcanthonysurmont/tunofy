<template>
    <div class="mb-6">
        <div class="grid grid-cols-1 sm:hidden">
            <select aria-label="Select a tab"
                class="col-start-1 row-start-1 w-full appearance-none rounded-lg bg-white py-2 pr-8 pl-3 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"
                @change="changeTab($event)">
                <option v-for="tab in tabs" :key="tab.name" :selected="tab.active">{{ tab.name }}</option>
            </select>
            <ChevronDownIcon
                class="pointer-events-none col-start-1 row-start-1 mr-2 size-5 self-center justify-self-end fill-gray-500"
                aria-hidden="true" />
        </div>
        <div class="hidden sm:block">
            <nav class="flex space-x-4" aria-label="Tabs">
                <a v-for="tab in tabs" :key="tab.name" :href="tab.href" :class="[tab.active ? 'bg-primary text-white' : 'text-white hover:text-neutral-200 bg-tab-background-inactive border-2 border-tab-stroke-inactive',
                    'rounded-lg flex items-center px-3 py-2 text-3xl font-medium font-headers cursor-pointer']"
                    :aria-current="tab.active ? 'page' : undefined" @click.prevent="changeTab(tab.name)">
                    {{ tab.name }}
                </a>
            </nav>
        </div>
    </div>
</template>


<script setup>
import { ChevronDownIcon } from '@heroicons/vue/16/solid';

defineProps({
    tabs: {
        type: Array,
        required: true,
    },
})

const emit = defineEmits(['tab-changed'])

// Method to notify the parent about the tab change
const changeTab = (tabName) => {
    console.log('Tab changed to:', tabName)
    emit('tab-changed', tabName)
}
</script>
