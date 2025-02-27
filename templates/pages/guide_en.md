# Derafu Routing - Comprehensive Usage Guide

Derafu Routing is a flexible PHP routing library that uses a parser-based architecture. Instead of having a monolithic router, it separates routing logic into specialized parsers, each handling different types of routes.

[TOC]

## Installation

```bash
composer require derafu/routing
```

## Core Concepts

### Parser Architecture

The routing system is built around specialized parsers:

1. **StaticParser**: Handles exact route matches.
2. **DynamicParser**: Processes routes with parameters.
3. **FileSystemParser**: Maps URLs to physical files.

Each parser implements the `ParserInterface`:

```php
interface ParserInterface {
    public function parse(string $uri, array $routes): ?RouteMatchInterface;
    public function supports(RouteInterface $route): bool;
}
```

## Route Types

### Static Routes

The simplest form of routing, handled by `StaticParser`:

```php
$router = new Router([new StaticParser()]);
$router->addRoute('/about', 'PagesController@action');
$router->addRoute('/contact', 'ContactController@show');
```

### Dynamic Routes

Handled by `DynamicParser`, supporting various parameter types:

```php
$router->addParser(new DynamicParser());

// Basic parameter.
$router->addRoute('/users/{id}', 'UserController@show');

// Regex validation.
$router->addRoute('/users/{id:\d+}', 'UserController@show');

// Optional parameters.
$router->addRoute('/blog/{year?}', 'BlogController@index');

// Multiple parameters.
$router->addRoute('/blog/{year}/{month?}', 'BlogController@archive');

// Complex patterns.
$router->addRoute('/users/{username:[a-z0-9_-]+}', 'UserController@profile');
```

### File System Routes

The `FileSystemParser` maps URLs to actual files:

```php
$parser = new FileSystemParser(
    directories: [__DIR__ . '/pages'],
    extensions: ['.html.twig', '.md']
);
$router->addParser($parser);
```

Directory structure:
```
pages/
├── about.md           # Matches /about
├── contact.twig       # Matches /contact
└── blog/
    ├── post-1.md     # Matches /blog/post-1
    └── post-2.md     # Matches /blog/post-2
```

## Using the Router

### Basic Setup

```php
use Derafu\Routing\Router;
use Derafu\Routing\Parser\StaticParser;
use Derafu\Routing\Parser\DynamicParser;

$router = new Router([
    new StaticParser(),
    new DynamicParser(),
]);
```

### Adding Routes

```php
// String handler (Controller@action).
$router->addRoute('/users', 'UserController@index');

// Closure handler.
$router->addRoute('/api/data', function($params) {
    return ['data' => 'value'];
});

// Array handler.
$router->addRoute('/blog', [
    'controller' => 'BlogController',
    'action' => 'list'
]);

// Named routes with parameters.
$router->addRoute(
    route: '/users/{id}',
    handler: 'UserController@show',
    name: 'user.show',
    parameters: ['active' => true]
);
```

### Matching Routes

```php
try {
    $match = $router->match('/users/123');
    // $match->getHandler(): Returns the route handler.
    // $match->getParameters(): Returns route parameters.
    // $match->getName(): Returns route name if set.
} catch (RouteNotFoundException $e) {
    // Handle 404.
}
```

## The Dispatcher

The dispatcher handles the execution of matched routes:

```php
$dispatcher = new Dispatcher([
    'md' => function($file, $params) {
        // Render markdown file.
        return parseMarkdown(file_get_contents($file));
    },
    'twig' => function($file, $params) {
        // Render Twig template.
        return $twig->render($file, $params);
    }
]);

$result = $dispatcher->dispatch($match);
```

**Note**: This is a very basic dispatcher, you should implement one by yourself.

## Advanced Usage

### Custom Parser Example

```php
class RegexParser implements ParserInterface
{
    public function parse(string $uri, array $routes): ?RouteMatchInterface
    {
        foreach ($routes as $route) {
            if (!$this->supports($route)) {
                continue;
            }

            // Custom regex matching logic.
            if (preg_match($route->getPattern(), $uri, $matches)) {
                return new RouteMatch(
                    $route->getHandler(),
                    $matches
                );
            }
        }
        return null;
    }

    public function supports(RouteInterface $route): bool
    {
        // Define what routes this parser handles.
        return str_starts_with($route->getPattern(), '#');
    }
}
```

## Best Practices

1. **Parser Order**: Add parsers in order of specificity.
   - StaticParser first (fastest, most specific).
   - DynamicParser next.
   - FileSystemParser last (most flexible, but slower).

2. **Route Organization**: Group related routes
   ```php
   // User management.
   $router->addRoute('/users', 'UserController@index');
   $router->addRoute('/users/{id}', 'UserController@show');

   // Blog system.
   $router->addRoute('/blog', 'BlogController@index');
   $router->addRoute('/blog/{slug}', 'BlogController@show');
   ```

3. **Parameter Validation**: Use regex constraints for better security.
   ```php
   // Ensure ID is numeric.
   $router->addRoute('/users/{id:\d+}', 'UserController@show');

   // Validate username format.
   $router->addRoute('/users/{username:[a-z0-9_-]+}', 'UserController@profile');
   ```

4. **Error Handling**: Always wrap matches in try-catch.
   ```php
   try {
       $match = $router->match($uri);
       $result = $dispatcher->dispatch($match);
   } catch (RouteNotFoundException $e) {
       // Handle 404.
   } catch (DispatcherException $e) {
       // Handle dispatcher errors.
   }
   ```
