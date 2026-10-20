<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing\ValueObject;

use Derafu\Routing\Contract\RequestContextInterface;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Holds information about the current request.
 */
final class RequestContext implements RequestContextInterface
{
    /**
     * Constructor.
     *
     * @param string $baseUrl The base URL.
     * @param string $method The HTTP method.
     * @param string $host The HTTP host name.
     * @param string $scheme The HTTP scheme.
     * @param int $httpPort The HTTP port.
     * @param int $httpsPort The HTTPS port.
     * @param string $pathInfo The path info.
     * @param string $queryString The query string.
     */
    public function __construct(
        private string $baseUrl = '',
        private string $method = 'GET',
        private string $host = 'localhost',
        private string $scheme = 'http',
        private int $httpPort = 80,
        private int $httpsPort = 443,
        private string $pathInfo = '/',
        private string $queryString = ''
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * {@inheritDoc}
     */
    public function setBaseUrl(string $baseUrl): static
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getPathInfo(): string
    {
        return $this->pathInfo;
    }

    /**
     * {@inheritDoc}
     */
    public function setPathInfo(string $pathInfo): static
    {
        $this->pathInfo = $pathInfo;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * {@inheritDoc}
     */
    public function setMethod(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * {@inheritDoc}
     */
    public function setHost(string $host): static
    {
        $this->host = $host;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * {@inheritDoc}
     */
    public function setScheme(string $scheme): static
    {
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getHttpPort(): int
    {
        return $this->httpPort;
    }

    /**
     * {@inheritDoc}
     */
    public function setHttpPort(int $httpPort): static
    {
        $this->httpPort = $httpPort;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getHttpsPort(): int
    {
        return $this->httpsPort;
    }

    /**
     * {@inheritDoc}
     */
    public function setHttpsPort(int $httpsPort): static
    {
        $this->httpsPort = $httpsPort;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getQueryString(): string
    {
        return $this->queryString;
    }

    /**
     * {@inheritDoc}
     */
    public function setQueryString(string $queryString): static
    {
        $this->queryString = $queryString;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        $context = [
            'method' => $this->method,
            'baseUrl' => $this->baseUrl,
            'pathInfo' => $this->pathInfo,
            'host' => $this->host,
            'scheme' => $this->scheme,
            'httpPort' => $this->httpPort,
            'httpsPort' => $this->httpsPort,
            'queryString' => $this->queryString,
        ];

        return http_build_query($context, '', ', ');
    }

    /**
     * Creates a new request context from a PSR-7 server request.
     *
     * The data of the context (see below) wins over the data of the request:
     *
     *   - `URL_SCHEME`: The scheme (`http` or `https`).
     *   - `URL_HOST`: The host name.
     *   - `URL_PORT`: The port, for the scheme of the request.
     *   - `APP_BASE_PATH`: The base path of the application.
     *   - `URL_URI`: The path of the request.
     *
     * Without a port in the context or in the URI of the request, the ports are
     * the default ones of each scheme (80 and 443).
     *
     * @param ServerRequestInterface $request A PSR-7 server request.
     * @param string|array<string, mixed> $context The context attribute name or
     * an array of context data.
     * @return static A new request context instance.
     * @throws InvalidArgumentException If the context is not valid.
     */
    public static function fromRequest(
        ServerRequestInterface $request,
        string|array $context = 'derafu.context'
    ): static {
        // Get data from context attribute and other sources of the request.
        if (is_string($context)) {
            $attribute = $request->getAttribute($context, []);
            if (!is_array($attribute)) {
                throw new InvalidArgumentException([
                    'The context attribute {attribute} must be an array; {type} given.',
                    'attribute' => $context,
                    'type' => get_debug_type($attribute),
                ]);
            }
            $context = $attribute;
        }
        $server = $request->getServerParams();
        $uri = $request->getUri();

        // Determine scheme.
        $scheme = self::contextString($context, 'URL_SCHEME') ?? ($uri->getScheme() ?: 'http');

        // Determine host.
        $serverName = $server['SERVER_NAME'] ?? null;
        $host = self::contextString($context, 'URL_HOST')
            ?? ($uri->getHost() ?: (is_string($serverName) && $serverName !== '' ? $serverName : 'localhost'));

        // Set default ports.
        $httpPort = 80;
        $httpsPort = 443;

        // Adjust ports.
        $port = self::contextPort($context) ?? $uri->getPort();
        if ($port !== null) {
            if ('http' === $scheme) {
                $httpPort = $port;
            } elseif ('https' === $scheme) {
                $httpsPort = $port;
            }
        }

        // Determine base URL.
        $baseUrl = self::contextString($context, 'APP_BASE_PATH') ?? '';

        // Determine path info.
        $pathInfo = self::contextString($context, 'URL_URI');
        if ($pathInfo === null) {
            $requestPath = $uri->getPath();
            $pathInfo = $requestPath;
            if ($baseUrl !== '' && str_starts_with($requestPath, $baseUrl)) {
                $pathInfo = substr($requestPath, strlen($baseUrl)) ?: '/';
            }
            $pathInfo = '/' . ltrim($pathInfo, '/');
        }

        // Build the context.
        return new static(
            $baseUrl,
            $request->getMethod(),
            $host,
            $scheme,
            $httpPort,
            $httpsPort,
            $pathInfo,
            $uri->getQuery()
        );
    }

    /**
     * Gets a string of the context data, if it is there and it is not empty.
     *
     * @param array<array-key, mixed> $context The context data.
     * @param string $key The key of the value.
     * @return string|null The value, or `null` if there is none.
     * @throws InvalidArgumentException If the value is not a string.
     */
    private static function contextString(array $context, string $key): ?string
    {
        $value = $context[$key] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException([
                'The key {key} of the request context must be a string; {type} given.',
                'key' => $key,
                'type' => get_debug_type($value),
            ]);
        }

        return $value;
    }

    /**
     * Gets the port of the context data, if it is there.
     *
     * @param array<array-key, mixed> $context The context data.
     * @return int|null The port, or `null` if there is none.
     * @throws InvalidArgumentException If the value is not a valid port.
     */
    private static function contextPort(array $context): ?int
    {
        $value = $context['URL_PORT'] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        $port = match (true) {
            is_int($value) => $value,
            is_string($value) && ctype_digit($value) => (int) $value,
            default => 0,
        };
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException([
                'The key URL_PORT of the request context must be a port number from 1 to 65535; {type} given.',
                'type' => get_debug_type($value),
            ]);
        }

        return $port;
    }
}
