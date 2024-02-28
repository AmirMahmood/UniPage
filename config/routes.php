<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UniPage\utils\UserStatusEnum;

return function (App $app) {
    (require __DIR__ . '/routes_admin.php')($app);

    $app->get('/', function (Request $request, Response $response, $args) {
        $view = $this->get('view');
        $em = $this->get('orm');

        return $view->render($response, 'landing.html', ['page_id' => ""]);
    });

    $app->get('/people', function (Request $request, Response $response, $args) {
        $view = $this->get('view');
        $em = $this->get('orm');

        $query = $em->createQuery(
            'SELECT p
            FROM UniPage\Domain\User p
            WHERE p.status = :status
            ORDER BY p.date ASC'
        )->setParameter('status', UserStatusEnum::ACTIVE->value);
        $res = $query->getResult();

        return $view->render($response, 'people.html', ['page_id' => "people", 'users' => $res]);
    });

    $app->get('/links', function (Request $request, Response $response, $args) {
        $view = $this->get('view');
        $em = $this->get('orm');
        
        return $view->render($response, 'links.html', ['page_id' => "links"]);
    });
};
