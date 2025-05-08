<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing\Parser;

use Derafu\Routing\Contract\ParserInterface;
use Derafu\Routing\Contract\RouteInterface;
use Derafu\Routing\ValueObject\Route;
use Derafu\Routing\ValueObject\RouteMatch;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Parser that handles file-based routes.
 *
 * This parser looks for files in registered directories that match the URI path
 * with supported extensions (.md, .html.twig, etc).
 */
final class FileSystemParser implements ParserInterface
{
    /**
     * List of registered directories to search for files.
     *
     * @var array<string>
     */
    private array $directories = [];

    /**
     * List of valid extensions in filenames.
     *
     * @var array<string>
     */
    private array $extensions;

    /**
     * Creates a new FileSystemParser instance.
     *
     * @param array $directories List of directories to search in.
     * @param array $extensions List of valid extensions in filenames.
     */
    public function __construct(array $directories, array $extensions)
    {
        foreach ($directories as $directory) {
            $this->addDirectory($directory);
        }

        $this->extensions = $extensions;
    }

    /**
     * {@inheritDoc}
     */
    public function parse(string $uri, array $routes): ?RouteMatch
    {
        // Remove leading slash for filesystem paths.
        $path = ltrim($uri, '/');

        foreach ($this->directories as $directory) {
            // Look for files with supported extensions.
            foreach ($this->extensions as $extension) {
                $filepath = $directory . '/' . $path . $extension;

                if (file_exists($filepath)) {

                    $route = new Route(
                        name: 'route_' . uniqid(),
                        path: '/' . $path,
                        handler: $filepath,
                        defaults: ['uri' => $uri]
                    );

                    return new RouteMatch($route, $route->getDefaults());
                }
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(RouteInterface $route): bool
    {
        $handler = $route->getHandler();

        // Only support string handlers that end with supported extensions.
        return is_string($handler) && $this->hasValidExtension($handler);
    }

    /**
     * Checks if a path has one of the supported extensions.
     */
    private function hasValidExtension(string $path): bool
    {
        foreach ($this->extensions as $extension) {
            if (str_ends_with($path, $extension)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adds a directory for file-based route discovery.
     *
     * @param string $directory Path to the directory to search for route files.
     * @return static Returns itself for method chaining.
     */
    private function addDirectory(string $directory): static
    {
        $realPath = realpath($directory);
        if ($realPath === false || !is_dir($realPath)) {
            throw new InvalidArgumentException([
                'Invalid directory: {directory}',
                'directory' => $directory,
            ]);
        }

        if (!in_array($realPath, $this->directories)) {
            array_unshift($this->directories, $realPath);
        }

        return $this;
    }
}
