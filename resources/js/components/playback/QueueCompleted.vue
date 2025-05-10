<template>
    <Teleport to="body">
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
                class="fixed left-0 top-0 z-[9999] flex h-full w-full items-center justify-center gap-8 bg-black/25 backdrop-blur-md shadow-2xl"
                @click.self="closeModal"
            >
                <div
                    class="modern-border relative m-auto flex w-4/5 max-w-xl flex-col justify-center gap-4 overflow-hidden rounded-xl bg-modal-background border-2 border-modal-stroke p-8"
                >
                    <header class="mb-6 text-center">
                        <h1 class="text-4xl mb-2">Queue completed</h1>
                        <p class="text-base text-zinc-300">
                            The queue has been completed. Add more songs to keep
                            the queue going!
                        </p>
                    </header>
                    <footer>
                        <RegularButton
                            color="blue"
                            class="w-full"
                            @click="$emit('closeModal')"
                            >I understand.</RegularButton
                        >
                    </footer>
                    <span
                        class="absolute right-2 top-0 cursor-pointer p-4 text-xl"
                        @click="emit('closeModal')"
                        ><XMarkIcon class="size-5"
                    /></span>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<script setup>
import RegularButton from "@/components/buttons/RegularButton.vue";
import { XMarkIcon } from "@heroicons/vue/24/outline";
const emit = defineEmits(["closeModal"]);

const props = defineProps({
    isVisible: Boolean,
});
</script>
