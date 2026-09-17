<?php

namespace Darvis\LivewireHoneypot;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class HoneypotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/livewire-honeypot.php',
            'livewire-honeypot'
        );
    }

    /**
     * Register the <x-honeypot /> component and the publishable views, translations and config.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'livewire-honeypot');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'livewire-honeypot');

        Blade::component('livewire-honeypot::components.honeypot', 'honeypot');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/livewire-honeypot'),
        ], 'livewire-honeypot-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/livewire-honeypot'),
        ], 'livewire-honeypot-translations');

        $this->publishes([
            __DIR__.'/../config/livewire-honeypot.php' => config_path('livewire-honeypot.php'),
        ], 'livewire-honeypot-config');
    }
}
