<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
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
        Carbon::setLocale(config('app.locale')); // diffForHumans em pt-BR

        // @brl($valor) → R$ 1.234,56
        Blade::directive('brl', fn ($valor) => "<?php echo 'R$ '.number_format((float) ($valor), 2, ',', '.'); ?>");
    }
}
