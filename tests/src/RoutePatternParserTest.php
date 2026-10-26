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
use Derafu\Routing\Parser\DynamicParser;
use Derafu\Routing\RoutePatternParser;
use Derafu\Routing\UrlGenerator;
use Derafu\Routing\ValueObject\Route;
use Derafu\Routing\ValueObject\RouteMatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RoutePatternParser::class)]
#[CoversClass(DynamicParser::class)]
#[CoversClass(UrlGenerator::class)]
#[CoversClass(Collection::class)]
#[CoversClass(Route::class)]
#[CoversClass(RouteMatch::class)]
final class RoutePatternParserTest extends TestCase
{
    /**
     * @param array<string, string> $parameters
     */
    #[DataProvider('patterns')]
    public function testMatchingAndGenerationInterpretTheSamePattern(
        string $pattern,
        array $parameters,
        string $path,
        string $invalidPath
    ): void {
        $route = new Route('example', $pattern, 'ExampleController@index');
        $routes = new Collection();
        $routes->add($route);
        $generator = new UrlGenerator($routes);
        $parser = new DynamicParser();

        $this->assertSame($path, $generator->generate('example', $parameters));
        $match = $parser->parse($path, [$route]);
        $this->assertNotNull($match);
        $this->assertSame($parameters, $match->getParameters());
        $this->assertNull($parser->parse($invalidPath, [$route]));
    }

    /**
     * @return array<string, array{string, array<string, string>, string, string}>
     */
    public static function patterns(): array
    {
        return [
            'exact quantifier' => ['/users/{id:\d{2}}', ['id' => '12'], '/users/12', '/users/123'],
            'range quantifier' => ['/users/{id:\d{2,4}}', ['id' => '123'], '/users/123', '/users/1'],
            'open quantifier' => ['/users/{id:\d{2,}}', ['id' => '12345'], '/users/12345', '/users/x'],
            'braces inside a character class' => ['/codes/{code:[{}]+}', ['code' => '{}'], '/codes/%7B%7D', '/codes/x'],
            'escaped braces' => ['/codes/{code:\{\d{2}\}}', ['code' => '{12}'], '/codes/%7B12%7D', '/codes/{1}'],
            'lazy requirement' => ['/users/{id:\d+?}', ['id' => '12'], '/users/12', '/users/x'],
            'optional constrained parameter present' => ['/users/{id?:\d{2}}', ['id' => '12'], '/users/12', '/users/x'],
            'optional constrained parameter absent' => ['/users/{id?:\d{2}}', [], '/users', '/users/1'],
            'adjacent parameters' => ['/codes/{prefix:[A-Z]{2}}{id:\d{2}}', ['prefix' => 'AB', 'id' => '12'], '/codes/AB12', '/codes/A12'],
        ];
    }
}
