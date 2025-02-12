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

use Closure;
use Derafu\Routing\Exception\RouteNotFoundException;
use Derafu\Routing\Parser\StaticParser;
use Derafu\Routing\Router;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Derafu\Routing\Router
 */
final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->addParser(new StaticParser());
    }

    /**
     * @dataProvider routeProvider
     */
    public function testAddAndMatchRoute(
        string $pattern,
        string|array|Closure $handler,
        string $matchUri,
        bool $shouldMatch
    ): void {
        $this->router->addRoute($pattern, $handler);

        if ($shouldMatch) {
            $match = $this->router->match($matchUri);
            $this->assertSame($handler, $match->getHandler());
        } else {
            $this->expectException(RouteNotFoundException::class);
            $this->router->match($matchUri);
        }
    }

    public static function routeProvider(): array
    {
        $handler = function () {};

        return [
            'exact-match' => [
                '/test',
                'TestController@action',
                '/test',
                true,
            ],
            'no-match' => [
                '/test',
                'TestController@action',
                '/wrong',
                false,
            ],
            'closure-handler' => [
                '/api/data',
                $handler,
                '/api/data',
                true,
            ],
            'array-handler' => [
                '/users',
                ['controller' => 'UserController', 'action' => 'index'],
                '/users',
                true,
            ],
        ];
    }
}
