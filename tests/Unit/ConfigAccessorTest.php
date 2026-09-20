<?php

declare(strict_types=1);

use Darvis\LivewireHoneypot\Support\HoneypotConfig;

/**
 * HoneypotConfig is the one place that reads the package config. These tests guard the two things
 * that go wrong once a default is written down twice: an accessor that disagrees with the config
 * file, and a caller that reaches past the accessor and keeps its own stale fallback.
 */
function packageRoot(string $path = ''): string
{
    return dirname(__DIR__, 2).($path === '' ? '' : '/'.$path);
}

it('returns the values the config file ships', function () {
    $config = require packageRoot('config/livewire-honeypot.php');

    expect(HoneypotConfig::fieldName())->toBe($config['field_name'])
        ->and(HoneypotConfig::minimumFillSeconds())->toBe($config['minimum_fill_seconds'])
        ->and(HoneypotConfig::maximumFillSeconds())->toBe($config['maximum_fill_seconds'])
        ->and(HoneypotConfig::tokenLength())->toBe($config['token_length']);
});

it('follows a changed setting', function () {
    config([
        'livewire-honeypot.field_name' => 'hp_reference',
        'livewire-honeypot.minimum_fill_seconds' => 2,
        'livewire-honeypot.maximum_fill_seconds' => 600,
        'livewire-honeypot.token_length' => 40,
    ]);

    expect(HoneypotConfig::fieldName())->toBe('hp_reference')
        ->and(HoneypotConfig::minimumFillSeconds())->toBe(2)
        ->and(HoneypotConfig::maximumFillSeconds())->toBe(600)
        ->and(HoneypotConfig::tokenLength())->toBe(40);
});

it('never lets the token length fall below one', function () {
    config(['livewire-honeypot.token_length' => 0]);

    expect(HoneypotConfig::tokenLength())->toBe(1);
});

it('is the only place in the package that reads the config', function () {
    $offenders = [];

    foreach (['src', 'resources', 'routes'] as $directory) {
        $path = packageRoot($directory);

        if (! is_dir($path)) {
            continue;
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace(packageRoot('').'/', '', $file->getPathname());

            if (str_contains($relative, 'HoneypotConfig.php')) {
                continue;
            }

            if (preg_match("/config\(['\"]livewire-honeypot\./", (string) file_get_contents($file->getPathname()))) {
                $offenders[] = $relative;
            }
        }
    }

    expect($offenders)->toBe([], 'these read the config directly instead of through HoneypotConfig');
});

it('keeps the config keys in alphabetical order', function () {
    preg_match_all(
        "/^    '([a-z_0-9]+)' =>/m",
        (string) file_get_contents(packageRoot('config/livewire-honeypot.php')),
        $matches
    );

    $keys = $matches[1];
    $sorted = $keys;
    sort($sorted);

    expect($keys)->toBe($sorted);
});
