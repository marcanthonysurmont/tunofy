<template>
    <div
        class="flex items-center justify-between border-t border-zinc-800 px-4 py-3 sm:px-6 mb-40 sm:mb-16"
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
                    <span class="font-medium">1</span>
                    to
                    <span class="font-medium">10</span>
                    of
                    <span class="font-medium">97</span>
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
                        v-html="link.label"
                        :aria-current="link.active ? 'page' : null"
                        class="relative font-body inline-flex items-center px-4 py-2 text-sm font-semibold ring-1 ring-inset ring-zinc-800 focus:z-20 text-zinc-300 hover:bg-zinc-800 transition-colors duration-150 ease-in-out"
                        :class="{
                            'z-10 bg-primary text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary':
                                link.active,
                            'text-zinc-500 cursor-not-allowed':
                                !link.url &&
                                link.label.toLowerCase().includes('previous'),
                            'text-zinc-500 cursor-not-allowed':
                                !link.url &&
                                link.label.toLowerCase().includes('next'),
                            'text-zinc-300 hover:bg-zinc-700':
                                link.url && !link.active,
                        }"
                    />
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps(["elements"]);

//this makes sure to remove the "1, 2, 3" links from the mobile view
const mobileLinks = props.elements.links.filter(
    (link) =>
        link.label.toLowerCase().includes("previous") ||
        link.label.toLowerCase().includes("next")
);
</script>
