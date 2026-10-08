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
use Derafu\Routing\Exception\InvalidPathException;
use Derafu\Routing\Exception\MethodNotAllowedException;
use Derafu\Routing\Exception\RouteNotFoundException;
use Derafu\Routing\Parser\DynamicParser;
use Derafu\Routing\Parser\StaticParser;
use Derafu\Routing\Router;
use Derafu\Routing\UrlGenerator;
use Derafu\Routing\ValueObject\Route;
use Derafu\Routing\ValueObject\RouteMatch;
use Derafu\Translation\Contract\TranslatableInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Router::class)]
#[CoversClass(Collection::class)]
#[CoversClass(StaticParser::class)]
#[CoversClass(Route::class)]
#[CoversClass(RouteMatch::class)]
#[CoversClass(RouteNotFoundException::class)]
#[CoversClass(InvalidPathException::class)]
#[CoversClass(DynamicParser::class)]
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

    /**
     * @return array<string, array{array<int|string, mixed>, string}>
     */
    public static function invalidRouteConfigurationProvider(): array
    {
        return [
            'not an array' => [[5], 'Invalid route configuration.'],
            'without name' => [[['path' => '/', 'handler' => 'home']], 'Name is required in route "0".'],
            'without path' => [[['name' => 'home', 'handler' => 'home']], 'Path is required in route "home".'],
            'without handler' => [[['name' => 'home', 'path' => '/']], 'Handler is required in route "home".'],
        ];
    }

    /**
     * @param array<int|string, mixed> $routes
     */
    #[DataProvider('invalidRouteConfigurationProvider')]
    public function testAnInvalidRouteConfigurationIsATranslatableError(array $routes, string $message): void
    {
        try {
            new Router(parsers: [new StaticParser()], routes: $routes);
            $this->fail('The configuration was accepted.');
        } catch (InvalidArgumentException $e) {
            $this->assertInstanceOf(TranslatableInterface::class, $e);
            $this->assertSame($message, $e->getMessage());
        }
    }

    public function testHasTellsWhetherARouteNameIsDefined(): void
    {
        $router = new Router(
            parsers: [new StaticParser()],
            routes: ['home' => ['path' => '/', 'handler' => 'home.html.twig']]
        );
        $router->addRoute('about', '/about', 'about.html.twig');

        $this->assertTrue($router->has('home'));
        $this->assertTrue($router->has('about'));
        $this->assertFalse($router->has('missing'));
    }

    public function testTheRolesOfARouteComeFromItsConfiguration(): void
    {
        $router = new Router(
            parsers: [new StaticParser()],
            routes: [
                'named' => ['path' => '/named', 'handler' => 'a.html.twig', 'roles' => ['admin']],
                ['name' => 'listed', 'path' => '/listed', 'handler' => 'b.html.twig', 'roles' => ['editor', 'owner']],
                'without' => ['path' => '/without', 'handler' => 'c.html.twig'],
                '/by-path' => 'd.html.twig',
            ]
        );

        $this->assertSame(['admin'], $router->match('/named')->getRoles());
        $this->assertSame(['editor', 'owner'], $router->match('/listed')->getRoles());
        $this->assertSame([], $router->match('/without')->getRoles());
        $this->assertSame([], $router->match('/by-path')->getRoles());
    }

    public function testTheRolesOfARouteAddedWithAddRoute(): void
    {
        $this->router->addRoute('panel', '/panel', 'panel.html.twig', roles: ['admin']);
        $this->router->addRoute('open', '/open', 'open.html.twig');

        $this->assertTrue($this->router->match('/panel')->hasAnyRole(['admin']));
        $this->assertFalse($this->router->match('/panel')->hasAnyRole(['guest']));
        $this->assertSame([], $this->router->match('/open')->getRoles());
    }

    public function testHasChecksTheNameAndNotThePath(): void
    {
        $this->router->addRoute('about', '/about', 'about.html.twig');

        $this->assertFalse($this->router->has('/about'));
        $this->assertFalse($this->router->has('About'));
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
            'empty is the root' => [
                '/',
                'TestController@action',
                '',
                true,
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

    /**
     * @return array<string, array{string}>
     */
    public static function provideWaysOfWritingThePathOfARoute(): array
    {
        return [
            'as it is' => ['/api/index'],
            'a double slash at the start' => ['//api/index'],
            'a double slash in the middle' => ['/api//index'],
            'many slashes' => ['///api////index//'],
            'a dot segment' => ['/api/./index'],
            'a slash at the end' => ['/api/index/'],
            'no slash at the start' => ['api/index'],
            'an escaped letter' => ['/api/%69ndex'],
            'an escaped letter of the first segment' => ['/%61pi/index'],
            'an escaped dot segment' => ['/api/%2E/index'],
        ];
    }

    #[DataProvider('provideWaysOfWritingThePathOfARoute')]
    public function testEveryWayOfWritingAPathMatchesTheRouteOfThePath(string $path): void
    {
        $this->router->addRoute('index', '/api/index', 'IndexController@action');

        $this->assertSame('index', $this->router->match($path)->getName());
    }

    #[DataProvider('provideWaysOfWritingThePathOfARoute')]
    public function testADynamicRouteSeesTheCanonicalPath(string $path): void
    {
        // The route that takes everything below /api: it must get "index", not
        // "/index" or "%69ndex", or whoever reads the parameter would have to
        // guess what the router did not decide.
        $router = new Router(parsers: [new StaticParser(), new DynamicParser()]);
        $router->addRoute('api', '/api/{resource:.+}', 'ApiController@dispatch');

        $this->assertSame(['resource' => 'index'], $router->match($path)->getParameters());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function providePathsWithoutASafeForm(): array
    {
        return [
            'a parent segment' => ['/api/../index'],
            'a parent segment at the start' => ['/../api/index'],
            'an escaped parent segment' => ['/api/%2e%2e/index'],
            'an escaped slash' => ['/api%2Findex'],
            'an escaped backslash' => ['/api%5Cindex'],
            'a backslash' => ['/api\\index'],
            'a null byte' => ["/api/index\0"],
            'an escaped null byte' => ['/api/index%00'],
            'a control character' => ["/api/\x01index"],
            'an escape that is not valid' => ['/api/%zzindex'],
            'a percent at the end' => ['/api/index%'],
        ];
    }

    #[DataProvider('providePathsWithoutASafeForm')]
    public function testAPathWithoutASafeFormIsNotMatched(string $path): void
    {
        $this->router->addRoute('index', '/api/index', 'IndexController@action');
        $router = new Router(parsers: [new StaticParser(), new DynamicParser()]);
        $router->addRoute('api', '/api/{resource:.+}', 'ApiController@dispatch');

        foreach ([$this->router, $router] as $candidate) {
            try {
                $candidate->match($path);
                $this->fail('The path was matched.');
            } catch (InvalidPathException $e) {
                $this->assertSame(400, $e->getCode());
                $this->assertSame(InvalidPathException::CODE, $e->getCode());
                $this->assertSame($path, $e->getUri());
            }
        }
    }

    public function testThePathThatIsNotValidIsNotInTheMessage(): void
    {
        // It is what a client sent: it does not belong in a response or a log.
        $exception = new InvalidPathException("/api/\x01<script>");

        $this->assertInstanceOf(TranslatableInterface::class, $exception);
        $this->assertSame('The path of the request is not valid.', $exception->getMessage());
        $this->assertStringNotContainsString('script', $exception->getMessage());
    }

    public function testWithoutAPathTheOfTheCurrentRequestIsUsedInItsCanonicalForm(): void
    {
        $backup = $_SERVER;
        $_SERVER['REQUEST_URI'] = '//api//index/';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        try {
            $this->router->addRoute('index', '/api/index', 'IndexController@action');

            $this->assertSame('index', $this->router->match()->getName());

            $_SERVER['REQUEST_URI'] = '/api/../index';
            $this->expectException(InvalidPathException::class);
            $this->router->match();
        } finally {
            $_SERVER = $backup;
        }
    }
}
