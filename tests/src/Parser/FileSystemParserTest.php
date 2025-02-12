<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsRouting\Parser;

use Derafu\Routing\Parser\FileSystemParser;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Derafu\Routing\Parser\FileSystemParser
 */
final class FileSystemParserTest extends TestCase
{
    private string $tempDir;

    private FileSystemParser $parser;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/router-test-' . uniqid();
        mkdir($this->tempDir);
        $this->parser = new FileSystemParser([$this->tempDir], ['.html.twig', '.md']);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tempDir . '/*.*'));
        rmdir($this->tempDir);
    }

    /**
     * @dataProvider fileRoutesProvider
     */
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
}
