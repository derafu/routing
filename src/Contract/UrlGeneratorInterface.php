<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing\Contract;

use Derafu\Routing\Enum\UrlReferenceType;
use Derafu\Routing\Exception\RouteNotFoundException;

/**
 * Interface for the URL generator.
 *
 * Provides methods to generate URLs for named routes with parameters.
 */
interface UrlGeneratorInterface
{
    /**
     * Generates a URL or path for a specific route based on the given
     * parameters.
     *
     * @param string $name The name of the route.
     * @param array $parameters An array of parameters.
     * @param UrlReferenceType $referenceType The type of reference to be
     * generated.
     *
     * @return string The generated URL.
     *
     * @throws RouteNotFoundException If the named route doesn't exist.
     */
    public function generate(
        string $name,
        array $parameters = [],
        UrlReferenceType $referenceType = UrlReferenceType::ABSOLUTE_PATH
    ): string;

    /**
     * Sets the request context.
     *
     * @param RequestContextInterface $context The context.
     * @return static Returns itself for method chaining.
     */
    public function setContext(RequestContextInterface $context): static;

    /**
     * Gets the request context.
     *
     * @return RequestContextInterface|null The context or null if not set.
     */
    public function getContext(): ?RequestContextInterface;
}
