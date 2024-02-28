<?php

use Slim\App;
use Slim\Views\TwigMiddleware;

return function (App $app) {
    // Parse json, form data and xml
    $app->addBodyParsingMiddleware();

    // Add the Slim built-in routing middleware
    $app->addRoutingMiddleware();

    // Handle exceptions
    $app->addErrorMiddleware(true, true, true);

    // Add Twig-View Middleware
    $app->add(TwigMiddleware::createFromContainer($app));

    $app->add('csrf');

    // Remove TrailingSlash Middleware
    $app->add(function ($request, $handler) {
        $response = $handler->handle($request);

        $uri = $request->getUri();
        $path = $uri->getPath();

        //Add/remove slash
        if (strlen($path) > 1) {
            $path = rtrim($path, '/');
        } elseif ($path === '') {
            $path = '/';
        }

        //redirect
        if ($uri->getPath() !== $path) {
            return $response->withStatus(302)->withHeader('Location', (string)$uri->withPath($path));
        }

        return $response;
    });
};
