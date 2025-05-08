<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsRouting\Parser;

use Derafu\Routing\Parser\StaticParser;
use Derafu\Routing\ValueObject\Route;
use Derafu\Routing\ValueObject\RouteMatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(StaticParser::class)]
#[CoversClass(Route::class)]
#[CoversClass(RouteMatch::class)]
final class StaticParserTest extends TestCase
{
    private StaticParser $parser;

    protected function setUp(): void
    {
        $this->parser = new StaticParser();
    }

    #[DataProvider('supportedRoutesProvider')]
    public function testSupportsStaticRoutes(Route $route, bool $expected): void
    {
        $this->assertSame($expected, $this->parser->supports($route));
    }

    public function testParseMatchesExactRoute(): void
    {
        $route = new Route('test_route', '/test', 'TestController@action');
        $routes = [$route];

        $match = $this->parser->parse('/test', $routes);

        $this->assertNotNull($match);
        $this->assertSame($route->getHandler(), $match->getHandler());
    }

    public function testParseReturnsNullForNonMatch(): void
    {
        $routes = [new Route('test_route', '/test', 'TestController@action')];
        $this->assertNull($this->parser->parse('/non-existent', $routes));
    }

    public static function supportedRoutesProvider(): array
    {
        return [
            'static-route' => [
                new Route('test_route', '/test', 'TestController@action'),
                true,
            ],
            'dynamic-route' => [
                new Route('user.show', '/users/{id}', 'UserController@show'),
                false,
            ],
            'wildcard-route' => [
                new Route('file.show', '/files/*', 'FileController@show'),
                false,
            ],
        ];
    }
}
