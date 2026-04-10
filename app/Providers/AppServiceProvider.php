<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        \App\Models\StoryTask::observe(\App\Observers\StoryTaskObserver::class);
        \App\Models\Feature::observe(\App\Observers\FeatureObserver::class);
    }
}
