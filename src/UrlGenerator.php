<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing;

use Derafu\Routing\Contract\CollectionInterface;
use Derafu\Routing\Contract\RequestContextInterface;
use Derafu\Routing\Contract\RouteInterface;
use Derafu\Routing\Contract\UrlGeneratorInterface;
use Derafu\Routing\Enum\UrlReferenceType;
use Derafu\Routing\Exception\RouteNotFoundException;
use Derafu\Routing\Exception\UrlGeneratorException;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;
use Stringable;

/**
 * Generates URLs for named routes.
 */
final class UrlGenerator implements UrlGeneratorInterface
{
    /**
     * Constructor.
     *
     * @param CollectionInterface $routes The route collection.
     * @param RequestContextInterface|null $context The request context.
     */
    public function __construct(
        private readonly CollectionInterface $routes,
        private ?RequestContextInterface $context = null
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function generate(
        string $name,
        array $parameters = [],
        UrlReferenceType $referenceType = UrlReferenceType::ABSOLUTE_PATH
    ): string {
        // Get the route by name from the collection if exists.
        try {
            $route = $this->routes->getByName($name);
        } catch (RouteNotFoundException $e) {
            throw UrlGeneratorException::forRouteNotFound($name);
        }

        // Generate the path by replacing parameters.
        $path = $this->generatePath($route, $parameters);

        // If context is not available or reference type is absolute path, return the path.
        if ($this->context === null || $referenceType === UrlReferenceType::ABSOLUTE_PATH) {
            return $path;
        }

        // Apply reference type if context is available.
        return $this->applyReferenceType($path, $referenceType);
    }

    /**
     * {@inheritDoc}
     */
    public function setContext(RequestContextInterface $context): static
    {
        $this->context = $context;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getContext(): ?RequestContextInterface
    {
        return $this->context;
    }

    /**
     * Generates a URL path by replacing placeholders in the route pattern.
     *
     * @param RouteInterface $route The route
     * @param array<string, mixed> $parameters Parameters to replace in the pattern
     * @return string The generated path
     */
    private function generatePath(RouteInterface $route, array $parameters): string
    {
        $pattern = $route->getPath();

        // Merge route-specific parameters with provided parameters.
        $parameters = array_merge($route->getDefaults(), $parameters);

        // First pass: replace required parameters.
        $path = preg_replace_callback(
            '/{([^:}?]+)(?::[^}]+)?}/',
            function ($matches) use ($parameters, $pattern) {
                $name = $matches[1];

                if (!isset($parameters[$name])) {
                    throw new InvalidArgumentException([
                        'Parameter "{parameter}" is required for route "{route}" but was not provided.',
                        'parameter' => $name,
                        'route' => $pattern,
                    ]);
                }

                return $this->urlValue($parameters[$name], $name, $pattern);
            },
            $pattern
        ) ?? throw UrlGeneratorException::forPatternFailure($pattern, preg_last_error_msg());

        // Second pass: handle optional parameters.
        $path = preg_replace_callback(
            '#/({[^:}]+\?(?::[^}]+)?})#',
            function ($matches) use ($parameters, $pattern) {
                $name = trim($matches[1], '{}:?');
                return isset($parameters[$name])
                    ? '/' . $this->urlValue($parameters[$name], $name, $pattern)
                    : '';
            },
            $path
        ) ?? throw UrlGeneratorException::forPatternFailure($pattern, preg_last_error_msg());

        return $path;
    }

    /**
     * Converts the value of a parameter to the text that goes in the path.
     *
     * @param mixed $value The value of the parameter.
     * @param string $name The name of the parameter.
     * @param string $route The pattern of the path of the route.
     * @return string The text for the path.
     * @throws InvalidArgumentException If the value is not a string, a number,
     * a boolean or an object that can be converted to a string.
     */
    private function urlValue(mixed $value, string $name, string $route): string
    {
        if (is_scalar($value) || $value instanceof Stringable) {
            return (string) $value;
        }

        throw new InvalidArgumentException([
            'Parameter "{parameter}" of route "{route}" must be a string, a number, a boolean or an object that can be converted to a string; {type} given.',
            'parameter' => $name,
            'route' => $route,
            'type' => get_debug_type($value),
        ]);
    }

    /**
     * Applies the reference type to make the URL absolute, network, or relative.
     *
     * @param string $path The path to transform.
     * @param UrlReferenceType $referenceType The type of reference.
     * @return string The transformed URL.
     */
    private function applyReferenceType(
        string $path,
        UrlReferenceType $referenceType
    ): string {
        if ($this->context === null) {
            return $path;
        }

        $baseUrl = $this->context->getBaseUrl();
        $path = $baseUrl . $path;

        switch ($referenceType) {
            case UrlReferenceType::ABSOLUTE_URL:
                return $this->context->getScheme() . '://'
                    . $this->context->getHost()
                    . $this->getPortString()
                    . $path
                ;

            case UrlReferenceType::NETWORK_PATH:
                return '//'
                    . $this->context->getHost()
                    . $this->getPortString()
                    . $path
                ;

            case UrlReferenceType::RELATIVE_PATH:
                // For simplicity, we're not implementing true relative path
                // generation. This would require current path context and
                // complex path transformation.
                throw new UrlGeneratorException(
                    'Relative path generation is not implemented yet.'
                );

            default:
                throw new UrlGeneratorException([
                    'Invalid reference type: "{referenceType}".',
                    'referenceType' => $referenceType->name,
                ]);
        }
    }

    /**
     * Gets the port string for URL generation if it's non-standard.
     *
     * @return string Port string with colon prefix or empty string.
     */
    private function getPortString(): string
    {
        if ($this->context === null) {
            return '';
        }

        $scheme = $this->context->getScheme();
        $port = $scheme === 'https'
            ? $this->context->getHttpsPort()
            : $this->context->getHttpPort()
        ;

        $standardPort = $scheme === 'https' ? 443 : 80;

        return $port !== $standardPort ? ':' . $port : '';
    }
}
