<template>
    <div class="flex flex-col gap-2">
        <div
            class="flex flex-col sm:flex-row gap-4 md:gap-8 w-full items-center relative z-10 py-2 pr-2"
        >
            <div
                class="size-40 sm:size-64 flex-shrink-0 rounded-md bg-zinc-800 animate-pulse"
            ></div>
            <div
                class="h-9 sm:h-10 md:h-12 bg-zinc-800 rounded w-3/4 animate-pulse sm:hidden"
            ></div>

            <div
                class="flex flex-col justify-center items-center sm:items-start gap-3 flex-1 overflow-hidden min-w-0 py-2 text-center sm:text-left"
            >
                <div
                    class="h-9 sm:h-10 md:h-12 bg-zinc-800 rounded w-3/4 animate-pulse"
                ></div>

                <div class="flex flex-col sm:flex-row items-center w-full">
                    <div
                        class="h-6 bg-zinc-800 rounded w-1/2 animate-pulse"
                    ></div>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 mt-2 sm:mt-0">
            <div
                class="flex flex-row gap-3 justify-center items-center sm:justify-start"
            >
                <div
                    class="size-6 sm:size-8 bg-zinc-800 rounded-full animate-pulse"
                ></div>
                <div
                    class="size-6 sm:size-8 bg-zinc-800 rounded-full animate-pulse"
                ></div>
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-4 w-full">
            <div
                v-for="n in 5"
                :key="n"
                class="flex flex-row gap-3 items-center p-3 rounded-md bg-zinc-900 bg-opacity-50"
            >
                <div class="size-10 bg-zinc-800 rounded-md animate-pulse"></div>
                <div class="flex flex-col gap-2 flex-1">
                    <div
                        class="h-5 bg-zinc-800 rounded w-1/2 animate-pulse"
                    ></div>
                    <div
                        class="h-4 bg-zinc-800 rounded w-1/3 animate-pulse"
                    ></div>
                </div>
                <div
                    class="size-6 bg-zinc-800 rounded-full animate-pulse"
                ></div>
            </div>
        </div>
    </div>
</template>
