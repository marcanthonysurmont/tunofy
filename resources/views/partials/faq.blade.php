<section
    class="w-full max-w-[1728px] mt-32 md:mt-48 lg:mt-64 mx-auto flex-col flex items-center justify-center mb-32 px-4"
    id="faq">
    <div class="flex flex-col gap-10 items-center justify-center mb-16 text-center">
        <h2 class="font-medium text-3xl md:text-4xl">Frequently Asked Questions (FAQ)</h2>
        <p class="text-xl md:text-2xl font-headings">Got doubts and questions? Let’s break it down.</p>
    </div>
    @php
        $faqs = [
            [
                'question' => 'How does the playback work?',
                'answer' =>
                    'Playback works by using the Spotify API to control the playback of your spotify device. You can play, pause, skip, and seek through the track using the playback controls on the web app. You can also see what track is currently playing and who is currently playing it!',
            ],
            [
                'question' => 'Do I need Spotify premium to use this platform?',
                'answer' =>
                    'Technically, no. However, you do need Spotify Premium to use our playback feature. You can always assign another Premium user as the co-DJ to allow playback.',
            ],
            [
                'question' => 'Do my guests need Spotify to use this platform?',
                'answer' =>
                    'Yes, guests do need a Spotify account to use this platform. However, they do not need Spotify Premium.',
            ],
            [
                'question' => 'Is this web application free?',
                'answer' =>
                    'Yes! This web application is completely free to use. We do not charge any fees for using the platform. We might however add a some "premium" features in the future, but the core functionality will always be free.',
            ],
            [
                'question' => 'Do you use my Spotify data?',
                'answer' => 'For more information on data, please refer to our privacy policy.',
            ],
            [
                'question' => 'Can I customize the look and theme of my mix session?',
                'answer' =>
                    'Of course! We have a few themes - birthday party, halloween, .. - to choose from with some customization options. You can also add a custom cover image to your mix session.',
            ],
        ];
    @endphp
    <div class="w-full">
        @foreach ($faqs as $faq)
            <div x-data="{ open: false }" class="border-b rounded-lg border-card-stroke py-4 transition-all duration-500">
                <button @click="open = !open"
                    class="flex items-center justify-between w-full p-4 text-left font-medium cursor-pointer">
                    <p class="font-headings text-xl font-light">{{ $faq['question'] }}</p>
                    <svg :class="{ 'rotate-180': open }" class="w-6 h-6 transition-transform duration-300 ml-4"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div class="relative overflow-hidden transition-all max-h-0 duration-400" x-ref="container"
                    x-bind:style="open ? 'max-height: ' + $refs.container.scrollHeight + 'px' : ''">
                    <div class="px-4 pb-4 max-w-7xl">
                        <p class="text-l font-normal">{{ $faq['answer'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>
