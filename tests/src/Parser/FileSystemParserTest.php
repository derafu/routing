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

use Derafu\Routing\Parser\FileSystemParser;
use Derafu\Routing\ValueObject\Route;
use Derafu\Routing\ValueObject\RouteMatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileSystemParser::class)]
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
        array_map('unlink', glob($this->tempDir . '/*.*'));
        array_map('unlink', glob($this->tempDir . '/blog/*.*'));
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

    #[DataProvider('fileRoutesProvider')]
    public function testParseFileRoutes(string $filename, string $uri, bool $shouldMatch): void
    {
        // Create test file.
        file_put_contents($this->tempDir . '/' . $filename, 'test content');

        $match = $this->parser->parse($uri, []);

        if ($shouldMatch) {
            $this->assertNotNull($match);
            $this->assertStringEndsWith($filename, $match->getHandler());
        } else {
            $this->assertNull($match);
        }
    }

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
}
