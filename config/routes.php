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
use UniPage\utils\HelperFuncs as HF;

return function (App $app) {
    (require __DIR__ . '/dbrun.php')($app);

    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/publications', 'UniPage\Controller\PublicationController:site_publications_page');
        $group->get('/links', 'UniPage\Controller\LinkController:site_links_page');
        $group->get('/people', 'UniPage\Controller\UserController:site_people_page');
        $group->get('/alumni', 'UniPage\Controller\UserController:site_alumni_page');
    });

    $app->group('/admin', function (RouteCollectorProxy $group) {
        # publications
        $group->get('/publications', 'UniPage\Controller\PublicationController:admin_publications_list_page');
        $group->get('/publication-create', 'UniPage\Controller\PublicationController:admin_publication_create_page');
        $group->get('/publication-edit/{id:[0-9]+}', 'UniPage\Controller\PublicationController:admin_publication_edit_page');
        # links
        $group->get('/links', 'UniPage\Controller\LinkController:admin_links_list_page');
        $group->get('/link-create', 'UniPage\Controller\LinkController:admin_link_create_page');
        $group->get('/link-edit/{id:[0-9]+}', 'UniPage\Controller\LinkController:admin_link_edit_page');
        # users
        $group->get('/users', 'UniPage\Controller\UserController:admin_users_list_page');
        $group->get('/user-create', 'UniPage\Controller\UserController:admin_user_create_page');
        $group->get('/user-edit/{id:[0-9]+}', 'UniPage\Controller\UserController:admin_user_edit_page');
    })->add(Guard::class)->add(new LoginMiddleware($app->getContainer()));

    $app->group('/api', function (RouteCollectorProxy $group) {
        # publications
        $group->get('/get-publications', 'UniPage\Controller\PublicationController:get_publications_list');
        $group->get('/get-publication/{id:[0-9]+}', 'UniPage\Controller\PublicationController:get_publication');
        $group->post('/delete-publication/{id:[0-9]+}', 'UniPage\Controller\PublicationController:delete_publication');
        $group->post('/create-publication', 'UniPage\Controller\PublicationController:create_publication');
        $group->post('/update-publication', 'UniPage\Controller\PublicationController:update_publication');
        # links
        $group->get('/get-links', 'UniPage\Controller\LinkController:get_links_list');
        $group->get('/get-link/{id:[0-9]+}', 'UniPage\Controller\LinkController:get_link');
        $group->post('/delete-link/{id:[0-9]+}', 'UniPage\Controller\LinkController:delete_link');
        $group->post('/create-link', 'UniPage\Controller\LinkController:create_link');
        $group->post('/update-link', 'UniPage\Controller\LinkController:update_link');
        # users
        $group->get('/get-users', 'UniPage\Controller\UserController:get_users_list');
        $group->get('/get-user/{id:[0-9]+}', 'UniPage\Controller\UserController:get_user');
        $group->post('/delete-user/{id:[0-9]+}', 'UniPage\Controller\UserController:delete_user');
        $group->post('/create-user', 'UniPage\Controller\UserController:create_user');
        $group->post('/update-user', 'UniPage\Controller\UserController:update_user');
        $group->post('/delete-avatar/{id:[0-9]+}', 'UniPage\Controller\UserController:delete_avatar');
        $group->post('/change-password', 'UniPage\Controller\UserController:change_password');
    })->add(Guard::class)->add(new LoginMiddleware($app->getContainer()));

    $app->get('/', function (Request $request, Response $response, $args) {
        $view = $this->get(Twig::class);
        $em = $this->get(EntityManager::class);

        return $view->render($response, 'landing.html', ['page_id' => ""]);
    });

    $app->get('/courses', function (Request $request, Response $response, $args) {
        $view = $this->get(Twig::class);
        $em = $this->get(EntityManager::class);

        return $view->render($response, 'courses.html', ['page_id' => "courses"]);
    });

    $app->get('/admin/login', function (Request $request, Response $response, $args) {
        $view = $this->get(Twig::class);
        return $view->render(
            $response,
            'admin/login.html',
            ['csrf' => HF::getCSRF($this), 'site_info' => $this->get('settings')['site_info']]
        );
    })->add(Guard::class);

    $app->post('/admin/login', function (Request $request, Response $response, $args) {
        $body = $request->getParsedBody();
        $user = $body['username'];
        $pass = $body['password'];

        $em = $this->get(EntityManager::class);
        $user = $em->getRepository('UniPage\Domain\User')
            ->findOneBy(array('username' => $user, 'is_admin' => true, 'deleted' => false));

        if ($user != null && $user->status != UserStatusEnum::BLOCKED->value && $user->password == hash('sha256', $pass)) {
            // Save session and continue process
            $_SESSION['user'] = $user->id;

            $user->update_login_time();
            $em->flush();

            return $response->withHeader('Location', '/admin/users')->withStatus(302);
        }

        // Any other case, no valid session, send error
        unset($_SESSION["user"]);

        $view = $this->get(Twig::class);
        return $view->render(
            $response,
            'admin/login.html',
            ['csrf' => HF::getCSRF($this), 'site_info' => $this->get('settings')['site_info'], 'error' => "Wrong username or password"]
        );
    })->add(Guard::class);

    $app->get('/admin', function (Request $request, Response $response, $args) {
        return $response->withHeader('Location', '/admin/users')->withStatus(302);
    })->add(Guard::class);

    $app->get('/admin/delete-cache', function (Request $request, Response $response, $args) {
        function removeDir(string $dir): void
        {
            $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator(
                $it,
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }
        }

        removeDir(__DIR__ . '/../var/twig');
        removeDir(__DIR__ . '/../var/doctrine');

        $response->getBody()->write("Cache clear complete");
        return $response;
    });
};
