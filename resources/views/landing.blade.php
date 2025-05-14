@extends('layouts.landing')

@section('title', 'Tunofy - The music platform you need')
@section('meta_description', 'Vibe together using Spotify! Add tracks, vote, and control the vibe.')
@section('og_url', url('/'))
{{-- @section('og_image', asset('images/social-preview.jpg'))
@section('twitter_image', asset('images/twitter-preview.jpg')) --}}
@section('page_id', 'home')

@section('content')
    @include('partials.navbar-landing')
    @include('partials.hero')
    @include('partials.how-it-works')
    @include('partials.features')
    @include('partials.faq')
    @include('partials.footer')
@endsection
