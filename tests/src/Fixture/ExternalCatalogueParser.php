<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsRouting\Fixture;

use Derafu\Routing\Contract\CollectionInterface;
use Derafu\Routing\Contract\ParserInterface;
use Derafu\Routing\Contract\RouteInterface;
use Derafu\Routing\Contract\RouteMatchInterface;
use Derafu\Routing\Parser\StaticParser;

/**
 * Matches static routes discovered in a catalogue outside the router.
 */
final class ExternalCatalogueParser implements ParserInterface
{
    public function __construct(private readonly CollectionInterface $collection)
    {
    }

    public function parse(string $uri, array $routes): ?RouteMatchInterface
    {
        return (new StaticParser())->parse($uri, $this->collection->all());
    }

    public function supports(RouteInterface $route): bool
    {
        return (new StaticParser())->supports($route);
    }
}
