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
use Derafu\Routing\ValueObject\Route;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Derafu\Routing\Collection
 */
final class CollectionTest extends TestCase
{
    private Collection $collection;

    protected function setUp(): void
    {
        $this->collection = new Collection();
    }

    /**
     * @dataProvider routeProvider
     */
    public function testAddAndGetRoute(Route $route): void
    {
        $this->collection->add($route);

        $this->assertSame($route, $this->collection->get($route->getPattern()));
        $this->assertTrue($this->collection->has($route->getPattern()));
    }

    public function testGetNonExistentRoute(): void
    {
        $this->assertNull($this->collection->get('/non-existent'));
        $this->assertFalse($this->collection->has('/non-existent'));
    }

    public function testAllReturnsAllRoutes(): void
    {
        $routes = [];
        foreach ($this->routeProvider() as $data) {
            $route = $data[0];
            $routes[] = $route;
            $this->collection->add($route);
        }

        $this->assertSame($routes, $this->collection->all());
    }

    public static function routeProvider(): array
    {
        return [
            'simple-route' => [
                new Route('/test', 'TestController@action'),
            ],
            'named-route' => [
                new Route('/users', 'UserController@index', 'users.index'),
            ],
            'parameterized-route' => [
                new Route('/users/{id}', 'UserController@show', 'users.show', ['id' => 1]),
            ],
            'closure-route' => [
                new Route('/api/data', fn () => []),
            ],
        ];
    }
}
