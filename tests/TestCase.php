<?php

namespace Darvis\LivewireHoneypot\Tests;

use Darvis\LivewireHoneypot\HoneypotServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            HoneypotServiceProvider::class,
        ];
    }

    /**
     * Use the package defaults and an app key, which Livewire snapshots and signed tokens need.
     *
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('livewire-honeypot.minimum_fill_seconds', 5);
        $app['config']->set('livewire-honeypot.field_name', 'hp_website');
        $app['config']->set('livewire-honeypot.token_min_length', 10);
        $app['config']->set('livewire-honeypot.token_length', 24);
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
