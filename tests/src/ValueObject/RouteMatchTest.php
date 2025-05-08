<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsRouting\ValueObject;

use Closure;
use Derafu\Routing\ValueObject\Route;
use Derafu\Routing\ValueObject\RouteMatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RouteMatch::class)]
#[CoversClass(Route::class)]
final class RouteMatchTest extends TestCase
{
    #[DataProvider('matchDataProvider')]
    public function testMatchGetters(
        string $name,
        string $path,
        string|array|Closure $handler,
        array $defaults,
        ?string $module
    ): void {
        $route = new Route($name, $path, $handler, $defaults);
        $match = new RouteMatch($route, $route->getDefaults(), $module);

        $this->assertSame($handler, $match->getHandler());
        $this->assertSame($defaults, $match->getParameters());
        $this->assertSame($name, $match->getName());
        $this->assertSame($module, $match->getModule());
    }

    public static function matchDataProvider(): array
    {
        $closure = function () {};

        return [
            'full-match' => [
                'user.show',
                '/users/{id}',
                'UserController@show',
                ['id' => 1],
                'admin',
            ],
            'closure-match' => [
                'api.handler',
                '/api/data',
                $closure,
                ['data' => 'test'],
                null,
            ],
            'minimal-match' => [
                'homepage',
                '/',
                'HomeController@index',
                [],
                null,
            ],
            'array-handler-match' => [
                'blog.list',
                '/blog',
                ['controller' => 'BlogController', 'action' => 'index'],
                [],
                'blog',
            ],
        ];
    }
}
