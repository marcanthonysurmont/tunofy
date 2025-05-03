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
                class="modern-border relative m-auto flex w-4/5 max-w-xl flex-col justify-center gap-4 overflow-hidden rounded-xl bg-modal-background border-2 border-modal-stroke p-8"
            >
                <header class="mb-6">
                    <slot name="title"></slot>
                </header>
                <main>
                    <slot name="body"></slot>
                </main>
                <footer>
                    <slot name="footer"></slot>
                </footer>
                <span
                    class="absolute right-2 top-0 cursor-pointer p-4 text-xl"
                    @click="closeModal"
                    ><XMarkIcon class="size-5"
                /></span>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { defineProps, defineEmits, watch, onUnmounted } from "vue";
import { XMarkIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    isVisible: Boolean,
});

const emit = defineEmits(["closeModal", "submitFromEnter"]);

function closeModal() {
    console.log("closeModal");
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

//not using onmounted here, im using a watch instead
//why: because the isVisible prop is reactive and we want to add the event listener when the modal is visible and remove it when it's not
//i noticed some bugs with onMounted as well, it would not work well.
watch(
    () => props.isVisible,
    (newValue) => {
        if (newValue) {
            window.addEventListener("keydown", handleKeyPressActions);
        } else {
            window.removeEventListener("keydown", handleKeyPressActions);
        }
    }
);

onUnmounted(() => {
    window.removeEventListener("keydown", handleKeyPressActions);
});
</script>
