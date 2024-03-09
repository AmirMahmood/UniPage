<?php

use Slim\App;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Doctrine\ORM\EntityManager;
use UniPage\utils\UserStatusEnum;

return function (App $app) {
    (require __DIR__ . '/dbrun.php')($app);
    (require __DIR__ . '/routes_admin.php')($app);

    $app->get('/', function (Request $request, Response $response, $args) {
        $view = $this->get(Twig::class);
        $em = $this->get(EntityManager::class);

        return $view->render($response, 'landing.html', ['page_id' => ""]);
    });

    $app->get('/people', function (Request $request, Response $response, $args) {
        $view = $this->get(Twig::class);
        $em = $this->get(EntityManager::class);

        $query = $em->createQuery(
            'SELECT p
            FROM UniPage\Domain\User p
            WHERE p.status <> :status
            ORDER BY p.start_date ASC'
        )->setParameter('status', UserStatusEnum::BLOCKED->value);
        $res = $query->getResult();

        return $view->render($response, 'people.html', ['page_id' => "people", 'users' => $res]);
    });

    $app->get('/alumni', function (Request $request, Response $response, $args) {
        $view = $this->get(Twig::class);
        $em = $this->get(EntityManager::class);

        return $view->render($response, 'alumni.html', ['page_id' => "alumni"]);
    });

    $app->get('/publications', function (Request $request, Response $response, $args) {
        $view = $this->get(Twig::class);
        $em = $this->get(EntityManager::class);

        return $view->render($response, 'publications.html', ['page_id' => "publications"]);
    });

    $app->get('/courses', function (Request $request, Response $response, $args) {
        $view = $this->get(Twig::class);
        $em = $this->get(EntityManager::class);

        return $view->render($response, 'courses.html', ['page_id' => "courses"]);
    });

    $app->get('/links', function (Request $request, Response $response, $args) {
        $view = $this->get(Twig::class);
        $em = $this->get(EntityManager::class);

        return $view->render($response, 'links.html', ['page_id' => "links"]);
    });
};
