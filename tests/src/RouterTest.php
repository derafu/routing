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

use Closure;
use Derafu\Routing\Collection;
use Derafu\Routing\Exception\MethodNotAllowedException;
use Derafu\Routing\Exception\RouteNotFoundException;
use Derafu\Routing\Parser\StaticParser;
use Derafu\Routing\Router;
use Derafu\Routing\UrlGenerator;
use Derafu\Routing\ValueObject\Route;
use Derafu\Routing\ValueObject\RouteMatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Router::class)]
#[CoversClass(Collection::class)]
#[CoversClass(StaticParser::class)]
#[CoversClass(Route::class)]
#[CoversClass(RouteMatch::class)]
#[CoversClass(RouteNotFoundException::class)]
#[CoversClass(MethodNotAllowedException::class)]
#[CoversClass(UrlGenerator::class)]
final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router(
            parsers: [
                new StaticParser(),
            ]
        );
    }

    #[DataProvider('provideRoutes')]
    public function testAddAndMatchRoute(
        string $path,
        string|array|Closure $handler,
        string $matchUri,
        bool $shouldMatch
    ): void {
        $this->router->addRoute('test_route', $path, $handler);

        if ($shouldMatch) {
            $match = $this->router->match($matchUri);
            $this->assertSame($handler, $match->getHandler());
        } else {
            $this->expectException(RouteNotFoundException::class);
            $this->router->match($matchUri);
        }
    }

    public static function provideRoutes(): array
    {
        $handler = function () {
        };

        return [
            'empty' => [
                '/',
                'TestController@action',
                '',
                false,
            ],
            'root' => [
                '/',
                'TestController@action',
                '/',
                true,
            ],
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

    #[DataProvider('provideRoutesWithMethod')]
    public function testMatchWithMethod(
        string $path,
        string|array|Closure $handler,
        array $routeMethods,
        string $matchUri,
        string $matchMethod,
        bool $shouldMatch,
        ?string $expectedException = null
    ): void {
        $this->router->addRoute('test_route', $path, $handler, [], $routeMethods);

        if ($shouldMatch) {
            $match = $this->router->match($matchUri, $matchMethod);
            $this->assertSame($handler, $match->getHandler());
        } else {
            $this->expectException($expectedException);
            $this->router->match($matchUri, $matchMethod);
        }
    }

    public static function provideRoutesWithMethod(): array
    {
        return [
            'no-methods-get-request' => [
                '/test', 'Handler::action', [], '/test', 'GET', true,
            ],
            'no-methods-post-request' => [
                '/test', 'Handler::action', [], '/test', 'POST', true,
            ],
            'get-route-get-request' => [
                '/test', 'Handler::action', ['GET'], '/test', 'GET', true,
            ],
            'get-route-post-request' => [
                '/test', 'Handler::action', ['GET'], '/test', 'POST', false, MethodNotAllowedException::class,
            ],
            'post-route-post-request' => [
                '/test', 'Handler::action', ['POST'], '/test', 'POST', true,
            ],
            'post-route-get-request' => [
                '/test', 'Handler::action', ['POST'], '/test', 'GET', false, MethodNotAllowedException::class,
            ],
            'multi-methods-allowed' => [
                '/test', 'Handler::action', ['GET', 'POST'], '/test', 'POST', true,
            ],
            'multi-methods-not-allowed' => [
                '/test', 'Handler::action', ['GET', 'POST'], '/test', 'PUT', false, MethodNotAllowedException::class,
            ],
            'wrong-uri-with-method' => [
                '/test', 'Handler::action', ['GET'], '/wrong', 'GET', false, RouteNotFoundException::class,
            ],
            'lowercase-method-normalized' => [
                '/test', 'Handler::action', ['GET'], '/test', 'get', true,
            ],
        ];
    }

    public function testMultipleRoutesRegisteredSimultaneously(): void
    {
        $router = new Router(parsers: [new StaticParser()]);

        $router->addRoute('home', '/', 'HomeController::index');
        $router->addRoute('users_list', '/users', 'UserController::index', [], ['GET']);
        $router->addRoute('users_store', '/users', 'UserController::store', [], ['POST']);
        $router->addRoute('about', '/about', 'PageController::about');

        // Routes without method restriction accept any method.
        $this->assertSame('HomeController::index', $router->match('/', 'GET')->getHandler());
        $this->assertSame('HomeController::index', $router->match('/', 'POST')->getHandler());

        // Same path /users, different methods dispatch to different handlers.
        $getMatch = $router->match('/users', 'GET');
        $this->assertSame('users_list', $getMatch->getName());
        $this->assertSame('UserController::index', $getMatch->getHandler());

        $postMatch = $router->match('/users', 'POST');
        $this->assertSame('users_store', $postMatch->getName());
        $this->assertSame('UserController::store', $postMatch->getHandler());

        // A disallowed method on /users lists ALL allowed methods (GET and POST).
        try {
            $router->match('/users', 'DELETE');
            $this->fail('Expected MethodNotAllowedException.');
        } catch (MethodNotAllowedException $e) {
            $this->assertSame('/users', $e->getUri());
            $this->assertSame('DELETE', $e->getMethod());
            $allowedMethods = $e->getAllowedMethods();
            sort($allowedMethods);
            $this->assertSame(['GET', 'POST'], $allowedMethods);
        }

        // Other registered routes are unaffected.
        $this->assertSame('PageController::about', $router->match('/about', 'GET')->getHandler());

        // Unknown path throws RouteNotFoundException regardless of method.
        $this->expectException(RouteNotFoundException::class);
        $router->match('/unknown', 'GET');
    }
}
