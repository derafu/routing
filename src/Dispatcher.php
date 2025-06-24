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
use Derafu\Routing\Contract\DispatcherInterface;
use Derafu\Routing\Contract\RouteMatchInterface;
use Derafu\Routing\Exception\DispatcherException;

/**
 * A simple dispatcher that handles common route match cases.
 *
 * This is an implementation that shows how to handle RouteMatch objects.
 *
 * It supports:
 *
 *   - File based handlers with a renderer (eg.: .html.twig and .md).
 *   - Closure handlers.
 *   - Controller::action string notation.
 */
final class Dispatcher implements DispatcherInterface
{
    /**
     * Dispatcher constructor and dependencies.
     *
     * @param array<string,Closure> $renderers List of valid renderers according
     * to the file extensions that are going to be handled.
     */
    public function __construct(private array $renderers = [])
    {
        $this->renderers = $renderers;
    }

    /**
     * Executes the handler from a route match.
     *
     * @param RouteMatchInterface $match The route match to dispatch.
     * @return mixed The result of executing the handler.
     * @throws DispatcherException If the handler cannot be dispatched.
     */
    public function dispatch(RouteMatchInterface $match): mixed
    {
        $handler = $match->getHandler();
        $params = $match->getParameters();

        // Handle files with a renderer (eg.: .html.twig and .md).
        if (is_string($handler) && file_exists($handler)) {
            return $this->handleFile($handler, $params);
        }

        // Handle "Controller::action" strings.
        if (is_string($handler) && str_contains($handler, '::')) {
            return $this->handleControllerAction($handler, $params);
        }

        // Handle Closures.
        if ($handler instanceof Closure) {
            return $handler($params);
        }

        throw new DispatcherException([
            'Unable to dispatch handler of type: {type}',
            'type' => get_debug_type($handler),
        ]);
    }

    /**
     * Handles file-based routes (.md, .html.twig).
     *
     * @param string $file The file path.
     * @param array $params Parameters for the file handler.
     * @return string The processed file content.
     * @throws DispatcherException If the file type is not supported.
     */
    private function handleFile(string $file, array $params): string
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        if (!isset($this->renderers[$extension])) {
            throw new DispatcherException([
                'Unsupported file type: {extension}',
                'extension' => $extension,
            ]);
        }

        return $this->renderers[$extension]($file, $params);
    }

    /**
     * Handles "Controller::action" style handlers.
     *
     * @param string $handler The handler in "Controller::action" format.
     * @param array $params Parameters for the controller action.
     * @return mixed The result of the controller action.
     * @throws DispatcherException If the controller or action cannot be found.
     */
    private function handleControllerAction(string $handler, array $params): mixed
    {
        [$controller, $action] = explode('::', $handler);

        if (!class_exists($controller)) {
            throw new DispatcherException([
                'Controller not found: {controller}',
                'controller' => $controller,
            ]);
        }

        $instance = new $controller();

        if (!method_exists($instance, $action)) {
            throw new DispatcherException([
                'Action not found: {controller}::{action}',
                'controller' => $controller,
                'action' => $action,
            ]);
        }

        return $instance->$action($params);
    }
}
