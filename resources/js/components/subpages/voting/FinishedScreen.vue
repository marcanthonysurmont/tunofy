<template>
    <div class="flex flex-col items-center max-w-md mx-6 rounded-xl">
        <h1 class="text-5xl text-center text-white mb-2">Finished!</h1>
        <p class="text-center mb-6">
            Thanks for voting! Once the session ends, songs will play based on
            everyone's votes. Your vote counted!
        </p>
        <RegularButton color="blue" class="w-full max-w-sm" @click="closeWindow"
            >Close window</RegularButton
        >
    </div>
</template>

<script setup>
import RegularButton from "@/components/buttons/RegularButton.vue";

const emit = defineEmits(["closeWindow"]);

function closeWindow() {
    emit("closeWindow");
}
</script>
