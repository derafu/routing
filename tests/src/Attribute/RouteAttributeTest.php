<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Routing library.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsRouting\Attribute;

use Derafu\Routing\Attribute\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Controller with routes declared with the attribute.
 */
final class RoutedController
{
    #[Route('/users/list')]
    #[Route('/users/{id}', name: 'user_show', methods: ['GET'], defaults: ['id' => 1], handler: 'Other::handler', roles: ['admin'])]
    public function action(): void
    {
    }
}

#[CoversClass(Route::class)]
final class RouteAttributeTest extends TestCase
{
    /**
     * @return list<Route>
     */
    private function routes(): array
    {
        $attributes = (new ReflectionMethod(RoutedController::class, 'action'))->getAttributes(Route::class);

        return array_map(fn ($attribute) => $attribute->newInstance(), $attributes);
    }

    #[Test]
    public function theAttributeCanBeRepeatedInAMethod(): void
    {
        $this->assertCount(2, $this->routes());
    }

    #[Test]
    public function aRouteKeepsWhatItWasDeclaredWith(): void
    {
        $route = $this->routes()[1];

        $this->assertSame('/users/{id}', $route->path);
        $this->assertSame('user_show', $route->name);
        $this->assertSame(['GET'], $route->methods);
        $this->assertSame(['id' => 1], $route->defaults);
        $this->assertSame('Other::handler', $route->handler);
        $this->assertSame(['admin'], $route->roles);
    }

    #[Test]
    public function aRouteWithoutAnythingElseHasTheDefaults(): void
    {
        $route = new Route('/about');

        $this->assertSame([], $route->methods);
        $this->assertSame([], $route->defaults);
        $this->assertNull($route->handler);
        $this->assertSame([], $route->roles);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function namesProvider(): array
    {
        return [
            'a path' => ['/users/list', 'users_list'],
            'without the leading slash' => ['users/list', 'users_list'],
            'with a trailing slash' => ['/users/list/', 'users_list'],
            'a single segment' => ['/about', 'about'],
            'the root' => ['/', ''],
        ];
    }

    #[Test]
    #[DataProvider('namesProvider')]
    public function theNameIsMadeFromThePathWhenThereIsNone(string $path, string $name): void
    {
        $this->assertSame($name, (new Route($path))->name);
    }

    #[Test]
    public function theNameThatIsGivenIsKept(): void
    {
        $this->assertSame('given', (new Route('/users/list', 'given'))->name);
    }

    #[Test]
    public function theNameIsAStringThatCanNotBeChangedAfterwards(): void
    {
        $property = new ReflectionProperty(Route::class, 'name');

        // Not nullable: the name that is not given is made from the path.
        $this->assertSame('string', (string) $property->getType());
        $this->assertTrue($property->isReadOnly());
    }
}
