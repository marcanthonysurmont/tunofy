<section x-data="{ show: false }" x-init="setTimeout(() => show = true, 100)"
    :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-5'"
    class="transition-all duration-1000 ease-out w-full max-w-[1728px] mt-24 md:xs-32 md:mt-48 mx-auto flex-col bg-hero flex items-center justify-center py-8">
    <section class="items-center flex flex-col gap-8 justify-center h-[600px] w-max-[875px] mx-8">
        <h1 class="text-6xl sm:text-7xl md:text-8xl font-headings text-center max-w-[875px]">The music platform you
            <span class="text-primary custom-underline">need</span>
        </h1>
        <p class="font-headings text-2xl sm:tex   t-3xl md:text-4xl max-w-[953px] text-center text-gradient">
            Join the ultimate music
            experience —
            create, vote
            on tracks,
            and control the
            music together.</p>
        <div class="flex flex-row gap-4 items-center justify-center mt-8">
            <a href=""
                class="custom-item-hover font-headings font-normal bg-primary rounded-md px-4 py-2 flex items-center justify-center text-lg md:text-2xl">Join
                now</a>
            <a href="#key-features"
                class="font-headings font-normal text-lg md:text-2xl flex items-center gap-2 custom-item-hover">View
                features<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </a>
        </div>
    </section>
</section>
