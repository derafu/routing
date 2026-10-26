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
use Derafu\Routing\Enum\UrlReferenceType;
use Derafu\Routing\Exception\UrlGeneratorException;
use Derafu\Routing\RoutePatternParser;
use Derafu\Routing\UrlGenerator;
use Derafu\Routing\ValueObject\RequestContext;
use Derafu\Routing\ValueObject\Route;
use Derafu\Translation\Contract\TranslatableInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(UrlGenerator::class)]
#[CoversClass(UrlGeneratorException::class)]
#[CoversClass(RoutePatternParser::class)]
#[CoversClass(Collection::class)]
#[CoversClass(Route::class)]
#[CoversClass(RequestContext::class)]
final class UrlReferenceTest extends TestCase
{
    private function generator(?RequestContext $context, string $path = '/products/{id}'): UrlGenerator
    {
        $collection = new Collection();
        $collection->add(new Route('example', $path, 'ProductController@show'));
        return new UrlGenerator($collection, $context);
    }

    /**
     * @return array<string, array{UrlReferenceType, string}>
     */
    public static function references(): array
    {
        return [
            'absolute path' => [UrlReferenceType::ABSOLUTE_PATH, '/app/products/12'],
            'absolute URL' => [UrlReferenceType::ABSOLUTE_URL, 'https://example.test/app/products/12'],
            'network path' => [UrlReferenceType::NETWORK_PATH, '//example.test/app/products/12'],
            'relative path' => [UrlReferenceType::RELATIVE_PATH, '../products/12'],
        ];
    }

    #[DataProvider('references')]
    public function testEachReferenceIncludesTheInstallationPrefix(UrlReferenceType $type, string $expected): void
    {
        $generator = $this->generator(new RequestContext(
            baseUrl: '/app',
            host: 'example.test',
            scheme: 'https',
            pathInfo: '/orders/25'
        ));

        $this->assertSame($expected, $generator->generate('example', ['id' => 12], $type));
        $this->assertSame($expected . '?page=2', $generator->generate('example', ['id' => 12, 'page' => 2], $type));
        $this->assertSame(
            str_replace('/12', '/hello%20world', $expected) . '?q=caf%C3%A9%3F%23',
            $generator->generate('example', ['id' => 'hello world', 'q' => 'café?#'], $type),
        );
    }

    /**
     * @return array<string, array{UrlReferenceType}>
     */
    public static function referencesRequiringContext(): array
    {
        return [
            'absolute URL' => [UrlReferenceType::ABSOLUTE_URL],
            'network path' => [UrlReferenceType::NETWORK_PATH],
            'relative path' => [UrlReferenceType::RELATIVE_PATH],
        ];
    }

    #[DataProvider('referencesRequiringContext')]
    public function testAReferenceRequiringContextCannotSilentlyBecomeAnAbsolutePath(UrlReferenceType $type): void
    {
        try {
            $this->generator(null)->generate('example', ['id' => 12], $type);
            $this->fail('This reference cannot be generated without context.');
        } catch (UrlGeneratorException $exception) {
            $this->assertInstanceOf(TranslatableInterface::class, $exception);
            $this->assertStringContainsString($type->name, $exception->getMessage());
        }
    }

    public function testAnAbsolutePathCanBeGeneratedWithoutContext(): void
    {
        $this->assertSame('/products/12', $this->generator(null)->generate('example', ['id' => 12]));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function relativePaths(): array
    {
        return [
            'sibling' => ['/products/5', '/products/12', '12'],
            'same URL' => ['/products/12', '/products/12', '12'],
            'another directory' => ['/orders/25', '/products/12', '../products/12'],
            'from root' => ['/', '/products/12', 'products/12'],
            'to root' => ['/orders/25', '/', '../'],
            'root to root' => ['/', '/', './'],
            'from a directory' => ['/orders/', '/products/12', '../products/12'],
            'to a directory' => ['/orders/25', '/products/', '../products/'],
            'to the current directory' => ['/products/12', '/products/', './'],
            'directory without its final slash' => ['/products/', '/products', '../products'],
            'directory with its final slash' => ['/products', '/products/', 'products/'],
            'deep directory' => ['/a/b/c/item', '/d/target', '../../../d/target'],
            'scheme-like file name' => ['/docs/start', '/docs/http:guide', './http:guide'],
        ];
    }

    #[DataProvider('relativePaths')]
    public function testRelativePathsUseTheCurrentRequestDirectory(string $current, string $target, string $expected): void
    {
        foreach (['', '/app', '/nested/app'] as $baseUrl) {
            $generator = $this->generator(new RequestContext(baseUrl: $baseUrl, pathInfo: $current), $target);
            $this->assertSame($expected, $generator->generate('example', [], UrlReferenceType::RELATIVE_PATH));
        }
    }

    public function testQueryParametersDoNotIncludeInternalDefaultsOrConsumedOptionalParameters(): void
    {
        $routes = new Collection();
        $routes->add(new Route('example', '/products/{id}/{page?}', 'ProductController@show', [
            'id' => 12, 'internal' => 'not-a-query',
        ]));
        $generator = new UrlGenerator($routes);

        $this->assertSame('/products/12', $generator->generate('example'));
        $this->assertSame('/products/12?search=hello%20world&enabled=1&tags%5B0%5D=a&tags%5B1%5D=b', $generator->generate('example', [
            'page' => null, 'search' => 'hello world', 'enabled' => true, 'tags' => ['a', 'b'],
        ]));
        $this->assertSame('/products/12?internal=explicit', $generator->generate('example', ['internal' => 'explicit']));
    }

    public function testTheQueryOfTheCurrentRequestIsNotInherited(): void
    {
        $generator = $this->generator(new RequestContext(pathInfo: '/products/12', queryString: 'old=1'));
        $this->assertSame('12?new=2', $generator->generate('example', ['id' => 12, 'new' => 2], UrlReferenceType::RELATIVE_PATH));
    }
}
