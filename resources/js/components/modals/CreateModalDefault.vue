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
                ref="modalRef"
                class="fixed left-0 top-0 z-[9999] flex h-full w-full items-center justify-center gap-8 bg-black/25 backdrop-blur-md shadow-2xl"
                @click.self="closeModal"
            >
                <div
                    class="modern-border relative m-auto flex w-4/5 max-w-xl flex-col justify-center gap-4 overflow-hidden rounded-xl bg-modal-background border-2 border-modal-stroke p-8"
                >
                    <header :class="slots.title ? 'mb-6' : ''">
                        <slot name="title"></slot>
                    </header>
                    <main>
                        <slot name="body"></slot>
                    </main>
                    <footer>
                        <slot name="footer"></slot>
                    </footer>
                    <button
                        class="absolute right-2 top-0 cursor-pointer p-4 text-xl"
                        @click="closeModal"
                    >
                        <XMarkIcon class="size-5" />
                    </button>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<script setup>
import {
    defineProps,
    defineEmits,
    watch,
    onUnmounted,
    useSlots,
    ref,
    nextTick,
} from "vue";
import { XMarkIcon } from "@heroicons/vue/24/outline";
import { useFocusTrap } from "@vueuse/integrations/useFocusTrap";

const props = defineProps({
    isVisible: Boolean,
});

const modalRef = ref(null);
const { activate, deactivate } = useFocusTrap(modalRef);

const emit = defineEmits(["closeModal", "submitFromEnter"]);
const slots = useSlots();

function closeModal() {
    if (window.getSelection().toString().length > 0) {
        return;
    }
    emit("closeModal", false);
}

function handleKeyPressActions(event) {
    if (event.key === "Enter") {
        const activeElement = document.activeElement;
        const isInputFocused =
            activeElement &&
            ["INPUT", "TEXTAREA"].includes(activeElement.tagName);
        if (isInputFocused) {
            emit("submitFromEnter");
        }
    } else if (event.key === "Escape") {
        closeModal();
    }
}

let lastFocusedElement = null;

watch(
    () => props.isVisible,
    async (newValue) => {
        if (newValue) {
            lastFocusedElement = document.activeElement;
            await nextTick();
            window.addEventListener("keydown", handleKeyPressActions);
            activate();
        } else {
            window.removeEventListener("keydown", handleKeyPressActions);
            deactivate();
            lastFocusedElement?.focus();
        }
    }
);
</script>
