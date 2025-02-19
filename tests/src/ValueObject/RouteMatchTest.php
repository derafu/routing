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
use Derafu\Routing\ValueObject\RouteMatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RouteMatch::class)]
final class RouteMatchTest extends TestCase
{
    #[DataProvider('matchDataProvider')]
    public function testMatchGetters(
        string|array|Closure $handler,
        array $parameters,
        ?string $name,
        ?string $module
    ): void {
        $match = new RouteMatch($handler, $parameters, $name, $module);

        $this->assertSame($handler, $match->getHandler());
        $this->assertSame($parameters, $match->getParameters());
        $this->assertSame($name, $match->getName());
        $this->assertSame($module, $match->getModule());
    }

    public static function matchDataProvider(): array
    {
        $closure = function () {};

        return [
            'full-match' => [
                'UserController@show',
                ['id' => 1],
                'user.show',
                'admin',
            ],
            'closure-match' => [
                $closure,
                ['data' => 'test'],
                'api.handler',
                null,
            ],
            'minimal-match' => [
                'HomeController@index',
                [],
                null,
                null,
            ],
            'array-handler-match' => [
                ['controller' => 'BlogController', 'action' => 'list'],
                ['page' => 1],
                'blog.list',
                'blog',
            ],
        ];
    }
}
