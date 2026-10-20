<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Routing library.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsRouting\ValueObject;

use Derafu\Routing\ValueObject\RequestContext;
use Derafu\Translation\Contract\TranslatableInterface;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequestContext::class)]
final class RequestContextTest extends TestCase
{
    /**
     * @param array<string, mixed> $server
     * @param array<string, mixed> $attributes
     */
    private function request(string $uri, array $server = [], array $attributes = [], string $method = 'GET'): ServerRequest
    {
        $request = new ServerRequest($method, $uri, [], null, '1.1', $server);
        foreach ($attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        return $request;
    }

    #[Test]
    public function theContextComesFromTheUriOfTheRequest(): void
    {
        $context = RequestContext::fromRequest($this->request('https://example.com:8443/a/b?x=1&y=2', method: 'POST'));

        $this->assertSame('https', $context->getScheme());
        $this->assertSame('example.com', $context->getHost());
        $this->assertSame(8443, $context->getHttpsPort());
        $this->assertSame(80, $context->getHttpPort());
        $this->assertSame('/a/b', $context->getPathInfo());
        $this->assertSame('x=1&y=2', $context->getQueryString());
        $this->assertSame('POST', $context->getMethod());
        $this->assertSame('', $context->getBaseUrl());
    }

    /**
     * @return array<string, array{string, int, int}>
     */
    public static function portsProvider(): array
    {
        return [
            'http without a port' => ['http://example.com/a', 80, 443],
            'https without a port' => ['https://example.com/a', 80, 443],
            'http with a port' => ['http://example.com:8080/a', 8080, 443],
            'https with a port' => ['https://example.com:8443/a', 80, 8443],
        ];
    }

    #[Test]
    #[DataProvider('portsProvider')]
    public function thePortsAreTheOnesOfTheUriOrTheDefaultOnesOfTheScheme(string $uri, int $http, int $https): void
    {
        $context = RequestContext::fromRequest($this->request($uri));

        $this->assertSame($http, $context->getHttpPort());
        $this->assertSame($https, $context->getHttpsPort());
    }

    #[Test]
    public function thePortOfTheServerIsNotUsed(): void
    {
        // Behind a proxy the server listens in a port that is not the one of
        // the URL of the request.
        $context = RequestContext::fromRequest($this->request('https://example.com/a', ['SERVER_PORT' => '8080']));

        $this->assertSame(443, $context->getHttpsPort());
        $this->assertSame(80, $context->getHttpPort());
    }

    #[Test]
    public function theHostIsTheOneOfTheServerWhenTheUriHasNone(): void
    {
        $context = RequestContext::fromRequest($this->request('/a', ['SERVER_NAME' => 'server.test']));

        $this->assertSame('server.test', $context->getHost());
        $this->assertSame('http', $context->getScheme());
    }

    #[Test]
    public function theHostIsLocalhostWhenThereIsNoneAtAll(): void
    {
        $this->assertSame('localhost', RequestContext::fromRequest($this->request('/a'))->getHost());
        $this->assertSame('localhost', RequestContext::fromRequest($this->request('/a', ['SERVER_NAME' => 5]))->getHost());
        $this->assertSame('localhost', RequestContext::fromRequest($this->request('/a', ['SERVER_NAME' => '']))->getHost());
    }

    #[Test]
    public function theDataOfTheContextWinsOverTheRequest(): void
    {
        $context = RequestContext::fromRequest(
            $this->request('http://example.com:8080/original', ['SERVER_NAME' => 'server.test']),
            [
                'URL_SCHEME' => 'https',
                'URL_HOST' => 'context.test',
                'URL_PORT' => 8443,
                'APP_BASE_PATH' => '/app',
                'URL_URI' => '/given',
            ]
        );

        $this->assertSame('https', $context->getScheme());
        $this->assertSame('context.test', $context->getHost());
        $this->assertSame(8443, $context->getHttpsPort());
        $this->assertSame(80, $context->getHttpPort());
        $this->assertSame('/app', $context->getBaseUrl());
        $this->assertSame('/given', $context->getPathInfo());
    }

    #[Test]
    public function thePortOfTheContextIsTheOneOfItsScheme(): void
    {
        $http = RequestContext::fromRequest($this->request('/a'), ['URL_PORT' => '8080']);
        $https = RequestContext::fromRequest($this->request('/a'), ['URL_SCHEME' => 'https', 'URL_PORT' => 8443]);

        $this->assertSame(8080, $http->getHttpPort());
        $this->assertSame(443, $http->getHttpsPort());
        $this->assertSame(8443, $https->getHttpsPort());
        $this->assertSame(80, $https->getHttpPort());
    }

    #[Test]
    public function aSchemeThatIsNeitherHttpNorHttpsKeepsTheDefaultPorts(): void
    {
        $context = RequestContext::fromRequest($this->request('/a'), ['URL_SCHEME' => 'ftp', 'URL_PORT' => 2121]);

        $this->assertSame('ftp', $context->getScheme());
        $this->assertSame(80, $context->getHttpPort());
        $this->assertSame(443, $context->getHttpsPort());
    }

    #[Test]
    public function theContextIsTheAttributeOfTheRequestThatIsNamed(): void
    {
        $request = $this->request('/a', attributes: [
            'derafu.context' => ['URL_HOST' => 'default.test'],
            'other' => ['URL_HOST' => 'other.test'],
        ]);

        $this->assertSame('default.test', RequestContext::fromRequest($request)->getHost());
        $this->assertSame('other.test', RequestContext::fromRequest($request, 'other')->getHost());
        $this->assertSame('localhost', RequestContext::fromRequest($request, 'missing')->getHost());
    }

    #[Test]
    public function theValuesThatAreEmptyAreNotUsed(): void
    {
        $context = RequestContext::fromRequest(
            $this->request('https://example.com:8443/a'),
            ['URL_SCHEME' => '', 'URL_HOST' => null, 'URL_PORT' => '', 'APP_BASE_PATH' => '', 'URL_URI' => '']
        );

        $this->assertSame('https', $context->getScheme());
        $this->assertSame('example.com', $context->getHost());
        $this->assertSame(8443, $context->getHttpsPort());
        $this->assertSame('/a', $context->getPathInfo());
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function basePathsProvider(): array
    {
        return [
            'inside the base' => ['/app', '/app/users', '/users', 'It is removed.'],
            'the base itself' => ['/app', '/app', '/', 'The root is left.'],
            'outside the base' => ['/app', '/other/users', '/other/users', 'It is kept.'],
            'without base' => ['', '/users', '/users', 'It is kept.'],
            'without a leading slash' => ['/app', '/appusers', '/users', 'The base is a prefix of the text.'],
        ];
    }

    #[Test]
    #[DataProvider('basePathsProvider')]
    public function theBasePathIsRemovedFromThePathOfTheRequest(string $base, string $path, string $expected, string $reason): void
    {
        $context = RequestContext::fromRequest($this->request('https://example.com' . $path), ['APP_BASE_PATH' => $base]);

        $this->assertSame($expected, $context->getPathInfo(), $reason);
    }

    /**
     * @return array<string, array{array<string, mixed>|string, string}>
     */
    public static function invalidContextsProvider(): array
    {
        return [
            'the attribute is not an array' => [
                'derafu.context',
                'The context attribute derafu.context must be an array; string given.',
            ],
            'a scheme that is not a string' => [
                ['URL_SCHEME' => 5],
                'The key URL_SCHEME of the request context must be a string; int given.',
            ],
            'a host that is not a string' => [
                ['URL_HOST' => ['x']],
                'The key URL_HOST of the request context must be a string; array given.',
            ],
            'a base path that is not a string' => [
                ['APP_BASE_PATH' => true],
                'The key APP_BASE_PATH of the request context must be a string; bool given.',
            ],
            'a uri that is not a string' => [
                ['URL_URI' => 5],
                'The key URL_URI of the request context must be a string; int given.',
            ],
            'a port that is not a number' => [
                ['URL_PORT' => 'abc'],
                'The key URL_PORT of the request context must be a port number from 1 to 65535; string given.',
            ],
            'a port that is zero' => [
                ['URL_PORT' => 0],
                'The key URL_PORT of the request context must be a port number from 1 to 65535; int given.',
            ],
            'a port that is too big' => [
                ['URL_PORT' => 70000],
                'The key URL_PORT of the request context must be a port number from 1 to 65535; int given.',
            ],
            'a port that is negative' => [
                ['URL_PORT' => '-1'],
                'The key URL_PORT of the request context must be a port number from 1 to 65535; string given.',
            ],
            'a port that is not an int' => [
                ['URL_PORT' => 80.5],
                'The key URL_PORT of the request context must be a port number from 1 to 65535; float given.',
            ],
        ];
    }

    /**
     * @param array<string, mixed>|string $context
     */
    #[Test]
    #[DataProvider('invalidContextsProvider')]
    public function aContextThatIsNotValidIsReported(array|string $context, string $message): void
    {
        $request = $this->request('/a', attributes: ['derafu.context' => 'not an array']);

        try {
            RequestContext::fromRequest($request, $context);
            $this->fail('A context that is not valid must be reported.');
        } catch (InvalidArgumentException $exception) {
            $this->assertInstanceOf(TranslatableInterface::class, $exception);
            $this->assertSame($message, $exception->getMessage());
        }
    }

    #[Test]
    public function theContextHasDefaults(): void
    {
        $context = new RequestContext();

        $this->assertSame('', $context->getBaseUrl());
        $this->assertSame('GET', $context->getMethod());
        $this->assertSame('localhost', $context->getHost());
        $this->assertSame('http', $context->getScheme());
        $this->assertSame(80, $context->getHttpPort());
        $this->assertSame(443, $context->getHttpsPort());
        $this->assertSame('/', $context->getPathInfo());
        $this->assertSame('', $context->getQueryString());
    }

    #[Test]
    public function theSettersChangeTheContextAndReturnIt(): void
    {
        $context = new RequestContext();

        $result = $context
            ->setBaseUrl('/app')
            ->setMethod('POST')
            ->setHost('example.com')
            ->setScheme('https')
            ->setHttpPort(8080)
            ->setHttpsPort(8443)
            ->setPathInfo('/users')
            ->setQueryString('page=2')
        ;

        $this->assertSame($context, $result);
        $this->assertSame('/app', $context->getBaseUrl());
        $this->assertSame('POST', $context->getMethod());
        $this->assertSame('example.com', $context->getHost());
        $this->assertSame('https', $context->getScheme());
        $this->assertSame(8080, $context->getHttpPort());
        $this->assertSame(8443, $context->getHttpsPort());
        $this->assertSame('/users', $context->getPathInfo());
        $this->assertSame('page=2', $context->getQueryString());
    }

    #[Test]
    public function theContextCanBeWrittenAsText(): void
    {
        $this->assertSame(
            'method=GET, baseUrl=, pathInfo=%2F, host=localhost, scheme=http, httpPort=80, httpsPort=443, queryString=',
            (string) new RequestContext()
        );
        $this->assertSame(
            'method=POST, baseUrl=%2Fapp, pathInfo=%2Fusers, host=example.com, scheme=https, httpPort=80, httpsPort=8443, queryString=page%3D2',
            (string) new RequestContext('/app', 'POST', 'example.com', 'https', 80, 8443, '/users', 'page=2')
        );
    }
}
