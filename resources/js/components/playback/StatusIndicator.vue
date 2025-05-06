<template>
    <div class="text-right flex-1 flex justify-end items-center">
        <span
            class="px-3 py-2 rounded text-xs font-semibold flex items-center gap-x-2"
            :class="{
                'bg-green-900 text-green-400': isPlaying && !isSyncDisabled,
                'bg-zinc-950 text-zinc-400': !isPlaying && !isSyncDisabled,
                'bg-red-900 text-red-400': isSyncDisabled,
            }"
        >
            <div class="relative w-2 h-2">
                <div
                    class="absolute inset-0 rounded-full animate-ping"
                    :class="{
                        'bg-green-400': isPlaying && !isSyncDisabled,
                        'bg-zinc-400': !isPlaying && !isSyncDisabled,
                        'bg-red-400': isSyncDisabled,
                    }"
                ></div>
                <div
                    class="w-2 h-2 rounded-full z-10"
                    :class="{
                        'bg-green-400': isPlaying && !isSyncDisabled,
                        'bg-zinc-400': !isPlaying && !isSyncDisabled,
                        'bg-red-400': isSyncDisabled,
                    }"
                ></div>
            </div>

            {{
                isSyncDisabled
                    ? "Sync Disabled"
                    : isPlaying
                    ? "Now Playing"
                    : "Paused"
            }}
        </span>
    </div>
</template>

<script setup>
const props = defineProps({
    isPlaying: {
        type: Boolean,
        required: true,
    },
    isSyncDisabled: {
        type: Boolean,
        required: false,
        default: false,
    },
});
</script>
