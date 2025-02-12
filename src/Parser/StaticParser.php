<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing\Parser;

use Derafu\Routing\Contract\ParserInterface;
use Derafu\Routing\Contract\RouteInterface;
use Derafu\Routing\Contract\RouteMatchInterface;
use Derafu\Routing\ValueObject\RouteMatch;

/**
 * Parser that handles static (exact match) routes.
 *
 * This parser only matches URIs that exactly match a route's pattern, without
 * any parameters or dynamic segments.
 */
final class StaticParser implements ParserInterface
{
    /**
     * {@inheritDoc}
     */
    public function parse(string $uri, array $routes): ?RouteMatchInterface
    {
        foreach ($routes as $route) {
            if (!$this->supports($route)) {
                continue;
            }

            if ($route->getPattern() === $uri) {
                return new RouteMatch(
                    $route->getHandler(),
                    $route->getParameters(),
                    $route->getName()
                );
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(RouteInterface $route): bool
    {
        $pattern = $route->getPattern();

        // Static routes don't contain any special characters.
        return
            !str_contains($pattern, '{')
            && !str_contains($pattern, '*')
            && !str_contains($pattern, ':')
        ;
    }
}
