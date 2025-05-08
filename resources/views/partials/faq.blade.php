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
                'question' => 'Do I need Spotify premium to use this platform?',
                'answer' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
            ],
            [
                'question' => 'How does voting work during a jam?',
                'answer' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
            ],
            [
                'question' => 'Is this web application free?',
                'answer' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
            ],
            [
                'question' => 'Can I customize the look and theme of my jam session?',
                'answer' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
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
                    <div class="px-4 pb-4">
                        <p class="text-l font-normal">{{ $faq['answer'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>
