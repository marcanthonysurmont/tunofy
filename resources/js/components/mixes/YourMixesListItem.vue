<template>
    <div class="relative w-full group">
        <!-- Hover background layer -->
        <div
            class="absolute inset-0 rounded-lg transition-colors group-hover:bg-card-background-lighter z-0"
        ></div>

        <!-- Content layer -->
        <div
            @click="routeUser"
            class="flex flex-row gap-4 w-full items-center relative z-10 py-2 px-2 cursor-pointer"
        >
            <img
                :src="imageUrl"
                class="size-14 object-cover flex-shrink-0 rounded-sm"
            />
            <div
                class="flex flex-col justify-center gap-3 h-14 flex-1 overflow-hidden min-w-0 py-0"
            >
                <h2
                    class="text-xl font-body font-medium whitespace-nowrap overflow-hidden text-ellipsis mt-0 mb-0 leading-none"
                >
                    {{ mix.name.trim() }}
                </h2>
                <span class="text-zinc-400 text-sm mt-0 mb-0 leading-none">
                    Playlist • 5 songs
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, router } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    mix: {
        type: Object,
        required: true,
    },
});

console.log(props.mix);

const imageUrl = computed(() =>
    props.mix.avatar === null
        ? "images/default-avatar.jpg"
        : "storage/" + props.mix.avatar
);

function routeUser() {
    router.get(route("mix.show", props.mix.slug));
}
</script>
