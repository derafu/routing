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
use Derafu\Routing\Exception\UrlGeneratorException;
use InvalidArgumentException;

/**
 * Interface for the URL generator.
 *
 * Provides methods to generate URLs for named routes with parameters.
 */
interface UrlGeneratorInterface
{
    /**
     * Generates a URL or path for a specific route based on the given
     * parameters. Path parameters are encoded segment by segment and must satisfy
     * their requirements and the safe-path policy. Unused explicit parameters
     * become an RFC 3986 query string using PHP's http_build_query semantics;
     * unused route defaults are not included.
     *
     * All references include the context's installation prefix. Without context,
     * only ABSOLUTE_PATH can be generated. RELATIVE_PATH is resolved against the
     * directory of the current context path, preserving trailing-slash semantics.
     *
     * @param string $name The name of the route.
     * @param array<string, mixed> $parameters An array of parameters.
     * @param UrlReferenceType $referenceType The type of reference to be
     * generated.
     *
     * @return string The generated URL.
     *
     * @throws UrlGeneratorException If the named route doesn't exist or the URL
     * cannot be generated, or the requested reference needs a missing context.
     * @throws InvalidArgumentException If a parameter that the route needs is
     * missing, cannot be converted to a string, fails its requirement or cannot
     * form a safe path.
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
