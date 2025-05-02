<template>
    <ul v-for="mix in mixes" v-if="mixes.length > 0">
        <YourMixesListItem :key="mix.id" :mix="mix"> </YourMixesListItem>
    </ul>
    <div v-else>
        <p class="text-zinc-400">You don't have any mixes yet.</p>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import YourMixesListItem from "./YourMixesListItem.vue";

const page = usePage();
const props = computed(() => page.props);
const mixes = computed(() => props.value.your_mixes);
</script>
