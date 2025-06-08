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

const props = defineProps({
    isVisible: Boolean,
});

const modalRef = ref(null);

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
let focusableElements = [];
const focusableSelectors =
    'a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, object, embed, [tabindex]:not([tabindex="-1"]), [contenteditable]';

function trapFocus(event) {
    if (event.key !== "Tab") return;

    const first = focusableElements[0];
    const last = focusableElements[focusableElements.length - 1];

    if (event.shiftKey) {
        if (document.activeElement === first) {
            event.preventDefault();
            last.focus();
        }
    } else {
        if (document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }
}

//not using onmounted here for visibility, im using a watch instead
//why: because the isVisible prop is reactive and we want to add the event listener when the modal is visible and remove it when it's not
//i noticed some bugs with onMounted as well, it would not work well.
watch(
    () => props.isVisible,
    async (newValue) => {
        if (newValue) {
            //save the currently focused element to return focus later
            lastFocusedElement = document.activeElement;

            //wait for dom changes
            await nextTick();

            const modal = modalRef.value;

            if (!modal) {
                return;
            }

            focusableElements = Array.from(
                modal.querySelectorAll(focusableSelectors)
            );

            //focus first element
            focusableElements[0]?.focus();

            window.addEventListener("keydown", handleKeyPressActions);
            window.addEventListener("keydown", trapFocus);
        } else {
            window.removeEventListener("keydown", handleKeyPressActions);
            window.removeEventListener("keydown", trapFocus);

            //restore focus to previous element
            lastFocusedElement?.focus();
        }
    }
);

onUnmounted(() => {
    window.removeEventListener("keydown", handleKeyPressActions);
});
</script>
