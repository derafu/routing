# Derafu Routing - Guía de Uso Completa

Derafu Routing es una biblioteca de enrutamiento PHP flexible que utiliza una arquitectura basada en parsers. En lugar de tener un router monolítico, separa la lógica de enrutamiento en parsers especializados, cada uno manejando diferentes tipos de rutas.

[TOC]

## Instalación

```bash
composer require derafu/routing
```

## Conceptos Básicos

### Arquitectura de Parsers

El sistema de enrutamiento está construido alrededor de parsers especializados:

1. **StaticParser**: Maneja coincidencias exactas de rutas.
2. **DynamicParser**: Procesa rutas con parámetros.
3. **FileSystemParser**: Mapea URLs a archivos físicos.

Cada parser implementa la interfaz `ParserInterface`:

```php
interface ParserInterface {
    public function parse(string $uri, array $routes): ?RouteMatchInterface;
    public function supports(RouteInterface $route): bool;
}
```

## Tipos de Rutas

### Rutas Estáticas

La forma más simple de enrutamiento, manejada por `StaticParser`:

```php
$router = new Router([new StaticParser()]);
$router->addRoute('/about', 'PagesController::action');
$router->addRoute('/contacto', 'ContactoController::show');
```

### Rutas Dinámicas

Manejadas por `DynamicParser`, soportando varios tipos de parámetros:

```php
$router->addParser(new DynamicParser());

// Parámetro básico.
$router->addRoute('/usuarios/{id}', 'UsuarioController::show');

// Validación con expresiones regulares.
$router->addRoute('/usuarios/{id:\d+}', 'UsuarioController::show');

// Parámetros opcionales.
$router->addRoute('/blog/{año?}', 'BlogController::index');

// Múltiples parámetros.
$router->addRoute('/blog/{año}/{mes?}', 'BlogController::archivo');

// Patrones complejos.
$router->addRoute('/usuarios/{username:[a-z0-9_-]+}', 'UsuarioController::perfil');
```

### Rutas del Sistema de Archivos

El `FileSystemParser` mapea URLs a archivos reales:

```php
$parser = new FileSystemParser(
    directories: [__DIR__ . '/paginas'],
    extensions: ['.html.twig', '.md']
);
$router->addParser($parser);
```

Estructura de directorios:
```
paginas/
├── about.md           # Coincide con /about
├── contacto.twig      # Coincide con /contacto
└── blog/
    ├── post-1.md     # Coincide con /blog/post-1
    └── post-2.md     # Coincide con /blog/post-2
```

## Usando el Router

### Configuración Básica

```php
use Derafu\Routing\Router;
use Derafu\Routing\Parser\StaticParser;
use Derafu\Routing\Parser\DynamicParser;

$router = new Router([
    new StaticParser(),
    new DynamicParser(),
]);
```

### Agregando Rutas

```php
// Manejador tipo string (Controlador@acción).
$router->addRoute('/usuarios', 'UsuarioController::index');

// Manejador tipo Closure.
$router->addRoute('/api/datos', function($params) {
    return ['datos' => 'valor'];
});

// Manejador tipo array.
$router->addRoute('/blog', [
    'controller' => 'BlogController',
    'action' => 'listar'
]);

// Rutas con nombre y parámetros.
$router->addRoute(
    route: '/usuarios/{id}',
    handler: 'UsuarioController::show',
    name: 'usuario.show',
    parameters: ['activo' => true]
);
```

### Coincidencia de Rutas

```php
try {
    $match = $router->match('/usuarios/123');
    // $match->getHandler(): Retorna el manejador de la ruta.
    // $match->getParameters(): Retorna los parámetros de la ruta.
    // $match->getName(): Retorna el nombre de la ruta si está definido.
} catch (RouteNotFoundException $e) {
    // Manejar 404.
}
```

## El Dispatcher

El dispatcher maneja la ejecución de las rutas coincidentes:

```php
$dispatcher = new Dispatcher([
    'md' => function($file, $params) {
        // Renderizar archivo markdown.
        return parseMarkdown(file_get_contents($file));
    },
    'twig' => function($file, $params) {
        // Renderizar plantilla Twig.
        return $twig->render($file, $params);
    }
]);

$resultado = $dispatcher->dispatch($match);
```

**Nota**: Este es un *dispatcher* muy básico, se debe implementar uno propio.

## Uso Avanzado

### Ejemplo de Parser Personalizado

```php
class RegexParser implements ParserInterface
{
    public function parse(string $uri, array $routes): ?RouteMatchInterface
    {
        foreach ($routes as $route) {
            if (!$this->supports($route)) {
                continue;
            }

            // Lógica personalizada de coincidencia regex
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
        // Define qué rutas maneja este parser
        return str_starts_with($route->getPattern(), '#');
    }
}
```

## Mejores Prácticas

1. **Orden de los Parsers**: Agregar parsers en orden de especificidad.
   - StaticParser primero (más rápido, más específico).
   - DynamicParser después.
   - FileSystemParser al final (más flexible, pero más lento).

2. **Organización de Rutas**: Agrupar rutas relacionadas.
   ```php
   // Gestión de usuarios
   $router->addRoute('/usuarios', 'UsuarioController::index');
   $router->addRoute('/usuarios/{id}', 'UsuarioController::show');

   // Sistema de blog
   $router->addRoute('/blog', 'BlogController::index');
   $router->addRoute('/blog/{slug}', 'BlogController::show');
   ```

3. **Validación de Parámetros**: Usar restricciones regex para mejor seguridad.
   ```php
   // Asegurar que ID sea numérico.
   $router->addRoute('/usuarios/{id:\d+}', 'UsuarioController::show');

   // Validar formato de nombre de usuario.
   $router->addRoute('/usuarios/{username:[a-z0-9_-]+}', 'UsuarioController::perfil');
   ```

4. **Manejo de Errores**: Siempre envolver coincidencias en try-catch.
   ```php
   try {
       $match = $router->match($uri);
       $resultado = $dispatcher->dispatch($match);
   } catch (RouteNotFoundException $e) {
       // Manejar 404.
   } catch (DispatcherException $e) {
       // Manejar errores del dispatcher.
   }
   ```
