<template>
    <div>
        <div class="h-12 w-3/4 bg-zinc-800 rounded mb-6 animate-pulse"></div>
        <div class="h-5 w-1/2 bg-zinc-800 rounded mb-8 animate-pulse"></div>

        <div
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 gap-4 mb-12"
        >
            <div
                v-for="i in 4"
                :key="i"
                class="bg-zinc-900 bg-opacity-50 border-2 border-zinc-800 p-4 rounded-lg h-32"
            >
                <div
                    class="h-6 w-3/4 bg-zinc-800 rounded mb-4 animate-pulse"
                ></div>
                <div class="h-4 bg-zinc-800 rounded mb-2 animate-pulse"></div>
                <div class="h-4 w-5/6 bg-zinc-800 rounded animate-pulse"></div>
            </div>
        </div>

        <div class="h-12 w-1/2 bg-zinc-800 rounded mb-6 animate-pulse"></div>
        <div class="h-5 w-3/4 bg-zinc-800 rounded mb-8 animate-pulse"></div>

        <div
            class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4 mb-8"
        >
            <div
                class="bg-zinc-900 bg-opacity-50 border-2 border-zinc-800 p-6 rounded-lg"
            >
                <div
                    class="h-7 w-1/3 bg-zinc-800 rounded mb-6 animate-pulse"
                ></div>
                <div class="flex flex-col gap-6">
                    <div v-for="i in 4" :key="i" class="flex flex-col gap-2">
                        <div
                            class="h-5 w-1/2 bg-zinc-800 rounded animate-pulse"
                        ></div>
                        <div
                            class="h-6 w-full bg-zinc-800 rounded animate-pulse"
                        ></div>
                    </div>
                </div>
            </div>

            <div
                class="bg-zinc-900 bg-opacity-50 border-2 border-zinc-800 p-6 rounded-lg"
            >
                <div
                    class="h-7 w-1/3 bg-zinc-800 rounded mb-6 animate-pulse"
                ></div>
                <div class="flex flex-col gap-6">
                    <div v-for="i in 4" :key="i" class="flex flex-col gap-2">
                        <div
                            class="h-5 w-1/2 bg-zinc-800 rounded animate-pulse"
                        ></div>
                        <div
                            class="h-6 w-full bg-zinc-800 rounded animate-pulse"
                        ></div>
                    </div>
                </div>
            </div>

            <div
                class="bg-zinc-900 bg-opacity-50 border-2 border-zinc-800 p-6 rounded-lg"
            >
                <div
                    class="h-7 w-1/3 bg-zinc-800 rounded mb-6 animate-pulse"
                ></div>
                <div class="flex flex-col gap-6">
                    <div class="flex flex-col gap-2">
                        <div
                            class="h-5 w-1/2 bg-zinc-800 rounded animate-pulse"
                        ></div>
                        <div
                            class="h-6 w-full bg-zinc-800 rounded animate-pulse"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="h-10 w-32 bg-zinc-800 rounded animate-pulse"></div>
    </div>
</template>
