<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsRouting\Parser;

use Derafu\Routing\Collection;
use Derafu\Routing\Parser\FileSystemParser;
use Derafu\Routing\RouteCollectionFactory;
use Derafu\Routing\Router;
use Derafu\Routing\UrlGenerator;
use Derafu\Routing\ValueObject\Route;
use Derafu\Routing\ValueObject\RouteMatch;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileSystemParser::class)]
#[CoversClass(Router::class)]
#[CoversClass(RouteCollectionFactory::class)]
#[UsesClass(Collection::class)]
#[UsesClass(UrlGenerator::class)]
#[CoversClass(Route::class)]
#[CoversClass(RouteMatch::class)]
final class FileSystemParserTest extends TestCase
{
    private string $tempDir;

    private FileSystemParser $parser;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/router-test-' . uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir . '/blog');
        $this->parser = new FileSystemParser([$this->tempDir], ['.html.twig', '.md']);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDir . '/*') ?: [] as $entry) {
            if (is_link($entry)) {
                unlink($entry);
            }
        }
        array_map('unlink', glob($this->tempDir . '/*.*') ?: []);
        array_map('unlink', glob($this->tempDir . '/blog/*.*') ?: []);
        rmdir($this->tempDir . '/blog');
        rmdir($this->tempDir);

        foreach ($this->outsideFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Files created outside of the registered directory by the tests.
     *
     * @var array<string>
     */
    private array $outsideFiles = [];

    /**
     * @param non-empty-string $filename
     */
    #[DataProvider('fileRoutesProvider')]
    public function testParseFileRoutes(string $filename, string $uri, bool $shouldMatch): void
    {
        // Create test file.
        file_put_contents($this->tempDir . '/' . $filename, 'test content');

        $match = $this->parser->parse($uri, []);

        if ($shouldMatch) {
            $this->assertNotNull($match);
            $handler = $match->getHandler();
            $this->assertIsString($handler);
            $this->assertStringEndsWith($filename, $handler);
        } else {
            $this->assertNull($match);
        }
    }

    /**
     * @return array<string, array{non-empty-string, string, bool}>
     */
    public static function fileRoutesProvider(): array
    {
        return [
            'markdown-file' => [
                'test.md',
                '/test',
                true,
            ],
            'twig-file' => [
                'page.html.twig',
                '/page',
                true,
            ],
            'nested-file' => [
                'blog/post.md',
                '/blog/post',
                true,
            ],
            'non-existent' => [
                'fake.md',
                '/not-found',
                false,
            ],
        ];
    }

    /**
     * A `..` segment must never be accepted, no matter where it points.
     */
    #[DataProvider('traversalUrisProvider')]
    public function testParseRejectsPathTraversal(string $uri): void
    {
        // Files that would be reached if `..` were honored: one outside of
        // the registered directory and one inside of it.
        $outside = dirname($this->tempDir) . '/secret-' . basename($this->tempDir) . '.md';
        file_put_contents($outside, 'secret');
        $this->outsideFiles[] = $outside;
        file_put_contents($this->tempDir . '/blog/post.md', 'test content');

        $uri = str_replace('{outside}', basename($outside, '.md'), $uri);

        $this->assertNull($this->parser->parse($uri, []));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function traversalUrisProvider(): array
    {
        return [
            'parent-directory' => ['/../{outside}'],
            'nested-parent-directory' => ['/blog/../../{outside}'],
            'no-leading-slash' => ['../{outside}'],
            'multiple-leading-slashes' => ['//../{outside}'],
            'resolves-inside-but-still-rejected' => ['/blog/../blog/post'],
            'only-dots-segment' => ['/..'],
            'trailing-dots-segment' => ['/blog/..'],
            'null-byte' => ["/blog/post\0.txt"],
        ];
    }

    public function testParseRejectsSymlinkPointingOutsideDirectory(): void
    {
        $outsideDir = sys_get_temp_dir() . '/router-outside-' . uniqid();
        mkdir($outsideDir);
        file_put_contents($outsideDir . '/secret.md', 'secret');
        $this->outsideFiles[] = $outsideDir . '/secret.md';
        symlink($outsideDir, $this->tempDir . '/link');

        try {
            $this->assertNull($this->parser->parse('/link/secret', []));
        } finally {
            @unlink($outsideDir . '/secret.md');
            @rmdir($outsideDir);
        }
    }

    /**
     * Names that merely contain dots are legitimate and must keep working.
     */
    public function testParseAllowsDotsInsideNames(): void
    {
        file_put_contents($this->tempDir . '/v1..2.md', 'test content');
        file_put_contents($this->tempDir . '/blog/a.b.md', 'test content');

        $this->assertNotNull($this->parser->parse('/v1..2', []));
        $this->assertNotNull($this->parser->parse('/blog/a.b', []));
    }

    public function testItSupportsTheRoutesWhoseHandlerIsAFileWithAValidExtension(): void
    {
        $this->assertTrue($this->parser->supports(new Route('a', '/a', '/pages/a.md')));
        $this->assertTrue($this->parser->supports(new Route('b', '/b', '/pages/b.html.twig')));
        $this->assertFalse($this->parser->supports(new Route('c', '/c', '/pages/c.txt')));
        $this->assertFalse($this->parser->supports(new Route('d', '/d', ['file' => 'd.md'])));
        $this->assertFalse($this->parser->supports(new Route('e', '/e', fn () => 'e.md')));
    }

    public function testADirectoryThatDoesNotExistIsReported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid directory: /does/not/exist');

        new FileSystemParser(['/does/not/exist'], ['.md']);
    }

    public function testAFileIsNotADirectory(): void
    {
        $file = $this->tempDir . '/file.md';
        file_put_contents($file, 'content');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid directory: ' . $file);

        new FileSystemParser([$file], ['.md']);
    }

    public function testTheLastDirectoryIsLookedUpFirst(): void
    {
        $other = sys_get_temp_dir() . '/router-test-other-' . uniqid();
        mkdir($other);
        file_put_contents($this->tempDir . '/page.md', 'first');
        file_put_contents($other . '/page.md', 'second');

        try {
            $parser = new FileSystemParser([$this->tempDir, $other, $this->tempDir], ['.md']);
            $match = $parser->parse('/page', []);

            $this->assertNotNull($match);
            $handler = $match->getHandler();
            $this->assertIsString($handler);
            $this->assertSame(realpath($other) . '/page.md', $handler);
        } finally {
            unlink($other . '/page.md');
            rmdir($other);
        }
    }

    public function testTheRouterDiscoversFilesWithoutAnyRegisteredRoutes(): void
    {
        file_put_contents($this->tempDir . '/page.md', '# Page');
        $router = new Router(parsers: [$this->parser]);

        $match = $router->match('/page', 'GET');

        $this->assertSame(realpath($this->tempDir . '/page.md'), $match->getHandler());
        $this->assertFalse($router->has($match->getName()));
    }
}
