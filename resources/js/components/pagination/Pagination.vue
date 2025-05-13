<template>
    <div
        v-if="elements.links.length !== 3"
        class="flex items-center justify-between border-t border-zinc-800 py-3 mb-40 sm:mb-16"
    >
        <div class="flex flex-1 justify-between sm:hidden">
            <Link
                v-for="link in mobileLinks"
                :key="link.label"
                :href="link.url || ''"
                class="relative inline-flex items-center rounded-md border border-zinc-800 px-4 py-2 text-sm font-medium text-zinc-300 hover:bg-zinc-700"
                :class="{ 'cursor-not-allowed text-zinc-500': !link.url }"
                v-html="link.label"
            />
        </div>

        <div
            class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between"
        >
            <div>
                <p class="text-sm text-zinc-300">
                    Showing
                    {{ " " }}
                    <span class="font-medium">{{ elements.from }}</span>
                    to
                    <span class="font-medium">{{ elements.to }}</span>
                    of
                    <span class="font-medium">{{ elements.total }}</span>
                    results
                </p>
            </div>
            <div>
                <nav
                    class="isolate inline-flex -space-x-px rounded-md shadow-sm"
                    aria-label="Pagination"
                >
                    <Link
                        v-for="(link, index) in props.elements.links"
                        :key="index"
                        :href="link.url || ''"
                        :aria-current="link.active ? 'page' : null"
                        class="relative font-body inline-flex items-center px-4 py-2 text-sm font-semibold ring-1 ring-inset ring-zinc-800 focus:z-20 text-zinc-300 hover:bg-zinc-800 transition-colors duration-150 ease-in-out"
                        :class="{
                            'z-10 bg-primary text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary':
                                link.active,
                            'text-zinc-500 cursor-not-allowed':
                                !link.url &&
                                (link.label
                                    .toLowerCase()
                                    .includes('previous') ||
                                    link.label.toLowerCase().includes('next')),
                            'text-zinc-300 hover:bg-zinc-700':
                                link.url && !link.active,
                        }"
                    >
                        <template
                            v-if="link.label.toLowerCase().includes('previous')"
                        >
                            <ChevronLeftIcon class="h-4 w-4 text-white" />
                        </template>
                        <template
                            v-else-if="
                                link.label.toLowerCase().includes('next')
                            "
                        >
                            <ChevronRightIcon class="h-4 w-4 text-white" />
                        </template>
                        <template v-else>
                            {{ link.label }}
                        </template>
                    </Link>
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ChevronLeftIcon, ChevronRightIcon } from "@heroicons/vue/24/solid";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    elements: {
        type: Object,
    },
});

console.log(props.elements);

//this makes sure to remove the "1, 2, 3" links from the mobile view
const mobileLinks = props.elements.links.filter(
    (link) =>
        link.label.toLowerCase().includes("previous") ||
        link.label.toLowerCase().includes("next")
);

const handleClick = (link) => {
    if (!link.url) return; // do nothing if no URL
};
</script>
