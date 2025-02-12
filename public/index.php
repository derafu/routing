<?php

declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

use Derafu\Routing\Dispatcher;
use Derafu\Routing\Exception\RouterException;
use Derafu\Routing\Parser\FileSystemParser;
use Derafu\Routing\Parser\StaticParser;
use Derafu\Routing\Router;
use Derafu\Twig\Service\TwigService;

// Create Router.
$router = new Router();
$router->addParser(new StaticParser());
$router->addParser(new FileSystemParser(
    [__DIR__ . '/pages'],
    ['.html.twig', '.md']
));

// Add route manually for index.
$router->addRoute('/', __DIR__ . '/../README.md');

// Create Dispatcher.
$dispatcher = new Dispatcher();

// Add renderers to dispatcher.
$twig = new TwigService([
    'paths' => [
        __DIR__ . '/__twig',
        __DIR__ . '/pages',
    ],
]);
$dispatcher->addRenderer(
    'twig',
    fn ($file, $params) => $twig->render($file, $params)
);
$dispatcher->addRenderer(
    'md',
    function ($file, $params) use ($twig) {
        $params['content'] = file_get_contents($file);
        return $twig->render('markdown', $params);
    }
);

// Resolve and dispatch the request.
try {
    $route = $router->match();
    echo $dispatcher->dispatch($route);
} catch (RouterException $e) {
    http_response_code(404);
    $data = ['message' => $e->getMessage()];
    echo $twig->render('error404', $data);
}
