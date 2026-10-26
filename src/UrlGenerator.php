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
use Derafu\Support\Url;
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
        [$path, $extra] = $this->generatePath($route, $parameters);
        $query = http_build_query($extra, '', '&', PHP_QUERY_RFC3986);
        $suffix = $query === '' ? '' : '?' . $query;
        $context = $this->context;
        if ($context === null) {
            if ($referenceType !== UrlReferenceType::ABSOLUTE_PATH) {
                throw new UrlGeneratorException([
                    'A request context is required to generate a URL with reference type "{referenceType}".',
                    'referenceType' => $referenceType->name,
                ]);
            }
            return $path . $suffix;
        }

        return $this->applyReferenceType($path, $referenceType, $context) . $suffix;
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
     * @return array{string, array<string, mixed>} The path and unused explicit parameters.
     */
    private function generatePath(RouteInterface $route, array $parameters): array
    {
        $pattern = $route->getPath();

        // Only explicitly supplied, unused parameters belong in the query.
        $extra = $parameters;

        // Merge route-specific parameters with provided parameters.
        $parameters = array_merge($route->getDefaults(), $parameters);

        $segments = RoutePatternParser::parse($pattern)
            ?? throw UrlGeneratorException::forPatternFailure($pattern, preg_last_error_msg());

        $path = '';
        foreach ($segments as $segment) {
            if (isset($segment['literal'])) {
                $path .= $segment['literal'];
                continue;
            }

            $name = $segment['name'];
            unset($extra[$name]);
            if ($segment['optional']) {
                $path = rtrim($path, '/');
                if (isset($parameters[$name])) {
                    $path .= '/' . $this->encodeParameter($parameters[$name], $name, $pattern, $segment['requirement']);
                }
                continue;
            }

            if (!isset($parameters[$name])) {
                throw new InvalidArgumentException([
                    'Parameter "{parameter}" is required for route "{route}" but was not provided.',
                    'parameter' => $name,
                    'route' => $pattern,
                ]);
            }

            $path .= $this->encodeParameter($parameters[$name], $name, $pattern, $segment['requirement']);
        }

        return [$path, $extra];
    }

    /**
     * Encodes a value accepted by its requirement and the safe-path policy.
     */
    private function encodeParameter(mixed $value, string $name, string $route, string $requirement): string
    {
        $value = $this->urlValue($value, $name, $route);
        $encoded = implode('/', array_map(rawurlencode(...), explode('/', $value)));
        $expression = '#\A(?:' . RoutePatternParser::escapeRegexDelimiter($requirement) . ')\z#D';
        $matches = @preg_match($expression, $value);
        if ($matches === false) {
            throw UrlGeneratorException::forPatternFailure($route, preg_last_error_msg());
        }

        if ($matches !== 1 || Url::normalizePath('/' . $encoded) !== '/' . $encoded) {
            throw new InvalidArgumentException([
                'Parameter "{parameter}" of route "{route}" does not satisfy its requirement or cannot form a safe path.',
                'parameter' => $name,
                'route' => $route,
            ]);
        }

        return $encoded;
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
     * Applies the reference type using the installation prefix and request context.
     */
    private function applyReferenceType(
        string $path,
        UrlReferenceType $referenceType,
        RequestContextInterface $context
    ): string {
        $path = $context->getBaseUrl() . $path;

        return match ($referenceType) {
            UrlReferenceType::ABSOLUTE_PATH => $path,
            UrlReferenceType::ABSOLUTE_URL => $context->getScheme() . '://'
                . $context->getHost() . $this->getPortString($context) . $path,
            UrlReferenceType::NETWORK_PATH => '//'
                . $context->getHost() . $this->getPortString($context) . $path,
            UrlReferenceType::RELATIVE_PATH => $this->relativePath(
                $context->getBaseUrl() . $context->getPathInfo(),
                $path,
            ),
        };
    }

    /**
     * Resolves the target relative to the directory of the current request.
     * A trailing slash identifies a directory; otherwise the final segment is
     * the current file. Retaining the target file also clears an old query.
     */
    private function relativePath(string $current, string $target): string
    {
        $source = explode('/', ltrim($current, '/'));
        $destination = explode('/', ltrim($target, '/'));
        array_pop($source);
        $file = array_pop($destination);

        $common = 0;
        while (isset($source[$common], $destination[$common]) && $source[$common] === $destination[$common]) {
            ++$common;
        }

        $relative = str_repeat('../', count($source) - $common)
            . implode('/', array_slice($destination, $common));
        if ($relative !== '' && !str_ends_with($relative, '/')) {
            $relative .= '/';
        }
        $relative .= $file;

        if ($relative === '') {
            return './';
        }

        // A colon in the first segment would otherwise be interpreted as a scheme.
        return str_contains(explode('/', $relative)[0], ':') ? './' . $relative : $relative;
    }

    /**
     * Gets the port string when it is not the default for the request scheme.
     */
    private function getPortString(RequestContextInterface $context): string
    {
        $scheme = $context->getScheme();
        $port = $scheme === 'https' ? $context->getHttpsPort() : $context->getHttpPort();
        $standardPort = $scheme === 'https' ? 443 : 80;

        return $port !== $standardPort ? ':' . $port : '';
    }
}
