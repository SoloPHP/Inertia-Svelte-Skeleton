<?php

declare(strict_types=1);

namespace Tests;

use App\Database\Migrator;
use App\Http\Vite;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Solo\Application\Application;
use Solo\Inertia\Header;
use Solo\Inertia\Inertia;

/**
 * Feature tests send requests through the whole application with
 * Application::handle(), as a browser and the Inertia client would: the
 * cookies a response sets go with the next request, the page the browser is
 * on is the Referer, and an Inertia visit sends the asset version and the
 * XSRF token the client sends.
 */
abstract class TestCase extends BaseTestCase
{
    protected Application $app;

    /** @var array<string, string> The cookies the browser keeps */
    private array $cookies = [];

    /** The URL of the page the browser shows */
    private ?string $location = null;

    protected function setUp(): void
    {
        // A new application per test, so each one gets a new in-memory database
        $app = require __DIR__ . '/../bootstrap/app.php';
        self::assertInstanceOf(Application::class, $app);
        $this->app = $app;

        // A build of its own: the tests neither need `npm run build` nor change with it
        $this->app->container->set(Vite::class, fn() => new Vite(__DIR__ . '/Fixtures/public'));

        $this->app->container->get(Migrator::class)->run();
    }

    /** A first visit, as the browser makes it: the answer is the HTML document */
    protected function visit(string $uri): ResponseInterface
    {
        return $this->send(new ServerRequest('GET', self::url($uri)));
    }

    /**
     * A visit the Inertia client makes. $data is sent as JSON, as the client sends forms.
     *
     * @param array<string, mixed>|null $data
     * @param array<string, string> $headers
     */
    protected function inertia(
        string $method,
        string $uri,
        ?array $data = null,
        array $headers = [],
    ): ResponseInterface {
        $request = new ServerRequest($method, self::url($uri), $headers + [
            Header::INERTIA => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
            Header::VERSION => $this->app->container->get(Inertia::class)->version,
        ]);

        // The client echoes the XSRF-TOKEN cookie in this header
        if (isset($this->cookies['XSRF-TOKEN']) && !$request->hasHeader('X-XSRF-TOKEN')) {
            $request = $request->withHeader('X-XSRF-TOKEN', $this->cookies['XSRF-TOKEN']);
        }
        if ($data !== null) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody(Stream::create(json_encode($data, JSON_THROW_ON_ERROR)));
        }

        return $this->send($request);
    }

    /**
     * A request that validates a form while the user fills it in, as the client sends it
     *
     * @param array<string, mixed> $data
     * @param list<string> $only The fields to validate
     */
    protected function precognition(string $method, string $uri, array $data, array $only = []): ResponseInterface
    {
        $request = (new ServerRequest($method, self::url($uri), array_filter([
            Header::PRECOGNITION => 'true',
            Header::PRECOGNITION_VALIDATE_ONLY => implode(',', $only),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-XSRF-TOKEN' => $this->cookies['XSRF-TOKEN'] ?? '',
        ])))->withBody(Stream::create(json_encode($data, JSON_THROW_ON_ERROR)));

        return $this->send($request);
    }

    /**
     * Follows a redirect as the XHR does: a GET to its Location, with the same headers
     *
     * @param array<string, string> $headers
     */
    protected function follow(ResponseInterface $redirect, array $headers = []): ResponseInterface
    {
        self::assertContains($redirect->getStatusCode(), [302, 303], 'Not a redirect');

        return $this->inertia('GET', $redirect->getHeaderLine('Location'), headers: $headers);
    }

    /**
     * The page object of a JSON response
     *
     * @return array<string, mixed>
     */
    protected function page(ResponseInterface $response): array
    {
        self::assertSame('true', $response->getHeaderLine(Header::INERTIA), (string) $response->getBody());

        return self::decode((string) $response->getBody());
    }

    /**
     * The page object of an HTML document, from its script element
     *
     * @return array<string, mixed>
     */
    protected function htmlPage(ResponseInterface $response): array
    {
        $html = (string) $response->getBody();
        self::assertSame(1, preg_match('#<script data-page="app" type="application/json">(.*?)</script>#s', $html, $m));

        return self::decode($m[1] ?? '');
    }

    /**
     * The props of a page object
     *
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    protected static function props(array $page): array
    {
        $props = $page['props'] ?? null;
        self::assertIsArray($props);
        /** @var array<string, mixed> $props */
        return $props;
    }

    private function send(ServerRequestInterface $request): ResponseInterface
    {
        // A PSR-7 request does not parse its own query string: the server request creator does
        parse_str($request->getUri()->getQuery(), $query);
        $request = $request->withQueryParams($query)->withCookieParams($this->cookies);
        if ($this->location !== null) {
            $request = $request->withHeader('Referer', $this->location);
        }

        $response = $this->app->handle($request);

        foreach ($response->getHeader('Set-Cookie') as $cookie) {
            [$name, $value] = explode('=', explode(';', $cookie, 2)[0], 2) + ['', ''];
            if (str_contains($cookie, 'Max-Age=0')) {
                unset($this->cookies[$name]);
            } else {
                $this->cookies[$name] = $value;
            }
        }
        if ($request->getMethod() === 'GET' && $response->getStatusCode() === 200) {
            $this->location = (string) $request->getUri();
        }

        return $response;
    }

    private static function url(string $uri): string
    {
        return str_starts_with($uri, 'http') ? $uri : 'http://localhost' . $uri;
    }

    /** @return array<string, mixed> */
    protected static function decode(string $json): array
    {
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        /** @var array<string, mixed> $data */
        return $data;
    }
}
