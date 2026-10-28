<?php

declare(strict_types=1);

namespace Tests\Feature;

use Solo\Inertia\Header;
use Tests\TestCase;

final class HomeTest extends TestCase
{
    public function testFirstVisitIsTheHtmlDocument(): void
    {
        $response = $this->visit('/');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertSame('X-Inertia', $response->getHeaderLine('Vary'));

        $html = (string) $response->getBody();
        self::assertStringContainsString('<link rel="stylesheet" href="/build/assets/app-test.css">', $html);
        self::assertStringContainsString('<link rel="stylesheet" href="/build/assets/shared-test.css">', $html);
        self::assertStringContainsString('<link rel="modulepreload" href="/build/assets/shared-test.js">', $html);
        self::assertStringContainsString('<script type="module" src="/build/assets/app-test.js"></script>', $html);
        self::assertStringContainsString('<div id="app"></div>', $html);

        $page = $this->htmlPage($response);
        self::assertSame('Home', $page['component']);
        $props = self::props($page);
        self::assertSame(['name' => 'Solo Inertia'], $props['app']);
        self::assertSame('/tasks', $props['tasksUrl']);
        self::assertSame([
            'home' => '/',
            'links' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Tasks', 'url' => '/tasks'],
            ],
        ], $props['nav']);
    }

    public function testFirstVisitHandsOutTheXsrfToken(): void
    {
        $cookie = $this->visit('/')->getHeaderLine('Set-Cookie');

        self::assertMatchesRegularExpression('/^XSRF-TOKEN=[0-9a-f]{64}; Path=\/; SameSite=Lax$/', $cookie);
    }

    public function testInertiaVisitIsThePageObject(): void
    {
        $page = $this->page($this->inertia('GET', '/'));

        self::assertSame('Home', $page['component']);
        self::assertSame('/', $page['url']);
        self::assertSame(['errors', 'app', 'nav'], $page['sharedProps']);
        self::assertSame(['nav' => ['prop' => 'nav', 'expiresAt' => null]], $page['onceProps']);
    }

    public function testClientThatHasTheNavigationIsNotSentItAgain(): void
    {
        $page = $this->page($this->inertia('GET', '/', headers: [Header::EXCEPT_ONCE_PROPS => 'nav']));

        self::assertArrayNotHasKey('nav', self::props($page));
        self::assertSame(['nav' => ['prop' => 'nav', 'expiresAt' => null]], $page['onceProps']);
    }

    public function testClientBuiltFromOtherAssetsReloads(): void
    {
        $response = $this->inertia('GET', '/tasks', headers: [Header::VERSION => 'an-old-build']);

        self::assertSame(409, $response->getStatusCode());
        self::assertSame('http://localhost/tasks', $response->getHeaderLine(Header::LOCATION));
        self::assertFalse($response->hasHeader(Header::INERTIA));
    }

    public function testUnknownPageIsTheErrorPage(): void
    {
        $response = $this->inertia('GET', '/nowhere');

        self::assertSame(404, $response->getStatusCode());
        $page = $this->page($response);
        self::assertSame('Error', $page['component']);
        self::assertSame(404, self::props($page)['status']);
        // Shared props reach the error page too
        self::assertSame(['name' => 'Solo Inertia'], self::props($page)['app']);
    }

    public function testWrongMethodIsA405ErrorPageWithAllow(): void
    {
        $this->visit('/');
        $response = $this->inertia('PUT', '/tasks');

        self::assertSame(405, $response->getStatusCode());
        self::assertSame(405, self::props($this->page($response))['status']);
        self::assertStringContainsString('POST', $response->getHeaderLine('Allow'));
    }
}
