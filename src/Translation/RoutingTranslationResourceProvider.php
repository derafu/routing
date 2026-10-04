<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing\Translation;

use Derafu\Translation\Contract\TranslationResourceProviderInterface;

/**
 * Provides the translations of this package: the messages of its exceptions.
 */
final class RoutingTranslationResourceProvider implements TranslationResourceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDirectories(): iterable
    {
        return [__DIR__ . '/../../resources/translations'];
    }
}
