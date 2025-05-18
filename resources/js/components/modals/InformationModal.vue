<template>
    <transition
        enter-active-class="transition duration-100 ease-out"
        enter-from-class="transform scale-95 opacity-0"
        enter-to-class="transform scale-100 opacity-100"
        leave-active-class="transition duration-75 ease-in"
        leave-from-class="transform scale-100 opacity-100"
        leave-to-class="transform scale-95 opacity-0"
    >
        <div
            v-if="isVisible"
            class="fixed left-0 top-0 z-[999] flex h-full w-full items-center justify-center gap-8 bg-black/25 backdrop-blur-md shadow-2xl"
            @click.self="closeModal"
        >
            <div
                class="relative m-auto flex w-4/5 max-w-xl flex-col justify-center gap-2 overflow-hidden rounded-xl bg-modal-background border-2 border-modal-stroke p-8"
            >
                <header class="mb-1 flex flex-row items-center gap-4">
                    <div class="flex-shrink-0">
                        <ExclamationTriangleIcon
                            class="size-10 text-white p-2 bg-primary rounded-full"
                        />
                    </div>
                    <h1 class="text-2xl sm:text-3xl md:text-4xl">
                        {{ title }}
                    </h1>
                </header>
                <main class="mb-4">
                    <p
                        class="text-white flex items-center gap-1 mt-2 text-base"
                    >
                        {{ text }}
                    </p>
                    <slot />
                </main>
                <footer class="mt-6 flex justify-end">
                    <RegularButton class="w-full" @click="closeModal"
                        >I understand</RegularButton
                    >
                </footer>
                <span
                    class="absolute right-2 top-0 cursor-pointer p-4 text-xl"
                    @click="closeModal"
                >
                    <XMarkIcon class="size-5 text-white" />
                </span>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { defineProps, defineEmits } from "vue";
import { XMarkIcon } from "@heroicons/vue/24/outline";
import RegularButton from "@/components/buttons/RegularButton.vue";
import { ExclamationTriangleIcon } from "@heroicons/vue/24/solid";

const props = defineProps({
    isVisible: Boolean,
    title: {
        type: String,
        default: "Please note",
    },
    text: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["closeModal"]);

function closeModal() {
    emit("closeModal", false);
}
</script>
