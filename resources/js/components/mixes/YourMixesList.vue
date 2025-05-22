<template>
    <ul v-for="mix in mixes" v-if="mixes.length > 0">
        <YourMixesListItem
            :key="mix.id"
            :mix="mix"
            :is-active="isActive(mix)"
        />
    </ul>
    <div v-else-if="!user.authorized.hasPremium">
        <p class="text-red-400 flex items-center gap-1 text-sm">
            <ExclamationCircleIcon class="size-5" />You need premium to create a
            mix.
        </p>
    </div>
    <div v-else>
        <p class="text-muted">You don't own any mixes yet.</p>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import YourMixesListItem from "@/components/mixes/YourMixesListItem.vue";
import { ExclamationCircleIcon } from "@heroicons/vue/24/outline";

const page = usePage();
const props = computed(() => page.props);
const user = computed(() => props.value.user);
const mixes = computed(() => props.value.your_mixes);
const currentMix = computed(() => props.value.mix);

function isActive(mix) {
    return currentMix.value && currentMix.value.id === mix.id;
}
</script>
