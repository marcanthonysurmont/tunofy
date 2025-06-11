<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('spotify', \SocialiteProviders\Spotify\Provider::class);
        });

        if (app()->isLocal()) {
            Model::preventLazyLoading(!app()->isProduction());
        };

        Model::handleLazyLoadingViolationUsing(function ($model, $relation) {
            throw new \Exception("N+1 detected: [{$relation}] was lazy loaded on " . get_class($model));
        });
    }
}
