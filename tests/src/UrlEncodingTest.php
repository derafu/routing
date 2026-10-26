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

use Derafu\Routing\Collection;
use Derafu\Routing\Exception\InvalidPathException;
use Derafu\Routing\Exception\UrlGeneratorException;
use Derafu\Routing\Parser\DynamicParser;
use Derafu\Routing\RouteCollectionFactory;
use Derafu\Routing\RoutePatternParser;
use Derafu\Routing\Router;
use Derafu\Routing\UrlGenerator;
use Derafu\Routing\ValueObject\Route;
use Derafu\Routing\ValueObject\RouteMatch;
use Derafu\Translation\Contract\TranslatableInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(UrlGenerator::class)]
#[CoversClass(DynamicParser::class)]
#[CoversClass(Router::class)]
#[CoversClass(RouteCollectionFactory::class)]
#[CoversClass(RoutePatternParser::class)]
#[CoversClass(Collection::class)]
#[CoversClass(Route::class)]
#[CoversClass(RouteMatch::class)]
#[CoversClass(InvalidPathException::class)]
#[CoversClass(UrlGeneratorException::class)]
final class UrlEncodingTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function values(): array
    {
        return [
            'space' => ['/search/{value}', 'hello world', '/search/hello%20world'],
            'unicode' => ['/search/{value}', 'café y té', '/search/caf%C3%A9%20y%20t%C3%A9'],
            'query and fragment' => ['/search/{value}', 'a?b#c', '/search/a%3Fb%23c'],
            'URL punctuation' => ['/search/{value}', 'a+b&=;:@[]{}', '/search/a%2Bb%26%3D%3B%3A%40%5B%5D%7B%7D'],
            'literal percent' => ['/search/{value}', '100%', '/search/100%25'],
            'encoded separator as text' => ['/search/{value}', '%2F', '/search/%252F'],
            'encoded traversal as text' => ['/search/{value}', '%2e%2e', '/search/%252e%252e'],
            'encoded space as text' => ['/search/{value}', '%20', '/search/%2520'],
            'greedy path' => ['/docs/{value:.+}', 'guide/first steps', '/docs/guide/first%20steps'],
            'custom multi-segment requirement' => ['/docs/{value:[a-z]+(?:/[a-z]+)*}', 'guide/start', '/docs/guide/start'],
            'regex delimiter' => ['/codes/{value:[a-z#]+}', 'a#b', '/codes/a%23b'],
            'escaped regex delimiter' => ['/codes/{value:[a-z\\#]+}', 'a#b', '/codes/a%23b'],
            'unicode requirement' => ['/names/{value:café}', 'café', '/names/caf%C3%A9'],
            'optional parameter' => ['/search/{value?}', 'hello world', '/search/hello%20world'],
        ];
    }

    #[DataProvider('values')]
    public function testGenerationAndMatchingPreserveTheOriginalValue(string $pattern, string $value, string $expected): void
    {
        $router = new Router(parsers: [new DynamicParser()]);
        $router->addRoute('example', $pattern, 'ExampleController@index');

        $path = $router->generate('example', ['value' => $value]);

        $this->assertSame($expected, $path);
        $this->assertSame(['value' => $value], $router->match($path, 'GET')->getParameters());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidValues(): array
    {
        return [
            'slash in a single segment' => ['/search/{value}', 'a/b'],
            'slash excluded by a custom requirement' => ['/search/{value:[^/]+}', 'a/b'],
            'custom requirement rejects the value' => ['/search/{value:[a-z]+}', 'abc/123'],
            'parent segment' => ['/docs/{value:.+}', 'a/../b'],
            'current segment' => ['/docs/{value:.+}', 'a/./b'],
            'parent as the whole value' => ['/docs/{value:.+}', '..'],
            'leading separator' => ['/docs/{value:.+}', '/a'],
            'trailing separator' => ['/docs/{value:.+}', 'a/'],
            'duplicate separator' => ['/docs/{value:.+}', 'a//b'],
            'backslash' => ['/docs/{value:.+}', 'a\\b'],
            'null byte' => ['/search/{value}', "a\0b"],
            'control character' => ['/search/{value}', "a\nb"],
        ];
    }

    #[DataProvider('invalidValues')]
    public function testGenerationRejectsValuesThatCannotRoundTripSafely(string $pattern, string $value): void
    {
        $router = new Router();
        $router->addRoute('example', $pattern, 'ExampleController@index');

        try {
            $router->generate('example', ['value' => $value]);
            $this->fail('An invalid path parameter must be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertInstanceOf(TranslatableInterface::class, $exception);
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsafePaths(): array
    {
        return [
            'escaped slash' => ['/docs/a%2Fb'],
            'escaped backslash' => ['/docs/a%5Cb'],
            'escaped traversal' => ['/docs/%2e%2e/secret'],
            'escaped null' => ['/docs/a%00b'],
        ];
    }

    #[DataProvider('unsafePaths')]
    public function testMatchingStillRejectsUnsafePathsBeforeDecodingParameters(string $path): void
    {
        $router = new Router(parsers: [new DynamicParser()]);
        $router->addRoute('example', '/docs/{value:.+}', 'ExampleController@index');

        $this->assertNull((new DynamicParser())->parse($path, [
            new Route('direct', '/docs/{value:.+}', 'ExampleController@index'),
        ]));
        $this->expectException(InvalidPathException::class);
        $router->match($path, 'GET');
    }

    public function testAnInvalidRequirementIsReportedAsATranslatableGenerationError(): void
    {
        $router = new Router();
        $router->addRoute('example', '/codes/{value:(}', 'ExampleController@index');

        try {
            $router->generate('example', ['value' => 'abc']);
            $this->fail('An invalid requirement must be reported.');
        } catch (UrlGeneratorException $exception) {
            $this->assertInstanceOf(TranslatableInterface::class, $exception);
            $this->assertStringContainsString('/codes/{value:(}', $exception->getMessage());
        }
    }
}
