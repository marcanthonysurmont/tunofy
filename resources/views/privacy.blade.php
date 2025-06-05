@extends('layouts.landing')

@section('title', 'Privacy Policy | Tunofy')
@section('meta_description', 'Learn how Tunofy collects and uses your data in compliance with privacy regulations.')
@section('og_url', url('/privacy'))
{{-- @section('og_image', asset('images/social-preview.jpg'))
@section('twitter_image', asset('images/twitter-preview.jpg')) --}}
@section('page_id', 'privacy')

@section('content')
    @include('partials.navbar-policy')
    <section class="w-full max-w-[1728px] mt-44 md:mt-48 mx-auto flex-col flex mb-32 px-8">
        <div class="flex flex-col gap-2 mb-8 text-left">
            <h1 class="font-medium text-4xl md:text-5xl">Privacy Policy</h1>
            <p class="text-l md:text-xl font-nohemi text-zinc-400">Last updated: Apr 29, 2025</p>
        </div>
        <hr class="mb-8 text-zinc-700 rounded-lg">
        <div class="mb-8 copy">
            <p>Welcome to Tunofy! (“we”, “us” or “Tunofy”). We offer tools to help you monitor, analyze, and enhance
                your performance or content across platforms. To provide our services—whether you’re browsing
                https://tunofy.com/ (the “Site”) or using our dashboard and features (the “Platform”)—we need to collect
                and process some of your personal information. This Privacy Policy (the “Privacy Policy”) explains how
                we handle that data.</p>

            <p> By using our services, you agree to this Privacy Policy, which should be read in combination with our
                Terms of Use. If any term is undefined here, it follows the meaning in our Terms.</p>

            <p> We may update this Privacy Policy as legal requirements or our practices change. The latest version will
                always be available on our Site. If we make significant changes, we’ll do our best to notify you in
                advance. What counts as a “significant change” is at our discretion. Continued use of our services after
                updates means you agree to the revised policy. If not, please stop using Tunofy.</p>

            <p> This Privacy Policy only applies to personal data processed by Tunofy. It does not cover how third-party
                platforms or tools handle your data.</p>

            <p> We don’t knowingly collect data from anyone under 18. If you're under 18, please don’t use our services
                or share any information with us. If we become aware that we've collected data from someone under 18
                without verified parental consent, we’ll delete it immediately.</p>
        </div>
        <div class="copy mb-8">
            <h2 class="font-medium text-2xl md:text-3xl mb-2">Who processes your data</h1>
                <p>Your data is being processed by our company Tunofy.</p>

                <p>To learn more about data management or if you have any other questions, please contact us at
                    <strong>hello@tunofy.com</strong>.
                </p>
        </div>

        <div class="copy mb-8">
            <h2 class="font-medium text-2xl md:text-3xl mb-2">What data is processed</h2>
            <p>We may collect the following types of information about you:</p>
            <h3 class="text-md md:text-lg">Personal Identifiable Information</h3>
            <p>
                Through our Services, we may collect and process information that can be used to identify or contact
                you as an individual known as your personal identifiable information (“PII”), including but not
                limited to:
            </p>

            <ul class="list-disc pl-8 mb-8">
                <li>first and second names</li>
                <li>email address</li>
                <li>professional experience</li>
                <li>and other PII provided by you voluntarily when you use our Services</li>
            </ul>

            <h3 class="text-md md:text-lg">Technical Information</h3>
            <p>
                We and/or our authorised external service providers may automatically collect technical data when you
                visit or interact with our Services. Technical data may include, in particular:
            </p>
            <ul class="list-disc pl-8 mb-8">
                <li>the URL of the site visited before using our Service</li>
                <li>the time and date of user visits</li>
                <li>IP address</li>
                <li>the browser name and type</li>
                <li>the type of computer or device accessing our Service</li>
                <li>time spent on the Service and other similar technical information</li>
            </ul>
            <p>In a limited number of cases it is possible to use technical data and identify you as an individual, thus
                making them PII, however, we never use technical data to identify you as an individual.</p>
        </div>
    </section>
    @include('partials.footer')
@endsection
