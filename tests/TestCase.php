<?php

namespace Darvis\LivewireHoneypot\Tests;

use Darvis\LivewireHoneypot\HoneypotServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            HoneypotServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default config
        $app['config']->set('livewire-honeypot.minimum_fill_seconds', 5);
        $app['config']->set('livewire-honeypot.field_name', 'hp_website');
        $app['config']->set('livewire-honeypot.token_min_length', 10);
        $app['config']->set('livewire-honeypot.token_length', 24);
        
        // Setup app key for encryption
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
