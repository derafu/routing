<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsRouting\Fixture;

use Derafu\Routing\Contract\ParserInterface;
use Derafu\Routing\Contract\RouteInterface;
use Derafu\Routing\Contract\RouteMatchInterface;
use Derafu\Routing\Parser\StaticParser;

/**
 * Resolves a legacy URL to the existing about route before ordinary matching.
 */
final class LegacyPathParser implements ParserInterface
{
    /**
     * {@inheritDoc}
     */
    public function parse(string $uri, array $routes): ?RouteMatchInterface
    {
        return $uri === '/legacy-about'
            ? (new StaticParser())->parse('/about', $routes)
            : null;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(RouteInterface $route): bool
    {
        return $route->getPath() === '/about';
    }
}
