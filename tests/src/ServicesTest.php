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
use Derafu\Routing\Contract\RouteMatcherInterface;
use Derafu\Routing\Contract\RouteRegistrarInterface;
use Derafu\Routing\Contract\RouterInterface;
use Derafu\Routing\Contract\UrlGeneratorInterface;
use Derafu\Routing\Exception\RouteNotFoundException;
use Derafu\Routing\Translation\RoutingTranslationResourceProvider;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Exercises the production router configuration and application contributions.
 */
#[CoversNothing]
final class ServicesTest extends TestCase
{
    private function container(string $configuration = 'services.yaml'): ContainerBuilder
    {
        $root = dirname(__DIR__, 2);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $root);
        $loader = new YamlFileLoader($container, new FileLocator($root . '/tests/fixtures/config'));
        $loader->load($configuration);
        $this->assertArrayHasKey(
            RoutingTranslationResourceProvider::class,
            $container->findTaggedServiceIds('derafu_translation.resource_provider')
        );
        foreach ($container->getDefinitions() as $definition) {
            $definition->setPublic(true);
        }
        foreach ($container->getAliases() as $alias) {
            $alias->setPublic(true);
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
        $container = $this->container();
        $translator = $container->get('app.translator');
        $this->assertInstanceOf(Translator::class, $translator);
        try {
            (new Collection())->getByName('missing_route');
            $this->fail('A missing route was accepted.');
        } catch (RouteNotFoundException $exception) {
            $this->assertSame('No se encontró ninguna ruta para "missing_route".', $exception->trans($translator));
        }
    }

    public function testConfiguredRouterMatchesStaticAndDynamicRoutesInPriorityOrder(): void
    {
        $router = $this->container()->get(RouterInterface::class);
        $this->assertInstanceOf(RouterInterface::class, $router);
        $this->assertSame('new_article', $router->match('/articles/new', 'GET')->getName());
        $match = $router->match('/articles/example', 'GET');
        $this->assertSame('article', $match->getName());
        $this->assertSame(['slug' => 'example'], $match->getParameters());
        $this->assertSame('/articles/example', $router->generate('article', ['slug' => 'example']));
    }

    public function testConfiguredCapabilitiesShareTheRouterAndSeeNewRoutes(): void
    {
        $container = $this->container();
        $router = $container->get(RouterInterface::class);
        $this->assertInstanceOf(RouterInterface::class, $router);
        foreach ([UrlGeneratorInterface::class, RouteRegistrarInterface::class, RouteMatcherInterface::class] as $id) {
            $this->assertSame($router, $container->get($id));
        }
        $registrar = $container->get(RouteRegistrarInterface::class);
        $this->assertInstanceOf(RouteRegistrarInterface::class, $registrar);
        $registrar->addRoute('added', '/added/{id}', 'AddedController::show');
        $generator = $container->get(UrlGeneratorInterface::class);
        $this->assertInstanceOf(UrlGeneratorInterface::class, $generator);
        $this->assertSame('/added/7', $generator->generate('added', ['id' => 7]));
        $matcher = $container->get(RouteMatcherInterface::class);
        $this->assertInstanceOf(RouteMatcherInterface::class, $matcher);
        $this->assertSame('added', $matcher->match('/added/7', 'GET')->getName());
    }

    public function testTaggedParserCanOverrideStaticMatchingWithoutReplacingTheRouter(): void
    {
        $router = $this->container('custom-parser.yaml')->get(RouterInterface::class);
        $this->assertInstanceOf(RouterInterface::class, $router);
        $this->assertSame('about', $router->match('/legacy-about', 'GET')->getName());
        $this->assertSame('new_article', $router->match('/articles/new', 'GET')->getName());
        $this->assertSame('article', $router->match('/articles/example', 'GET')->getName());
    }

    public function testOptionalFilesystemConfigurationKeepsExplicitRoutesFirst(): void
    {
        $router = $this->container('filesystem.yaml')->get(RouterInterface::class);
        $this->assertInstanceOf(RouterInterface::class, $router);
        $this->assertSame('about', $router->match('/about', 'GET')->getName());
        $this->assertSame('article', $router->match('/articles/example', 'GET')->getName());
        $this->assertSame(
            dirname(__DIR__) . '/fixtures/pages/help.md',
            $router->match('/help', 'GET')->getHandler()
        );
    }

    public function testBaseConfigurationDoesNotExposeFiles(): void
    {
        $router = $this->container()->get(RouterInterface::class);
        $this->assertInstanceOf(RouterInterface::class, $router);
        $this->expectException(RouteNotFoundException::class);
        $router->match('/help', 'GET');
    }
}
