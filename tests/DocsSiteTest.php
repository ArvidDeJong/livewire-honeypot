<?php

/**
 * Guards the rules of the GitHub Pages site in docs/: front matter, Liquid use,
 * single-source facts and the developer credit. Nothing here breaks loudly otherwise.
 */
function docsPath(string $path = ''): string
{
    return dirname(__DIR__).'/docs'.($path === '' ? '' : '/'.$path);
}

/**
 * @return array<string, string>
 */
function frontMatter(string $file): array
{
    preg_match('/\A---\n(.*?)\n---\n/s', (string) file_get_contents($file), $match);

    $values = [];
    foreach (explode("\n", $match[1] ?? '') as $line) {
        if (preg_match('/^(\w+):\s*(.*)$/', $line, $pair)) {
            $values[$pair[1]] = trim($pair[2]);
        }
    }

    return $values;
}

test('every page has a title, a unique description and a unique nav order', function () {
    $pages = glob(docsPath('*.md'));
    $descriptions = [];
    $navOrders = [];

    foreach ($pages as $page) {
        $meta = frontMatter($page);

        expect($meta)->toHaveKeys(['title', 'description', 'nav_order'], basename($page));
        $descriptions[] = $meta['description'];
        $navOrders[] = $meta['nav_order'];
    }

    expect(count($pages))->toBeGreaterThan(5);
    expect(array_unique($descriptions))->toHaveCount(count($pages));
    expect(array_unique($navOrders))->toHaveCount(count($pages));
});

test('pages use Liquid only where it is intended', function () {
    foreach (glob(docsPath('*.md')) as $page) {
        if (basename($page) === 'faq.md') {
            continue;
        }

        expect(file_get_contents($page))->not->toMatch('/\{\{|\{%/', basename($page));
    }
});

test('the FAQ, structured data and llms.txt read from the shared data', function () {
    $faq = (string) file_get_contents(docsPath('_data/faq.yml'));

    expect(substr_count($faq, "\n- q: ") + (str_starts_with(ltrim($faq), '- q: ') ? 1 : 0))->toBeGreaterThanOrEqual(6);
    expect(substr_count($faq, '- q: '))->toBe(substr_count($faq, '  a: '));

    expect(file_get_contents(docsPath('faq.md')))->toContain('site.data.faq');
    expect(file_get_contents(docsPath('llms.txt')))
        ->toContain('permalink: /llms.txt')
        ->toContain('site.data.faq')
        ->toContain('site.pages');
    expect(file_get_contents(docsPath('_includes/head_custom.html')))
        ->toContain('"FAQPage"')
        ->toContain('"SoftwareSourceCode"')
        ->toContain('site.data.faq');
});

test('the config holds the package facts and the sitemap plugin', function () {
    expect(file_get_contents(docsPath('_config.yml')))
        ->toContain('- jekyll-sitemap')
        ->toContain('name: darvis/livewire-honeypot')
        ->toContain('company: ARVID.NL')
        ->toContain('url: https://arvid.nl')
        ->not->toContain('footer_content');
});

test('the footer credits ARVID.NL without a personal name', function () {
    $footer = (string) file_get_contents(docsPath('_includes/footer_custom.html'));

    expect($footer)->toContain('site.developer.company')
        ->toContain('site.developer.url')
        ->not->toContain('Arvid de Jong')
        ->not->toMatch('/developed by|made by/i');

    expect(file_get_contents(docsPath('_includes/head_custom.html')))->not->toContain('"Person"');
});
