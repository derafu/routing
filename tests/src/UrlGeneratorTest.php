<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsRouting;

use Derafu\Routing\Collection;
use Derafu\Routing\Contract\RequestContextInterface;
use Derafu\Routing\Enum\UrlReferenceType;
use Derafu\Routing\Exception\RouteNotFoundException;
use Derafu\Routing\Exception\UrlGeneratorException;
use Derafu\Routing\UrlGenerator;
use Derafu\Routing\ValueObject\Route;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UrlGenerator::class)]
#[CoversClass(Collection::class)]
#[CoversClass(Route::class)]
#[CoversClass(RouteNotFoundException::class)]
#[CoversClass(UrlGeneratorException::class)]
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
        $context = $this->createMock(RequestContextInterface::class);
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
        $context = $this->createMock(RequestContextInterface::class);
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
        $this->expectException(InvalidArgumentException::class);
        $this->generator->generate('user.show');
    }

    public function testGenerateWithInvalidRouteName(): void
    {
        $this->expectException(UrlGeneratorException::class);
        $this->generator->generate('non.existent.route');
    }

    public function testRelativePathNotImplemented(): void
    {
        $context = $this->createMock(RequestContextInterface::class);
        $this->generator->setContext($context);

        $this->expectException(UrlGeneratorException::class);
        $this->expectExceptionMessage('Relative path generation is not implemented yet.');

        $this->generator->generate('home', [], UrlReferenceType::RELATIVE_PATH);
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

        $context = $this->createMock(RequestContextInterface::class);
        $this->generator->setContext($context);
        $this->assertSame($context, $this->generator->getContext());
    }
}
