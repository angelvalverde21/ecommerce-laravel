<?php

namespace App\Providers;

use App\Models\Option;
use App\Models\OptionValue;
use App\Observers\OptionObserver;
use App\Observers\OptionValueObserver;
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
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap(['photo_session' => \App\Models\PhotoSession::class, 'production_stage_progress' => \App\Models\ProductionStageProgress::class]);
        //
        //  app()->usePublicPath('/home/tecnoplanet/3b.pe/laravel');
        Option::observe(OptionObserver::class);
        OptionValue::observe(OptionValueObserver::class);
    }
}
