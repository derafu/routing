<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsRouting\Translation;

use Derafu\Routing\Exception\RouteNotFoundException;
use Derafu\Routing\Exception\RouterException;
use Derafu\Routing\Translation\RoutingTranslationResourceProvider;
use Derafu\Translation\Lint\TranslationAudit;
use Derafu\Translation\TranslatorFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * The package is translated: every message has its Spanish translation, the
 * catalogue has nothing the code does not use, every message can be checked by
 * reading the code, and every exception that the package throws is translatable.
 *
 * It is found by reading the code, so a new message without an entry in the
 * catalogue fails here, instead of showing in the original language when the
 * error happens.
 */
#[CoversClass(RoutingTranslationResourceProvider::class)]
#[UsesClass(RouteNotFoundException::class)]
#[UsesClass(RouterException::class)]
final class RoutingMessagesTest extends TestCase
{
    public function testThePackageIsTranslated(): void
    {
        $report = (new TranslationAudit())->audit(
            dirname(__DIR__, 3) . '/src',
            new RoutingTranslationResourceProvider()
        );

        // Finding nothing would look like a clean result.
        $this->assertFalse($report->nothingFound);
        $this->assertSame([], $report->describe($report->dynamicMessages));
        $this->assertSame([], $report->describe($report->missingTranslations));
        $this->assertSame([], $report->describe($report->notUsedBySources));
        $this->assertSame([], $report->describe($report->notTranslatable));
    }

    public function testAnExceptionIsTranslated(): void
    {
        $translator = TranslatorFactory::create('es', [], [new RoutingTranslationResourceProvider()]);

        $this->assertSame(
            'No se encontró ninguna ruta para "/pagina".',
            (new RouteNotFoundException('/pagina'))->trans($translator)
        );
    }
}
