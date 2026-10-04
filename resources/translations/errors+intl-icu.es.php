<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

return [
    // Dispatcher.
    'Unable to dispatch handler of type: {type}' =>
        'No se puede despachar un manejador de tipo: {type}',
    'Unsupported file type: {extension}' =>
        'Tipo de archivo no soportado: {extension}',
    'Controller not found: {controller}' =>
        'Controlador no encontrado: {controller}',
    'Action not found: {controller}::{action}' =>
        'Acción no encontrada: {controller}::{action}',

    // Router.
    'Invalid route configuration.' =>
        'Configuración de ruta inválida.',
    'Name is required in route "{route}".' =>
        'El nombre es obligatorio en la ruta "{route}".',
    'Path is required in route "{route}".' =>
        'El path es obligatorio en la ruta "{route}".',
    'Handler is required in route "{route}".' =>
        'El manejador (handler) es obligatorio en la ruta "{route}".',
    'No route found for "{uri}".' =>
        'No se encontró ninguna ruta para "{uri}".',
    'Method "{method}" is not allowed for "{uri}". Allowed methods: {allowed}.' =>
        'El método "{method}" no está permitido para "{uri}". Métodos permitidos: {allowed}.',

    // Parsers.
    'Invalid directory: {directory}' =>
        'Directorio inválido: {directory}',

    // URL generator.
    'Parameter "{parameter}" is required for route "{route}" but was not provided.' =>
        'El parámetro "{parameter}" es obligatorio para la ruta "{route}" pero no fue entregado.',
    'Unable to generate a URL for the named route "{name}" as such route does not exist.' =>
        'No se puede generar una URL para la ruta con nombre "{name}" porque esa ruta no existe.',
    'Relative path generation is not implemented yet.' =>
        'La generación de rutas relativas aún no está implementada.',
    'Invalid reference type: "{referenceType}".' =>
        'Tipo de referencia inválido: "{referenceType}".',
];
