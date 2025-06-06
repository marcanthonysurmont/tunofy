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
            <div class="relative size-14 flex-shrink-0">
                <img
                    :src="imageUrl"
                    alt="Cover image of the mix"
                    class="size-14 object-cover flex-shrink-0 rounded-sm"
                />
                <div
                    class="absolute inset-0 bg-black/85 rounded-xs"
                    v-if="mix.is_active === 1"
                ></div>
                <div
                    v-if="mix.is_active === 1"
                    class="absolute inset-0 flex items-center justify-center pointer-events-none"
                >
                    <SoundWave
                        :bar-count="4"
                        :color="isActive ? 'primary' : 'white'"
                        size="md"
                    />
                </div>
            </div>
            <div
                class="flex flex-col justify-center gap-3 h-14 flex-1 overflow-hidden min-w-0 py-0"
            >
                <h2
                    class="text-xl font-body font-medium whitespace-nowrap overflow-hidden text-ellipsis mt-0 mb-0 leading-none pt-0"
                    :class="{
                        'text-primary': isActive,
                    }"
                >
                    {{ mix.name.trim() }}
                </h2>
                <span class="text-muted text-sm mt-0 mb-0 leading-none">
                    Mix • {{ mix.mix_count }} songs
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from "@inertiajs/vue3";
import { computed } from "vue";
import SoundWave from "@/components/spinners/SoundWave.vue";

const props = defineProps({
    mix: {
        type: Object,
        required: true,
    },
    isActive: {
        type: Boolean,
        default: false,
    },
});

const imageUrl = computed(() =>
    props.mix.avatar === null
        ? "/images/default-song.png"
        : "/storage/" + props.mix.avatar
);

function routeUser() {
    router.get(route("mix.show", props.mix.slug));
}
</script>
