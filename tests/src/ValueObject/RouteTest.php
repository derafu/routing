<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsRouting\ValueObject;

use Closure;
use Derafu\Routing\ValueObject\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Route::class)]
final class RouteTest extends TestCase
{
    #[DataProvider('routeDataProvider')]
    public function testRouteGetters(
        string $name,
        string $path,
        string|array|Closure $handler,
        array $defaults
    ): void {
        $route = new Route($name, $path, $handler, $defaults);

        $this->assertSame($name, $route->getName());
        $this->assertSame($path, $route->getPath());
        $this->assertSame($handler, $route->getHandler());
        $this->assertSame($defaults, $route->getDefaults());
    }

    public function testARouteWithoutRolesHasNone(): void
    {
        $route = new Route('home', '/', 'HomeController@index');

        $this->assertSame([], $route->getRoles());
        $this->assertFalse($route->hasRole('admin'));
        $this->assertFalse($route->hasAnyRole(['admin', 'editor']));
    }

    public function testARouteTellsWhichRolesItDeclares(): void
    {
        $route = new Route('admin', '/admin', 'AdminController@index', [], [], ['admin', 'editor']);

        $this->assertSame(['admin', 'editor'], $route->getRoles());
        $this->assertTrue($route->hasRole('editor'));
        $this->assertFalse($route->hasRole('guest'));
    }

    public function testHasAnyRoleIsTrueWhenOneOfTheRolesIsDeclared(): void
    {
        $route = new Route('admin', '/admin', 'AdminController@index', [], [], ['admin', 'editor']);

        $this->assertTrue($route->hasAnyRole(['guest', 'editor']));
        $this->assertFalse($route->hasAnyRole(['guest', 'owner']));
    }

    public function testHasAnyRoleOfNoRolesIsFalse(): void
    {
        $route = new Route('admin', '/admin', 'AdminController@index', [], [], ['admin']);

        $this->assertFalse($route->hasAnyRole([]));
    }

    public static function routeDataProvider(): array
    {
        $closure = function () {
        };

        return [
            'string-handler' => [
                'test.route',
                '/test',
                'TestController@action',
                [], // Without defaults parameters.
            ],
            'array-handler' => [
                'user.show',
                '/users/{id}',
                ['controller' => 'UserController', 'action' => 'show'],
                ['id' => 1], // With defaults parameters.
            ],
            'closure-handler' => [
                'api.data',
                '/api/data',
                $closure,
                [], // Without defaults parameters.
            ],
        ];
    }
}
