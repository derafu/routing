# Derafu: Routing - Elegant PHP Router with Plugin Architecture

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://opensource.org/licenses/MIT)

A lightweight, extensible PHP routing library that combines simplicity with power through its unique parser-based architecture.

## Features

- 🔌 Plugin architecture with swappable parsers.
- 🎯 Multiple routing strategies (static, dynamic, filesystem).
- 🧩 Easy to extend with custom parsers.
- 📁 Built-in filesystem routing for static sites.
- 🔄 Support for different content types (.md, .twig, etc.).
- 🛠️ Clean separation of concerns.
- 🪶 Lightweight with zero dependencies.
- ⚡ Fast pattern matching.
- 🧪 Comprehensive test coverage.

## Why Derafu\Routing?

Unlike traditional monolithic routers, Derafu\Routing uses a unique parser-based architecture that offers several advantages:

- **Modularity**: Each routing strategy is encapsulated in its own parser.
- **Flexibility**: Easy to add new routing patterns without modifying existing code.
- **Clarity**: Clear separation between route matching and request handling.
- **Extensibility**: Add custom parsers for specific routing needs.
- **Predictability**: Each parser has a single responsibility.
- **Performance**: Only load the parsers you need.

## Installation

Install via Composer:

```bash
composer require derafu/routing
```

## Basic Usage

```php
use Derafu\Routing\Router;
use Derafu\Routing\Dispatcher;
use Derafu\Routing\Parser\StaticParser;
use Derafu\Routing\Parser\FileSystemParser;

// Create and configure router.
$router = new Router();
$router->addParser(new StaticParser());
$router->addParser(new FileSystemParser([__DIR__ . '/pages']));

// Add routes.
$router->addRoute('/', 'HomeController@index');
$router->addDirectory(__DIR__ . '/pages');

// Create and configure dispatcher.
$dispatcher = new Dispatcher([
    'md' => fn ($file, $params) => renderMarkdown($file),
    'twig' => fn($file, $params) => renderTwig($file, $params),
]);

// Handle request.
try {
    $route = $router->match();
    echo $dispatcher->dispatch($route);
} catch (RouterException $e) {
    // Handle error.
}
```

## Available Parsers

### StaticParser

Handles exact route matches:

```php
$router->addRoute('/about', 'PagesController@about');
```

### DynamicParser

Supports parameters and patterns:

```php
$router->addRoute('/users/{id:\d+}', 'UserController@show');
$router->addRoute('/blog/{year}/{slug}', 'BlogController@post');
```

### FileSystemParser

Maps URLs to files in directories:

```php
$router->addDirectory(__DIR__ . '/pages');
// Examples:
// /about maps to /pages/about.md
// /contact maps to /pages/contact.html.twig
```

## Creating Custom Parsers

Implement your own routing strategy by creating a parser:

```php
class CustomParser implements ParserInterface
{
    public function parse(string $uri, array $routes): ?RouteMatch
    {
        // Your custom routing logic.
    }

    public function supports(Route $route): bool
    {
        // Define what routes this parser can handle.
    }
}

$router->addParser(new CustomParser());
```

## File-based Routing Example

Perfect for static sites:

```
pages/
├── about.md
├── contact.html.twig
└── blog/
    ├── post-1.md
    └── post-2.md
```

URLs are automatically mapped to files:

- `/about` → `pages/about.md`
- `/contact` → `pages/contact.html.twig`
- `/blog/post-1` → `pages/blog/post-1.md`

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This library is licensed under the MIT License. See the `LICENSE` file for more details.
