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
    'The key {key} of route "{route}" must be a string; {type} given.' =>
        'La clave {key} de la ruta "{route}" debe ser un string; se recibió {type}.',
    'The key handler of route "{route}" must be a string, an array or a Closure; {type} given.' =>
        'La clave handler de la ruta "{route}" debe ser un string, un array o un Closure; se recibió {type}.',
    'The key defaults of route "{route}" must be an array with names as keys.' =>
        'La clave defaults de la ruta "{route}" debe ser un array con nombres como claves.',
    'The key {key} of route "{route}" must be a list of strings.' =>
        'La clave {key} de la ruta "{route}" debe ser una lista de strings.',
    'Handler is required in route "{route}".' =>
        'El manejador (handler) es obligatorio en la ruta "{route}".',
    'The path of the request is not valid.' =>
        'La ruta de la solicitud no es válida.',
    'No route found for "{uri}".' =>
        'No se encontró ninguna ruta para "{uri}".',
    'Method "{method}" is not allowed for "{uri}". Allowed methods: {allowed}.' =>
        'El método "{method}" no está permitido para "{uri}". Métodos permitidos: {allowed}.',

    // Parsers.
    'Invalid directory: {directory}' =>
        'Directorio inválido: {directory}',

    'Parameter "{parameter}" of route "{route}" does not satisfy its requirement or cannot form a safe path.' =>
        'El parámetro "{parameter}" de la ruta "{route}" no cumple su restricción o no puede formar un camino seguro.',

    // URL generator.
    'The server variable {variable} must be a string; {type} given.' =>
        'La variable del servidor {variable} debe ser un string; se recibió {type}.',
    'The pattern of the route "{route}" could not be processed: {error}' =>
        'No se pudo procesar el patrón de la ruta "{route}": {error}',
    'Unable to generate a URL for the route "{pattern}": {error}' =>
        'No se pudo generar la URL de la ruta "{pattern}": {error}',
    'Parameter "{parameter}" of route "{route}" must be a string, a number, a boolean or an object that can be converted to a string; {type} given.' =>
        'El parámetro "{parameter}" de la ruta "{route}" debe ser un string, un número, un booleano o un objeto que se pueda convertir a string; se recibió {type}.',
    'The context attribute {attribute} must be an array; {type} given.' =>
        'El atributo de contexto {attribute} debe ser un arreglo; se recibió {type}.',
    'The key {key} of the request context must be a string; {type} given.' =>
        'La clave {key} del contexto de la petición debe ser un string; se recibió {type}.',
    'The key URL_PORT of the request context must be a port number from 1 to 65535; {type} given.' =>
        'La clave URL_PORT del contexto de la petición debe ser un número de puerto de 1 a 65535; se recibió {type}.',
    'Parameter "{parameter}" is required for route "{route}" but was not provided.' =>
        'El parámetro "{parameter}" es obligatorio para la ruta "{route}" pero no fue entregado.',
    'Unable to generate a URL for the named route "{name}" as such route does not exist.' =>
        'No se puede generar una URL para la ruta con nombre "{name}" porque esa ruta no existe.',
    'A request context is required to generate a URL with reference type "{referenceType}".' =>
        'Se requiere un contexto de solicitud para generar una URL con el tipo de referencia "{referenceType}".',
];
