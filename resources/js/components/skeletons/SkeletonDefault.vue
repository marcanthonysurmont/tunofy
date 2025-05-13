<template>
    <div class="w-full space-y-6">
        <div class="flex items-center justify-between">
            <div class="h-10 w-1/3 bg-zinc-800 rounded-md animate-pulse"></div>
            <div class="h-8 w-16 bg-zinc-800 rounded-md animate-pulse"></div>
        </div>

        <div class="flex flex-col gap-4">
            <div class="h-6 w-2/3 bg-zinc-800 rounded-md animate-pulse"></div>
            <div class="h-4 w-5/6 bg-zinc-800 rounded-md animate-pulse"></div>
            <div class="h-4 w-3/4 bg-zinc-800 rounded-md animate-pulse"></div>
        </div>

        <div class="h-40 w-full bg-zinc-800 rounded-lg animate-pulse"></div>

        <div class="flex flex-col gap-4">
            <div
                v-for="n in 4"
                :key="n"
                class="flex items-center gap-6 p-4 rounded-lg bg-zinc-900 bg-opacity-50 w-full"
            >
                <div
                    class="h-12 w-12 bg-zinc-800 rounded-full animate-pulse"
                ></div>
                <div class="flex flex-col gap-2 flex-1">
                    <div
                        class="h-5 w-1/2 bg-zinc-800 rounded-md animate-pulse"
                    ></div>
                    <div
                        class="h-4 w-2/3 bg-zinc-800 rounded-md animate-pulse"
                    ></div>
                </div>
                <div class="h-8 w-8 bg-zinc-800 rounded-md animate-pulse"></div>
            </div>
        </div>

        <div class="h-12 w-32 bg-zinc-800 rounded-lg animate-pulse"></div>
    </div>
</template>
