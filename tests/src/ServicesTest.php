<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsRouting;

use Derafu\Routing\Collection;
use Derafu\Routing\Exception\RouteNotFoundException;
use Derafu\Routing\Translation\RoutingTranslationResourceProvider;
use Derafu\Translation\TranslatorFactory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Loads the real provider configuration and consumes its tagged resources.
 */
#[CoversNothing]
final class ServicesTest extends TestCase
{
    private function container(): ContainerBuilder
    {
        $root = dirname(__DIR__, 2);
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator($root . '/resources/config'));
        $loader->load('routing-services.yaml');
        $this->assertArrayHasKey(
            RoutingTranslationResourceProvider::class,
            $container->findTaggedServiceIds('derafu_translation.resource_provider')
        );
        $container->register('app.translator', Translator::class)
            ->setFactory([TranslatorFactory::class, 'create'])
            ->setArguments(['es', ['en'], new TaggedIteratorArgument('derafu_translation.resource_provider')]);
        foreach ($container->getDefinitions() as $definition) {
            $definition->setPublic(true);
        }
        $container->compile();

        return $container;
    }

    public function testConfigurationConstructsTheTranslationProvider(): void
    {
        $provider = $this->container()->get(RoutingTranslationResourceProvider::class);
        $this->assertInstanceOf(RoutingTranslationResourceProvider::class, $provider);
        $directories = [...$provider->getDirectories()];
        $this->assertCount(1, $directories);
        $this->assertSame(realpath(dirname(__DIR__, 2) . '/resources/translations'), realpath($directories[0]));
    }

    public function testTaggedResourcesTranslateARealMissingRouteException(): void
    {
        $translator = $this->container()->get('app.translator');
        $this->assertInstanceOf(Translator::class, $translator);
        try {
            (new Collection())->getByName('missing_route');
            $this->fail('A missing route was accepted.');
        } catch (RouteNotFoundException $exception) {
            $this->assertSame('No se encontró ninguna ruta para "missing_route".', $exception->trans($translator));
        }
    }
}
