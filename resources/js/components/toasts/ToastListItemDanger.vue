<template>
    <transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="visible"
            class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-[#2A2D33] shadow-lg ring-1 ring-zinc-700"
            role="alert"
        >
            <div class="p-4">
                <div class="flex items-center">
                    <div class="shrink-0">
                        <XCircleIcon
                            class="h-6 w-6 text-red-400"
                            aria-hidden="true"
                        />
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-zinc-200">
                            {{ message }}
                        </p>
                    </div>
                    <div class="ml-4 flex shrink-0">
                        <button
                            type="button"
                            @click="emit('removeParent')"
                            class="inline-flex rounded-md bg-[#2A2D33] text-zinc-500 hover:text-white focus:outline-none"
                        >
                            <span class="sr-only">Close</span>
                            <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { XCircleIcon } from "@heroicons/vue/24/outline";
import { XMarkIcon } from "@heroicons/vue/20/solid";

const props = defineProps({
    message: {
        type: String,
        default: "This is a message",
    },
    duration: {
        type: Number,
        default: 3000,
    },
});

const emit = defineEmits(["removeParent"]);

const visible = ref(true);

onMounted(() => {
    setTimeout(() => {
        visible.value = false;
        emit("removeParent");
    }, props.duration);
});
</script>
