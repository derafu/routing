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
use PHPUnit\Framework\TestCase;

/**
 * @covers \Derafu\Routing\ValueObject\Route
 */
final class RouteTest extends TestCase
{
    /**
     * @dataProvider routeDataProvider
     */
    public function testRouteGetters(
        string $pattern,
        string|array|Closure $handler,
        ?string $name,
        array $parameters
    ): void {
        $route = new Route($pattern, $handler, $name, $parameters);

        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($handler, $route->getHandler());
        $this->assertSame($name, $route->getName());
        $this->assertSame($parameters, $route->getParameters());
    }

    public static function routeDataProvider(): array
    {
        $closure = function () {};

        return [
            'string-handler' => [
                '/test',
                'TestController@action',
                'test.route',
                ['param' => 'value'],
            ],
            'array-handler' => [
                '/users/{id}',
                ['controller' => 'UserController', 'action' => 'show'],
                'user.show',
                ['id' => 1],
            ],
            'closure-handler' => [
                '/api/data',
                $closure,
                'api.data',
                [],
            ],
            'no-name-no-params' => [
                '/simple',
                'SimpleController@index',
                null,
                [],
            ],
        ];
    }
}
