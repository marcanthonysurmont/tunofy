<template>
    <FunStatsSection />
    <GenreStatsSection />
    <section class="mb-16">
        <h1 class="text-3xl sm:text-4xl font-medium mb-2">User facts</h1>
        <p class="mb-8">Here are some fun facts about the users in your mix.</p>
    </section>
</template>

<script setup>
import FunStatsSection from "@/components/subpages/stats/FunStatsSection.vue";
import GenreStatsSection from "@/components/subpages/stats/GenreStatsSection.vue";
</script>
