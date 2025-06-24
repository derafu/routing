<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing\Exception;

use Derafu\Translation\Exception\Core\TranslatableRuntimeException as RuntimeException;

/**
 * Base exception for all router related errors.
 */
class RouterException extends RuntimeException
{
}
