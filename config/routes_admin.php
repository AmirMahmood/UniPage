<?php

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard;
use UniPage\Domain\User;
use UniPage\utils\ValidationException;
use UniPage\Middleware\LoginMiddleware;
use UniPage\utils\UserStatusEnum;
use UniPage\utils\HelperFuncs as HF;

function pagesRoute(App $app)
{
    $app->group('/admin', function (RouteCollectorProxy $group) {
        $group->get('/login', function (Request $request, Response $response, $args) {
            $view = $this->get(Twig::class);
            return $view->render(
                $response,
                'admin/login.html',
                ['csrf' => HF::getCSRF($this), 'site_info' => $this->get('settings')['site_info']]
            );
        });

        $group->post('/login', function (Request $request, Response $response, $args) {
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
        });

        $group->get('/users', function (Request $request, Response $response, $args) {
            $view = $this->get(Twig::class);
            return $view->render(
                $response,
                'admin/users.html',
                ['page_id' => "users", 'csrf' => HF::getCSRF($this), 'site_info' => $this->get('settings')['site_info']]
            );
        })->add(new LoginMiddleware($this));

        $group->get('/user-create', function (Request $request, Response $response, $args) {
            $view = $this->get(Twig::class);
            return $view->render(
                $response,
                'admin/user-create.html',
                ['page_id' => "users", 'csrf' => HF::getCSRF($this), 'site_info' => $this->get('settings')['site_info']]
            );
        })->add(new LoginMiddleware($this));

        $group->get('/user-edit/{id:[0-9]+}', function (Request $request, Response $response, $args) {
            $view = $this->get(Twig::class);
            $id = $args['id'];
            return $view->render(
                $response,
                'admin/user-edit.html',
                ['page_id' => "users", 'id' => $id, 'csrf' => HF::getCSRF($this), 'site_info' => $this->get('settings')['site_info']]
            );
        })->add(new LoginMiddleware($this));
    })->add(Guard::class);
};

function apiRoute(App $app)
{
    $app->group('/api', function (RouteCollectorProxy $group) {
        $group->get('/get-users', function (Request $request, Response $response, $args) {
            $em = $this->get(EntityManager::class);

            $query = $em->createQuery(
                'SELECT p
            FROM UniPage\Domain\User p
            WHERE p.deleted <> 1
            ORDER BY p.id ASC'
            );
            $content = $query->getResult();

            return HF::UniJsonResponse($response, statusCode: 200, content: $content);
        });

        $group->get('/get-user/{id:[0-9]+}', function (Request $request, Response $response, $args) {
            $em = $this->get(EntityManager::class);
            $id = $args['id'];

            $query = $em->createQuery(
                'SELECT p
            FROM UniPage\Domain\User p
            WHERE p.id = :id AND p.deleted <> 1
            ORDER BY p.id ASC'
            )->setParameter('id', $id);
            $content = $query->getResult();

            return HF::UniJsonResponse($response, statusCode: 200, content: $content);
        });

        $group->post('/create-user', function (Request $request, Response $response, $args) {
            $em = $this->get(EntityManager::class);

            $params = (array)$request->getParsedBody();
            try {
                User::validate_new($params);
                $newUser = new User(...$params);
                $em->persist($newUser);
                $em->flush();
            } catch (ValidationException | UniqueConstraintViolationException $ex) {
                return HF::UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
            }

            try {
                $uploadedFiles = $request->getUploadedFiles();
                if (array_key_exists('avatar', $uploadedFiles)) {
                    $uploadedFile = $uploadedFiles['avatar'];
                    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                        $dir_path = $this->get('settings')['media_dir'] . DIRECTORY_SEPARATOR . 'avatar' . DIRECTORY_SEPARATOR;
                        if (!file_exists($dir_path)) {
                            mkdir($dir_path, 0777, true);
                        }
                        $file_path = $dir_path . sprintf('%s.%s', $params['username'], 'jpeg');
                        $uploadedFile->moveTo($file_path);
                    }
                }
            } catch (Exception $ex) {
            }

            return HF::UniJsonResponse($response, statusCode: 200);
        });

        $group->post('/delete-user/{id:[0-9]+}', function (Request $request, Response $response, $args) {
            $em = $this->get(EntityManager::class);
            $id = $args['id'];

            $query = $em->createQuery(
                'UPDATE UniPage\Domain\User p
            SET p.deleted=1
            WHERE p.id = :id, p.last_modification= :last_modification'
            )->setParameter('id', $id)->setParameter('last_modification', new DateTimeImmutable('now'));
            $content = $query->getResult();

            return HF::UniJsonResponse($response, statusCode: 200, content: $content);
        });

        $group->post('/change-password', function (Request $request, Response $response, $args) {
            $em = $this->get(EntityManager::class);

            $params = (array)$request->getParsedBody();
            try {
                User::validate_password($params);
                $query = $em->createQuery(
                    'UPDATE UniPage\Domain\User p
                SET p.password= :password, p.last_modification= :last_modification
                WHERE p.id = :id'
                )->setParameter('id', $params['id'])->setParameter('password', hash('sha256', $params['password']))
                    ->setParameter('last_modification', new DateTimeImmutable('now'));
                $query->getResult();
            } catch (ValidationException $ex) {
                return HF::UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
            }

            return HF::UniJsonResponse($response, statusCode: 200);
        });

        $group->post('/update-user', function (Request $request, Response $response, $args) {
            $em = $this->get(EntityManager::class);

            $params = (array)$request->getParsedBody();
            try {
                User::validate_update($params);
                $user = $em->find('UniPage\Domain\User', $params['id']);
                if ($user === null) {
                    throw new ValidationException("user doesn't exist");
                }
                $user->firstName = $params['firstName'];
                $user->lastName = $params['lastName'];
                $user->email = $params['email'];
                $user->start_date = $params['start_date'];
                $user->end_date = $params['end_date'];
                $user->position = $params['position'];
                $user->status = $params['status'];
                $user->is_admin = (int)$params['is_admin'];
                $user->last_modification = new DateTimeImmutable('now');
                $em->flush();
            } catch (ValidationException | UniqueConstraintViolationException $ex) {
                return HF::UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
            }

            try {
                $uploadedFiles = $request->getUploadedFiles();
                if (array_key_exists('avatar', $uploadedFiles)) {
                    $uploadedFile = $uploadedFiles['avatar'];
                    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                        $dir_path = $this->get('settings')['media_dir'] . DIRECTORY_SEPARATOR . 'avatar' . DIRECTORY_SEPARATOR;
                        if (!file_exists($dir_path)) {
                            mkdir($dir_path, 0777, true);
                        }
                        $file_path = $dir_path . sprintf('%s.%s', $user->username, 'jpeg');
                        $uploadedFile->moveTo($file_path);
                    }
                }
            } catch (Exception $ex) {
            }

            return HF::UniJsonResponse($response, statusCode: 200);
        });

        $group->post('/delete-avatar/{id:[0-9]+}', function (Request $request, Response $response, $args) {
            $em = $this->get(EntityManager::class);

            try {
                $user = $em->find('UniPage\Domain\User', $args['id']);
                if ($user === null) {
                    throw new ValidationException("user doesn't exist");
                }
                $dir_path = $this->get('settings')['media_dir'] . DIRECTORY_SEPARATOR . 'avatar' . DIRECTORY_SEPARATOR;
                $file_path = $dir_path . sprintf('%s.%s', $user->username, 'jpeg');
                if (file_exists($file_path)) {
                    unlink($file_path);
                    $user->last_modification = new DateTimeImmutable('now');
                    $em->flush();
                }
            } catch (ValidationException $ex) {
                return HF::UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
            }

            return HF::UniJsonResponse($response, statusCode: 200);
        });
    })->add(Guard::class)->add(new LoginMiddleware($app->getContainer()));
}

return function (App $app) {
    pagesRoute($app);
    apiRoute($app);
};
