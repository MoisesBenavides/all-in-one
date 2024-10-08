<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollector;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Louder\YamlFileLoader;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Config\FileLocator;

// Configura el contexto de solicitud HTTP
$request=Request::createFromGlobals();
$context=new RequestContext();
$context->fromRequest($request);

// Carga las rutas desde el archivo routes.yaml
$fileLocator=new FileLocator([__DIR__ . '/../config/routes/']);
$loader=new YamlFileLoader($fileLocator);
$routes= $loader->load('routes.yaml');

$matcher = new UrlMatcher($routes, $context);

try{
    // Intenta hacer coincidir la URL con una ruta definida
    $parameters= $matcher->match($request->getPathInfo());

    // Extraer el controlador y la acción de la ruta
    list($controller, $method) = explode('::', $parameters['_controller']);

    // Llamar a la función del controlador correspondiente
    $controllerInstance = new $controller();
    $response call_user_func([$controllerInstance, $method], $request);
} catch (ResourceNotFoundException $e){
    $response = new Response('Página no encontrada', 404);
} catch (Exception $e){
    $response= new Response('Error del servidor', 500);
}

// Enviar la respuesta HTTP al cliente
$response->send();