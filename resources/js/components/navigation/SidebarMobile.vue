<template>
    <div
        class="flex grow flex-col gap-y-5 overflow-y-auto bg-navbar-background px-6 pb-2 ring-1 ring-white/10"
    >
        <div class="flex h-16 shrink-0 items-center">
            <Link :href="route('app')">
                <img
                    class="h-8 w-auto"
                    src="/images/logos/tunofy-logo-white.png"
                    alt="Tunofy"
                />
            </Link>
        </div>
        <nav class="flex flex-1 flex-col">
            <ul role="list" class="flex flex-1 flex-col gap-y-7">
                <li class="min-h-[200px] mb-8">
                    <div
                        class="flex flex-row justify-between items-center mb-6"
                    >
                        <h1 class="text-2xl font-medium heading-center">
                            Your Mixes
                        </h1>
                        <PlusIcon
                            @click="emit('show-add-mix-modal')"
                            class="size-8 text-white bg-primary p-1.5 cursor-pointer rounded-lg custom-item-hover"
                        />
                    </div>
                    <ul role="list" class="flex flex-col gap-y-4">
                        <YourMixesList />
                    </ul>
                </li>
                <li class="min-h-[200px] mb-8">
                    <div class="flex flex-row justify-between items-center">
                        <h1 class="text-2xl font-medium heading-center">
                            Joined Mixes
                        </h1>
                        <PlusIcon
                            @click="emit('show-join-mix-modal')"
                            class="size-8 text-white bg-primary p-1.5 cursor-pointer rounded-lg custom-item-hover"
                        />
                    </div>
                    <ul role="list" class="flex flex-col gap-y-4">
                        <JoinedMixesList />
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
</template>

<script setup>
import JoinedMixesList from "@/components/mixes/JoinedMixesList.vue";
import YourMixesList from "@/components/mixes/YourMixesList.vue";
import { Link } from "@inertiajs/vue3";
import { PlusIcon } from "@heroicons/vue/24/outline";

const emit = defineEmits(["show-join-mix-modal", "show-add-mix-modal"]);
</script>
