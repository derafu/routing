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
     * @param ServerRequestInterface $request A PSR-7 server request.
     * @param string|array $context The context attribute name or an array of
     * context data.
     * @return static A new request context instance.
     */
    public static function fromRequest(
        ServerRequestInterface $request,
        string|array $context = 'derafu.context'
    ): static {
        // Get data from context attribute and other sources of the request.
        if (is_string($context)) {
            $context = (array) $request->getAttribute($context, []);
        }
        $server = $request->getServerParams();
        $uri = $request->getUri();

        // Determine scheme.
        $scheme = ($context['URL_SCHEME'] ?? false) ?: $uri->getScheme() ?: 'http';

        // Determine host.
        $host = ($context['URL_HOST'] ?? false) ?: $uri->getHost() ?: $server['SERVER_NAME'] ?: 'localhost';

        // Set default ports.
        $httpPort = 80;
        $httpsPort = 443;

        // Adjust ports.
        $port = (int) (($context['URL_PORT'] ?? false) ?: $uri->getPort() ?: $server['SERVER_PORT']);
        if ('http' === $scheme) {
            $httpPort = $port;
        } elseif ('https' === $scheme) {
            $httpsPort = $port;
        }

        // Determine base URL.
        $baseUrl = (string) ($context['APP_BASE_PATH'] ?? '');

        // Determine path info.
        if (!empty($context['URL_URI'])) {
            $pathInfo = $context['URL_URI'];
        } else {
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
}
