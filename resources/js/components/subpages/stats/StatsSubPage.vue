<template>
    <FunStatsSection />
    <section class="mb-16">
        <h1 class="text-3xl sm:text-4xl font-medium mb-2">Top genres</h1>
        <p class="mb-8">These genres were the most popular.</p>
    </section>

    <section class="mb-16">
        <h1 class="text-3xl sm:text-4xl font-medium mb-2">User facts</h1>
        <p class="mb-8">Here are some fun facts about the users in your mix.</p>
    </section>
</template>

<script setup>
import FunStatsSection from "@/components/subpages/stats/FunStatsSection.vue";
</script>
<style scoped>
[data-motion-number][style*="transform:"],
[data-motion-number] span[style*="transform:"] {
    will-change: transform !important;
}
</style>
