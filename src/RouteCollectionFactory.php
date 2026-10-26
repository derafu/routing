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

use Closure;
use Derafu\Routing\Contract\CollectionInterface;
use Derafu\Routing\ValueObject\Route;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Builds a route catalogue from the supported configuration formats.
 */
final class RouteCollectionFactory
{
    /**
     * @param array<array-key, mixed> $routes
     */
    public function create(array $routes): CollectionInterface
    {
        $collection = new Collection();
        foreach ($routes as $index => $route) {
            $config = $this->resolveRouteConfiguration($index, $route);
            $collection->add(new Route(
                $config['name'],
                $config['path'],
                $config['handler'],
                $config['defaults'],
                $config['methods'],
                $config['roles'],
            ));
        }

        return $collection;
    }

    /**
     * Resolves the configuration of a route, with all its values validated.
     *
     * @param string|int $index The index of the route in the configuration.
     * @param mixed $route The configuration of the route.
     * @return array{name: string, path: string, handler: string|array<array-key, mixed>|Closure, defaults: array<string, mixed>, methods: list<string>, roles: list<string>}
     * @throws InvalidArgumentException If the configuration is not valid.
     */
    private function resolveRouteConfiguration(string|int $index, mixed $route): array
    {
        // If the $index is a string, we have two cases:
        // 1. The $route is an array, it is a route configuration, and the
        //    $index is the route name.
        // 2. The $route is a string, it is the handler and the $index is
        //    the route path (this kind of routes are not named and are
        //    not recommended).
        if (is_string($index)) {
            if (is_array($route)) {
                $route = [
                    'name' => $index,
                    'path' => $route['path'] ?? $route['route'] ?? null,
                    'handler' => $route['handler'] ?? null,
                    'defaults' => $route['defaults'] ?? [],
                    'methods' => $route['methods'] ?? [],
                    'roles' => $route['roles'] ?? [],
                ];
            } elseif (is_string($route)) {
                $hashInput = $index . '::' . $route;
                $route = [
                    'name' => 'unnamed_' . substr(sha1($hashInput), 0, 16),
                    'path' => $index,
                    'handler' => $route,
                    'defaults' => [],
                    'methods' => [],
                    'roles' => [],
                ];
            }
        }

        // If the $index is a numeric index and the $route is an array, it
        // is a route configuration. This also has retrocompatibility with
        // previous router implementation.
        elseif (is_array($route)) {
            $route = [
                'name' => $route['name'] ?? null,
                'path' => $route['path'] ?? $route['route'] ?? null,
                'handler' => $route['handler'] ?? null,
                'defaults' => $route['defaults'] ?? [],
                'methods' => $route['methods'] ?? [],
                'roles' => $route['roles'] ?? [],
            ];
        }

        // If the $route is not an array, it is an invalid route configuration.
        if (!is_array($route)) {
            throw new InvalidArgumentException('Invalid route configuration.');
        }

        // Validate the route configuration.
        if (!isset($route['name'])) {
            throw new InvalidArgumentException([
                'Name is required in route "{route}".',
                'route' => $index,
            ]);
        }
        if (!isset($route['path'])) {
            throw new InvalidArgumentException([
                'Path is required in route "{route}".',
                'route' => $route['name'],
            ]);
        }
        if (!isset($route['handler'])) {
            throw new InvalidArgumentException([
                'Handler is required in route "{route}".',
                'route' => $route['name'],
            ]);
        }

        // Validate the types of the values.
        $name = $this->configurationString($route['name'], 'name', $index);
        $path = $this->configurationString($route['path'], 'path', $name);
        $handler = $route['handler'];
        if (!is_string($handler) && !is_array($handler) && !$handler instanceof Closure) {
            throw new InvalidArgumentException([
                'The key handler of route "{route}" must be a string, an array or a Closure; {type} given.',
                'route' => $name,
                'type' => get_debug_type($handler),
            ]);
        }

        return [
            'name' => $name,
            'path' => $path,
            'handler' => $handler,
            'defaults' => $this->configurationDefaults($route['defaults'], $name),
            'methods' => $this->configurationList($route['methods'], 'methods', $name),
            'roles' => $this->configurationList($route['roles'], 'roles', $name),
        ];
    }

    /**
     * Checks that a value of the configuration of a route is a string.
     *
     * @throws InvalidArgumentException If it is not a string.
     */
    private function configurationString(mixed $value, string $key, string|int $route): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException([
                'The key {key} of route "{route}" must be a string; {type} given.',
                'key' => $key,
                'route' => $route,
                'type' => get_debug_type($value),
            ]);
        }

        return $value;
    }

    /**
     * Checks that the defaults of a route are an array with names as keys.
     *
     * @return array<string, mixed>
     * @throws InvalidArgumentException If they are not.
     */
    private function configurationDefaults(mixed $defaults, string $route): array
    {
        if (!is_array($defaults)) {
            throw $this->invalidDefaults($route);
        }

        $valid = [];
        foreach ($defaults as $key => $value) {
            if (!is_string($key)) {
                throw $this->invalidDefaults($route);
            }
            $valid[$key] = $value;
        }

        return $valid;
    }

    /**
     * Checks that a value of the configuration of a route is a list of strings.
     *
     * @return list<string>
     * @throws InvalidArgumentException If it is not a list of strings.
     */
    private function configurationList(mixed $values, string $key, string $route): array
    {
        if (!is_array($values) || !array_is_list($values)) {
            throw $this->invalidList($key, $route);
        }

        $valid = [];
        foreach ($values as $value) {
            if (!is_string($value)) {
                throw $this->invalidList($key, $route);
            }
            $valid[] = $value;
        }

        return $valid;
    }

    private function invalidDefaults(string $route): InvalidArgumentException
    {
        return new InvalidArgumentException([
            'The key defaults of route "{route}" must be an array with names as keys.',
            'route' => $route,
        ]);
    }

    private function invalidList(string $key, string $route): InvalidArgumentException
    {
        return new InvalidArgumentException([
            'The key {key} of route "{route}" must be a list of strings.',
            'key' => $key,
            'route' => $route,
        ]);
    }
}
