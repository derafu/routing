<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsRouting;

use Derafu\Routing\Collection;
use Derafu\Routing\Contract\RequestContextInterface;
use Derafu\Routing\Enum\UrlReferenceType;
use Derafu\Routing\Exception\RouteNotFoundException;
use Derafu\Routing\Exception\UrlGeneratorException;
use Derafu\Routing\RoutePatternParser;
use Derafu\Routing\UrlGenerator;
use Derafu\Routing\ValueObject\RequestContext;
use Derafu\Routing\ValueObject\Route;
use Derafu\Translation\Contract\TranslatableInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversClass(RequestContext::class)]
#[CoversClass(UrlGenerator::class)]
#[CoversClass(Collection::class)]
#[CoversClass(Route::class)]
#[CoversClass(RouteNotFoundException::class)]
#[CoversClass(UrlGeneratorException::class)]
#[CoversClass(RoutePatternParser::class)]
final class UrlGeneratorTest extends TestCase
{
    private Collection $collection;

    private UrlGenerator $generator;

    protected function setUp(): void
    {
        $this->collection = new Collection();
        $this->generator = new UrlGenerator($this->collection);

        // Configurar rutas de prueba
        $this->collection->add(new Route(
            name: 'home',
            path: '/',
            handler: 'HomeController@index'
        ));

        $this->collection->add(new Route(
            name: 'user.show',
            path: '/users/{id:\d+}',
            handler: 'UserController@show'
        ));

        $this->collection->add(new Route(
            name: 'post.show',
            path: '/posts/{slug}',
            handler: 'PostController@show'
        ));

        $this->collection->add(new Route(
            name: 'search',
            path: '/search',
            handler: 'SearchController@index'
        ));

        $this->collection->add(new Route(
            name: 'blog.archive',
            path: '/blog/{year}/{month?}',
            handler: 'BlogController@archive'
        ));

        $this->collection->add(new Route(
            name: 'user.profile',
            path: '/users/{username:[a-z0-9_-]+}',
            handler: 'UserController@profile'
        ));
    }

    public function testGenerateAbsolutePath(): void
    {
        $url = $this->generator->generate('home');
        $this->assertSame('/', $url);

        $url = $this->generator->generate('user.show', ['id' => 123]);
        $this->assertSame('/users/123', $url);

        $url = $this->generator->generate('post.show', ['slug' => 'hello-world']);
        $this->assertSame('/posts/hello-world', $url);
    }

    public function testGenerateWithContext(): void
    {
        $context = $this->createStub(RequestContextInterface::class);
        $context->method('getBaseUrl')->willReturn('/base');
        $context->method('getScheme')->willReturn('https');
        $context->method('getHost')->willReturn('example.com');
        $context->method('getHttpPort')->willReturn(80);
        $context->method('getHttpsPort')->willReturn(443);

        $this->generator->setContext($context);

        // Prueba URL absoluta
        $url = $this->generator->generate('home', [], UrlReferenceType::ABSOLUTE_URL);
        $this->assertSame('https://example.com/base/', $url);

        // Prueba ruta de red
        $url = $this->generator->generate('home', [], UrlReferenceType::NETWORK_PATH);
        $this->assertSame('//example.com/base/', $url);

        // Prueba con puerto no estándar
        $context = $this->createStub(RequestContextInterface::class);
        $context->method('getBaseUrl')->willReturn('/base');
        $context->method('getScheme')->willReturn('https');
        $context->method('getHost')->willReturn('example.com');
        $context->method('getHttpPort')->willReturn(80);
        $context->method('getHttpsPort')->willReturn(8443);

        $this->generator->setContext($context);

        $url = $this->generator->generate('home', [], UrlReferenceType::ABSOLUTE_URL);
        $this->assertSame('https://example.com:8443/base/', $url);
    }

    public function testGenerateWithMissingParameters(): void
    {
        $exception = null;
        try {
            $this->generator->generate('user.show');
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(TranslatableInterface::class, $exception);
        $this->assertStringStartsWith('Parameter "', $exception->getMessage());
        $this->assertStringContainsString('" is required for route "', $exception->getMessage());
        $this->assertStringEndsWith('" but was not provided.', $exception->getMessage());
    }

    public function testGenerateWithInvalidRouteName(): void
    {
        $this->expectException(UrlGeneratorException::class);
        $this->generator->generate('non.existent.route');
    }

    public function testRelativePathsAreCalculatedFromTheCurrentDirectory(): void
    {
        $this->generator->setContext(new RequestContext(baseUrl: '/app', pathInfo: '/orders/25'));

        $this->assertSame('../', $this->generator->generate('home', [], UrlReferenceType::RELATIVE_PATH));
    }

    public function testGenerateWithOptionalParameters(): void
    {
        // Con ambos parámetros
        $url = $this->generator->generate('blog.archive', [
            'year' => '2024',
            'month' => '03',
        ]);
        $this->assertSame('/blog/2024/03', $url);

        // Solo con parámetro requerido
        $url = $this->generator->generate('blog.archive', [
            'year' => '2024',
        ]);
        $this->assertSame('/blog/2024', $url);
    }

    public function testGenerateWithComplexConstraints(): void
    {
        $url = $this->generator->generate('user.profile', [
            'username' => 'john-doe_123',
        ]);
        $this->assertSame('/users/john-doe_123', $url);
    }

    public function testGetContext(): void
    {
        $this->assertNull($this->generator->getContext());

        $context = $this->createStub(RequestContextInterface::class);
        $this->generator->setContext($context);
        $this->assertSame($context, $this->generator->getContext());
    }

    #[RunInSeparateProcess]
    public function testAPathThatTheRegularExpressionCanNotProcessIsReported(): void
    {
        // Makes the regular expressions fail when they have to backtrack, with
        // an error of PCRE (`preg_*` functions return `null`). It is in a
        // process of its own because PHP keeps the regular expressions that
        // were already compiled, and the ones that were compiled with JIT do
        // not have this limit.
        $limits = [ini_set('pcre.jit', '0'), ini_set('pcre.backtrack_limit', '1')];
        try {
            $this->generator->generate('post.show', ['slug' => 'hello']);
            $this->fail('A path that can not be processed must be reported.');
        } catch (UrlGeneratorException $exception) {
            $this->assertInstanceOf(TranslatableInterface::class, $exception);
            $this->assertStringStartsWith('Unable to generate a URL for the route "/posts/{slug}": ', $exception->getMessage());
        } finally {
            ini_set('pcre.jit', (string) $limits[0]);
            ini_set('pcre.backtrack_limit', (string) $limits[1]);
        }
    }

    public function testAParameterThatCanBeConvertedToAStringIsAccepted(): void
    {
        $slug = new class () {
            public function __toString(): string
            {
                return 'from-an-object';
            }
        };

        $this->assertSame('/posts/from-an-object', $this->generator->generate('post.show', ['slug' => $slug]));
        $this->assertSame('/posts/1.5', $this->generator->generate('post.show', ['slug' => 1.5]));
    }

    /**
     * @return array<string, array{string, array<string, mixed>, string}>
     */
    public static function parametersThatCanNotBeConvertedProvider(): array
    {
        $message = 'Parameter "%s" of route "%s" must be a string, a number, a boolean or an object that can be converted to a string; %s given.';

        return [
            'an array' => ['post.show', ['slug' => ['a']], sprintf($message, 'slug', '/posts/{slug}', 'array')],
            'an object' => ['post.show', ['slug' => new \stdClass()], sprintf($message, 'slug', '/posts/{slug}', 'stdClass')],
            'an optional array' => ['blog.archive', ['year' => 2026, 'month' => [1]], sprintf($message, 'month', '/blog/{year}/{month?}', 'array')],
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     */
    #[DataProvider('parametersThatCanNotBeConvertedProvider')]
    public function testAParameterThatCanNotBeConvertedToAStringIsReported(string $route, array $parameters, string $message): void
    {
        try {
            $this->generator->generate($route, $parameters);
            $this->fail('A parameter that can not be converted must be reported.');
        } catch (InvalidArgumentException $exception) {
            $this->assertInstanceOf(TranslatableInterface::class, $exception);
            $this->assertSame($message, $exception->getMessage());
        }
    }

    public function testTheRootRelativeToItselfUsesAnExplicitCurrentDirectory(): void
    {
        $generator = new UrlGenerator($this->collection, new RequestContext());

        $this->assertSame('./', $generator->generate('home', [], UrlReferenceType::RELATIVE_PATH));
    }

    /**
     * @return array<string, array{string, int, int, UrlReferenceType, string}>
     */
    public static function portsOfTheUrlProvider(): array
    {
        return [
            'http with its default port' => ['http', 80, 443, UrlReferenceType::ABSOLUTE_URL, 'http://example.com/search'],
            'http with another port' => ['http', 8080, 443, UrlReferenceType::ABSOLUTE_URL, 'http://example.com:8080/search'],
            'https with its default port' => ['https', 80, 443, UrlReferenceType::ABSOLUTE_URL, 'https://example.com/search'],
            'https with another port' => ['https', 80, 8443, UrlReferenceType::ABSOLUTE_URL, 'https://example.com:8443/search'],
            'http port is not the one of https' => ['https', 8080, 443, UrlReferenceType::ABSOLUTE_URL, 'https://example.com/search'],
            'https port is not the one of http' => ['http', 80, 8443, UrlReferenceType::ABSOLUTE_URL, 'http://example.com/search'],
            'network path with another port' => ['http', 8080, 443, UrlReferenceType::NETWORK_PATH, '//example.com:8080/search'],
            'network path with its default port' => ['https', 80, 443, UrlReferenceType::NETWORK_PATH, '//example.com/search'],
        ];
    }

    #[DataProvider('portsOfTheUrlProvider')]
    public function testThePortIsOnlyInTheUrlWhenItIsNotTheDefaultOfTheScheme(
        string $scheme,
        int $httpPort,
        int $httpsPort,
        UrlReferenceType $type,
        string $expected
    ): void {
        $context = new RequestContext('', 'GET', 'example.com', $scheme, $httpPort, $httpsPort);

        $this->assertSame($expected, (new UrlGenerator($this->collection, $context))->generate('search', [], $type));
    }
}
