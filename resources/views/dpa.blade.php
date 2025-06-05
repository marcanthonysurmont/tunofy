@extends('layouts.landing')

@section('title', 'Data Processing Agreement | Tunofy')
@section('meta_description',
    "Read Tunofy's Data Processing Agreement to understand how we comply with GDPR and process
    personal data securely and transparently.")
@section('og_url', url('/dpa'))
{{-- @section('og_image', asset('images/social-preview.jpg'))
@section('twitter_image', asset('images/twitter-preview.jpg')) --}}
@section('page_id', 'dpa')

@section('content')
    @include('partials.navbar-policy')
    <section class="w-full max-w-[1728px] mt-44 md:mt-48 mx-auto flex-col flex mb-32 px-8">
        <div class="flex flex-col gap-2 mb-8 text-left">
            <h1 class="font-medium text-4xl md:text-5xl">Data Processing Agreement (WIP)</h1>
            <p class="text-l md:text-xl font-nohemi text-zinc-400">Last updated: Apr 29, 2025</p>
        </div>
        <hr class="mb-8 text-zinc-700 rounded-lg">
        <div class="mb-16">
            <p>This Data Processing Agreement ("DPA") is entered into by the Tunofy customer identified on the
                applicable Tunofy ordering or registration document ("Customer") and Tunofy, the company providing
                the
                platform and services. It governs the processing of personal data that the Customer uploads or
                otherwise
                provides to Tunofy in connection with the use of the Tunofy platform, as well as any personal data
                that
                Tunofy uploads or provides to the Customer in connection with the services.</p>
        </div>
        <div class="copy mb-8">
            <h1 class="font-medium text-2xl md:text-3xl mb-2">1. Definitions</h1>
            <p>"Account Data" means Personal Data that relates to Customer’s relationship with Tunofy,
                including access to Customer’s account and billing information, identity verification, maintaining or
                improving
                performance of the Services, providing support, investigating and preventing system abuse, or fulfilling
                legal
                obligations.</p>

            <p>"Applicable Data Protection Legislation" refers to laws and regulations applicable to Tunofy's
                processing of personal data under the Agreement, including (a) the GDPR, (b) the GDPR as saved into
                United Kingdom law by virtue of section 3 of the United Kingdom's European Union (Withdrawal) Act 2019
                ("UK GDPR") and the Data Protection Act 2018 (together, "UK Laws"), (c) the Swiss Federal Data
                Protection Act and its implementing regulations ("Swiss DPA"), and (d) CCPA, in each case, as may be
                amended, superseded or replaced.</p>

            <p>"Customer Personal Data" means Personal Data Tunofy processes as a Processor on behalf of
                Customer.</p>

            <p>"CCPA" means the California Consumer Privacy Act of 2018 including regulations adopted in following
                years, including the California Privacy Rights Act of 2020.</p>
        </div>

        <div class="copy mb-8">
            <h1 class="font-medium text-2xl md:text-3xl mb-2">2. Nature of Data Processing</h1>
            <p>Tunofy processes Customer Personal Data solely for providing its services, including access to
                Customer’s account and billing information, identity verification, maintaining or improving performance,
                providing support, investigating and preventing abuse, and fulfilling legal obligations.</p>
        </div>

        <div class="copy mb-8">
            <h1 class="font-medium text-2xl md:text-3xl mb-2">3. Compliance with Laws</h1>
            <p>The parties shall each comply with their respective obligations under all Applicable Data Protection
                Legislation.</p>
        </div>
        <div class="copy mb-8">
            <h1 class="font-medium text-2xl md:text-3xl mb-2">4. Customer Obligations</h1>
            <p>Customer agrees to:</p>
            <ol class="list-decimal pl-5">
                <li>Provide instructions to Tunofy and determine the purposes and general means of Tunofy’s processing
                    of Customer Personal Data in accordance with the DPA;</li>
                <li>Comply with its protection, security, and other obligations with respect to Customer Personal Data
                    prescribed by Applicable Data Protection Legislation for data controllers by:
                    <ul class="list-inside list-disc pl-5">
                        <li>Establishing and maintaining a procedure for the exercise of the rights of the individuals
                            whose Customer Personal Data are processed on behalf of Customer;</li>
                        <li>Processing only data that has been lawfully and validly collected and ensuring that such
                            data will be relevant and proportionate to the respective uses;</li>
                        <li>Ensuring compliance with the provisions of this DPA by its personnel or by any third-party
                            accessing or using Customer Personal Data on its behalf.</li>
                    </ul>
                </li>
            </ol>
        </div>
    </section>
    @include('partials.footer')
@endsection
