@php
    $steps = [
        [
            'step' => '1',
            'title' => 'Create a mix',
            'description' =>
                'Easily create a new mix with your Spotify account. Create a unique session code and invite your friends to join.',
        ],
        [
            'step' => '2',
            'title' => 'Add music & vote',
            'description' =>
                'Everyone with the right permissions can add songs via a simple search and vote for their favorite tracks.',
        ],
        [
            'step' => '3',
            'title' => 'Listen & enjoy',
            'description' =>
                'The owner or appointed co-DJ plays the music live. Watch votes in real-time and discover the best vibes together.',
        ],
    ];
@endphp

<section class="w-full max-w-[1728px] mt-32 md:mt-48 mx-auto flex-col flex items-center justify-center mb-32 px-8"
    id="how-it-works">
    <div class="flex flex-col gap-10 items-center justify-center mb-16 text-center">
        <h2 class="font-medium text-3xl md:text-4xl">How it works</h2>
        <p class="text-xl md:text-2xl font-headings">Trust us, it's as simple as pretending to like your friend's
            playlist.</p>
    </div>
    <div class="flex flex-col lg:grid lg:grid-cols-3 w-full gap-8">
        @foreach ($steps as $step)
            <div class="bg-card-background p-4 rounded-lg border-2 border-card-stroke">
                <div class="flex flex-row gap-4 items-center mb-4">
                    <span
                        class="font-headings text-2xl md:text-3xl bg-primary rounded-full w-12 h-12 flex items-center justify-center text-white leading-none pt-[2px]">
                        {{ $step['step'] }}</span>
                    <h3 class="text-2xl md:text-3xl">{{ $step['title'] }}</h3>
                </div>
                <p class="text-xl font-normal">{{ $step['description'] }}</p>
            </div>
        @endforeach
    </div>
</section>
