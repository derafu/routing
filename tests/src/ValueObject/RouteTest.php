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

    public static function routeDataProvider(): array
    {
        $closure = function () {};

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
