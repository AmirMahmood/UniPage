<?php

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\Twig;
use Slim\Csrf\Guard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Doctrine\ORM\EntityManager;
use UniPage\utils\UserStatusEnum;
use UniPage\Middleware\LoginMiddleware;

return function (App $app) {
    (require __DIR__ . '/dbrun.php')($app);
    (require __DIR__ . '/routes_admin.php')($app);

    $app->group('/admin', function (RouteCollectorProxy $group) {
        $group->get('/links', 'UniPage\Controller\LinkController:admin_links_list_page');
        $group->get('/link-create', 'UniPage\Controller\LinkController:admin_link_create_page');
        $group->get('/link-edit/{id:[0-9]+}', 'UniPage\Controller\LinkController:admin_link_edit_page');
    })->add(Guard::class)->add(new LoginMiddleware($app->getContainer()));

    $app->group('/api', function (RouteCollectorProxy $group) {
        $group->get('/get-links', 'UniPage\Controller\LinkController:get_links_list');
        $group->get('/get-link/{id:[0-9]+}', 'UniPage\Controller\LinkController:get_link');
        $group->post('/delete-link/{id:[0-9]+}', 'UniPage\Controller\LinkController:delete_link');
        $group->post('/create-link', 'UniPage\Controller\LinkController:create_link');
        $group->post('/update-link', 'UniPage\Controller\LinkController:update_link');
    })->add(Guard::class)->add(new LoginMiddleware($app->getContainer()));

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

        $query = $em->createQuery(
            'SELECT p
            FROM UniPage\Domain\User p
            WHERE p.status <> :status
            ORDER BY p.start_date ASC'
        )->setParameter('status', UserStatusEnum::BLOCKED->value);
        $res = $query->getResult();

        return $view->render($response, 'alumni.html', ['page_id' => "alumni", 'users' => $res]);
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

        $query = $em->createQuery(
            'SELECT p
            FROM UniPage\Domain\Link p
            ORDER BY p.title ASC'
        );
        $res = $query->getResult();

        return $view->render($response, 'links.html', ['page_id' => "links", 'links' => $res]);
    });
};
