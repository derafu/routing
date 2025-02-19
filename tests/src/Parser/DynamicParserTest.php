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

use Derafu\Routing\Parser\DynamicParser;
use Derafu\Routing\ValueObject\Route;
use Derafu\Routing\ValueObject\RouteMatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DynamicParser::class)]
#[CoversClass(Route::class)]
#[CoversClass(RouteMatch::class)]
final class DynamicParserTest extends TestCase
{
    private DynamicParser $parser;

    protected function setUp(): void
    {
        $this->parser = new DynamicParser();
    }

    #[DataProvider('dynamicRoutesProvider')]
    public function testParseDynamicRoutes(
        string $pattern,
        string $uri,
        array $expectedParams,
        bool $shouldMatch
    ): void {
        $route = new Route($pattern, 'TestController@action');
        $match = $this->parser->parse($uri, [$route]);

        if ($shouldMatch) {
            $this->assertNotNull($match);
            $this->assertSame($expectedParams, $match->getParameters());
        } else {
            $this->assertNull($match);
        }
    }

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
}
