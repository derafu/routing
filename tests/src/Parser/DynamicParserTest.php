<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsRouting\Parser;

use Derafu\Routing\Exception\RouterException;
use Derafu\Routing\Parser\DynamicParser;
use Derafu\Routing\RoutePatternParser;
use Derafu\Routing\ValueObject\Route;
use Derafu\Routing\ValueObject\RouteMatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[CoversClass(DynamicParser::class)]
#[CoversClass(RouterException::class)]
#[CoversClass(Route::class)]
#[CoversClass(RouteMatch::class)]
#[CoversClass(RoutePatternParser::class)]
final class DynamicParserTest extends TestCase
{
    private DynamicParser $parser;

    protected function setUp(): void
    {
        $this->parser = new DynamicParser();
    }

    /**
     * @param array<string, string> $expectedParams
     */
    #[DataProvider('dynamicRoutesProvider')]
    public function testParseDynamicRoutes(
        string $path,
        string $uri,
        array $expectedParams,
        bool $shouldMatch
    ): void {
        $route = new Route('test_route', $path, 'TestController@action');
        $match = $this->parser->parse($uri, [$route]);

        if ($shouldMatch) {
            $this->assertNotNull($match);
            $this->assertSame($expectedParams, $match->getParameters());
        } else {
            $this->assertNull($match);
        }
    }

    /**
     * @return array<string, array{string, string, array<string, string>, bool}>
     */
    public static function dynamicRoutesProvider(): array
    {
        return [
            'simple-parameter' => [
                '/users/{id}',
                '/users/1',
                ['id' => '1'],
                true,
            ],
            'multiple-parameters' => [
                '/users/{id}/posts/{slug}',
                '/users/1/posts/hello-world',
                ['id' => '1', 'slug' => 'hello-world'],
                true,
            ],
            'with-regex' => [
                '/users/{id:\d+}',
                '/users/123',
                ['id' => '123'],
                true,
            ],
            'regex-no-match' => [
                '/users/{id:\d+}',
                '/users/abc',
                [],
                false,
            ],
            'optional-parameter-present' => [
                '/blog/{year?}',
                '/blog/2024',
                ['year' => '2024'],
                true,
            ],
            'optional-parameter-missing' => [
                '/blog/{year?}',
                '/blog',
                [],
                true,
            ],
            'multiple-optional-parameters' => [
                '/blog/{year?}/{month?}',
                '/blog/2024',
                ['year' => '2024'],
                true,
            ],
            'multiple-optional-all-present' => [
                '/blog/{year?}/{month?}',
                '/blog/2024/12',
                ['year' => '2024', 'month' => '12'],
                true,
            ],
            'multiple-optional-all-missing' => [
                '/blog/{year?}/{month?}',
                '/blog',
                [],
                true,
            ],
            'required-and-optional' => [
                '/users/{id}/posts/{slug?}',
                '/users/123/posts',
                ['id' => '123'],
                true,
            ],
            'complex-regex' => [
                '/users/{username:[a-z0-9_-]+}',
                '/users/john_doe-123',
                ['username' => 'john_doe-123'],
                true,
            ],
            'complex-regex-no-match' => [
                '/users/{username:[a-z0-9_-]+}',
                '/users/john@doe',
                [],
                false,
            ],
            'trailing-slash' => [
                '/users/{id}/',
                '/users/123/',
                ['id' => '123'],
                true,
            ],
            'no-leading-slash' => [
                'users/{id}',
                'users/123',
                ['id' => '123'],
                true,
            ],
            'with-extension' => [
                '/articles/{slug}.html',
                '/articles/my-post.html',
                ['slug' => 'my-post'],
                true,
            ],
        ];
    }

    #[RunInSeparateProcess]
    public function testAPathThatTheRegularExpressionCanNotProcessIsReported(): void
    {
        $path = '/users/{id}';
        $route = new Route('users', $path, 'UserController@show');

        // Makes the regular expressions fail when they have to backtrack, with
        // an error of PCRE (`preg_*` functions return `false` or `null`). It
        // is in a process of its own because PHP keeps the regular expressions
        // that were already compiled, and the ones that were compiled with JIT
        // do not have this limit.
        $limits = [ini_set('pcre.jit', '0'), ini_set('pcre.backtrack_limit', '1')];
        try {
            $this->parser->parse('/users/1', [$route]);
            $this->fail('A path that can not be processed must be reported.');
        } catch (RouterException $exception) {
            $this->assertStringStartsWith('The pattern of the route "' . $path . '" could not be processed: ', $exception->getMessage());
        } finally {
            ini_set('pcre.jit', (string) $limits[0]);
            ini_set('pcre.backtrack_limit', (string) $limits[1]);
        }
    }

    public function testTheRoutesThatAreNotDynamicAreSkipped(): void
    {
        $routes = [
            new Route('about', '/about', 'PageController@about'),
            new Route('user', '/users/{id}', 'UserController@show'),
        ];

        $match = $this->parser->parse('/users/7', $routes);

        $this->assertNotNull($match);
        $this->assertSame('user', $match->getName());
        $this->assertNull($this->parser->parse('/about', $routes));
    }

    public function testAnOptionalParameterAfterTextTakesItsOwnSlash(): void
    {
        $route = new Route('blog', '/blog{page?}', 'BlogController@index');

        $without = $this->parser->parse('/blog', [$route]);
        $with = $this->parser->parse('/blog/2', [$route]);

        $this->assertNotNull($without);
        $this->assertSame([], $without->getParameters());
        $this->assertNotNull($with);
        $this->assertSame(['page' => '2'], $with->getParameters());
    }
}
