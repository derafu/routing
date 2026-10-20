<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Routing library.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsRouting;

use Closure;
use Derafu\Routing\Dispatcher;
use Derafu\Routing\Exception\DispatcherException;
use Derafu\Routing\ValueObject\Route;
use Derafu\Routing\ValueObject\RouteMatch;
use Derafu\Translation\Contract\TranslatableInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Controller that the handlers of the tests point to.
 */
final class DispatcherTestController
{
    /**
     * @param array<string, mixed> $params
     */
    public function show(array $params): string
    {
        return 'show:' . json_encode($params);
    }
}

#[CoversClass(Dispatcher::class)]
#[CoversClass(DispatcherException::class)]
#[CoversClass(Route::class)]
#[CoversClass(RouteMatch::class)]
final class DispatcherTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/dispatcher-test-' . uniqid();
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory);
    }

    /**
     * @param string|array<array-key, mixed>|Closure $handler
     * @param array<string, mixed> $parameters
     */
    private function match(string|array|Closure $handler, array $parameters = []): RouteMatch
    {
        return new RouteMatch(new Route('test', '/test', $handler), $parameters);
    }

    private function file(string $name): string
    {
        $file = $this->directory . '/' . $name;
        file_put_contents($file, 'content');

        return $file;
    }

    /**
     * @return array<string, Closure>
     */
    private function renderers(): array
    {
        return [
            'md' => fn (string $file, array $params): string => 'markdown:' . basename($file) . ':' . json_encode($params),
            'twig' => fn (string $file, array $params): string => 'twig:' . basename($file),
        ];
    }

    #[Test]
    public function aFileIsRenderedWithTheRendererOfItsExtension(): void
    {
        $dispatcher = new Dispatcher($this->renderers());

        $this->assertSame(
            'markdown:page.md:{"id":5}',
            $dispatcher->dispatch($this->match($this->file('page.md'), ['id' => 5]))
        );
        $this->assertSame(
            'twig:page.html.twig',
            $dispatcher->dispatch($this->match($this->file('page.html.twig')))
        );
    }

    #[Test]
    public function aFileWithAnExtensionThatHasNoRendererIsReported(): void
    {
        $dispatcher = new Dispatcher($this->renderers());

        try {
            $dispatcher->dispatch($this->match($this->file('notes.txt')));
            $this->fail('A file without a renderer must be reported.');
        } catch (DispatcherException $exception) {
            $this->assertInstanceOf(TranslatableInterface::class, $exception);
            $this->assertSame('Unsupported file type: txt', $exception->getMessage());
        }
    }

    #[Test]
    public function aControllerActionIsCalledWithTheParameters(): void
    {
        $handler = DispatcherTestController::class . '::show';

        $result = (new Dispatcher())->dispatch($this->match($handler, ['id' => 7]));

        $this->assertSame('show:{"id":7}', $result);
    }

    #[Test]
    public function aClosureIsCalledWithTheParameters(): void
    {
        $handler = fn (array $params): string => 'closure:' . json_encode($params);

        $this->assertSame('closure:{"a":1}', (new Dispatcher())->dispatch($this->match($handler, ['a' => 1])));
    }

    /**
     * @return array<string, array{string|array<array-key, mixed>, string}>
     */
    public static function handlersThatCanNotBeDispatchedProvider(): array
    {
        return [
            'a controller that does not exist' => [
                'Not\\A\\Controller::show',
                'Controller not found: Not\\A\\Controller',
            ],
            'an action that does not exist' => [
                DispatcherTestController::class . '::missing',
                'Action not found: ' . DispatcherTestController::class . '::missing',
            ],
            'a string that is not a file or a controller action' => [
                'just-some-text',
                'Unable to dispatch handler of type: string',
            ],
            'an array' => [
                ['controller' => 'UserController', 'action' => 'index'],
                'Unable to dispatch handler of type: array',
            ],
        ];
    }

    /**
     * @param string|array<array-key, mixed> $handler
     */
    #[Test]
    #[DataProvider('handlersThatCanNotBeDispatchedProvider')]
    public function aHandlerThatCanNotBeDispatchedIsReported(string|array $handler, string $message): void
    {
        try {
            (new Dispatcher())->dispatch($this->match($handler));
            $this->fail('A handler that can not be dispatched must be reported.');
        } catch (DispatcherException $exception) {
            $this->assertInstanceOf(TranslatableInterface::class, $exception);
            $this->assertSame($message, $exception->getMessage());
        }
    }
}
