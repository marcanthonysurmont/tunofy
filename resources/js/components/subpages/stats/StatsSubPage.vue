<template>
    <section class="mb-16">
        <h1 class="text-3xl sm:text-4xl font-medium mb-2">Fun stats</h1>
        <!-- <p class="mb-8">Blablabla</p> -->
        <div class="grid grid-cols-1sm:grid-cols-2 lg:grid-cols-4 gap-2">
            <div
                class="bg-card-background px-4 py-6 sm:px-6 lg:px-8 border-card-stroke border-2 rounded-sm"
            >
                <p class="text-sm/6 font-medium text-zinc-400">
                    Amount of songs
                </p>
                <p class="mt-2 flex items-baseline gap-x-2">
                    <span
                        class="text-4xl font-semibold tracking-tight text-white"
                        ><NumberFlow :value="amountOfSongs"
                    /></span>
                </p>
            </div>
            <div
                class="bg-card-background px-4 py-6 sm:px-6 lg:px-8 border-card-stroke border-2 rounded-sm"
            >
                <p class="text-sm/6 font-medium text-zinc-400">
                    Average song kills
                </p>
                <p class="mt-2 flex items-baseline gap-x-2">
                    <span
                        class="text-4xl font-semibold tracking-tight text-white"
                        ><NumberFlow :value="amountOfKills"
                    /></span>
                    <span class="text-sm text-zinc-400">kills</span>
                </p>
            </div>
            <div
                class="bg-card-background px-4 py-6 sm:px-6 lg:px-8 border-card-stroke border-2 rounded-sm"
            >
                <p class="text-sm/6 font-medium text-zinc-400">
                    Most votes for a song
                </p>
                <p class="mt-2 flex items-baseline gap-x-2">
                    <span
                        class="text-4xl font-semibold tracking-tight text-white"
                        ><NumberFlow :value="amountOfVotesForSong"
                    /></span>
                </p>
            </div>
            <div
                class="bg-card-background px-4 py-6 sm:px-6 lg:px-8 border-card-stroke border-2 rounded-sm"
            >
                <p class="text-sm/6 font-medium text-zinc-400">
                    Minutes listened
                </p>
                <p class="mt-2 flex items-baseline gap-x-2">
                    <span
                        class="text-4xl font-semibold tracking-tight text-white"
                        ><NumberFlow :value="amountOfMinutesListened"
                    /></span>
                </p>
            </div>
        </div>
    </section>

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
import NumberFlow from "@number-flow/vue";
import { onMounted, ref } from "vue";

const amountOfKills = ref(0);
const amountOfSongs = ref(0);
const amountOfVotesForSong = ref(0);
const amountOfMinutesListened = ref(0);

onMounted(() => {
    //changing the values to trigger numberflow animation
    amountOfKills.value = 5;
    amountOfSongs.value = 405;
    amountOfVotesForSong.value = 10;
    amountOfMinutesListened.value = 8593;
});
</script>
