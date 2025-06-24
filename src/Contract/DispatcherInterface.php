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

use Derafu\Routing\Exception\RouterException;

/**
 * Contract for dispatchers.
 *
 * Dispatchers are responsible for handling a route match and process the
 * request.
 */
interface DispatcherInterface
{
    /**
     * Executes the handler from a route match.
     *
     * @param RouteMatchInterface $match The route match to dispatch.
     * @return mixed The result of executing the handler.
     * @throws RouterException If the handler cannot be dispatched.
     */
    public function dispatch(RouteMatchInterface $match): mixed;
}
