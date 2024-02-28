<?php

use Slim\App;
use Slim\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use UniPage\Domain\User;
use UniPage\utils\ValidationException;

function getCSRF($t)
{
    // CSRF token name and value
    $csrfNameKey = $t->get('csrf')->getTokenNameKey();
    $csrfValueKey = $t->get('csrf')->getTokenValueKey();
    $csrfName = $t->get('csrf')->getTokenName();
    $csrfValue = $t->get('csrf')->getTokenValue();

    return [
        'keys' => [
            'name'  => $csrfNameKey,
            'value' => $csrfValueKey
        ],
        'name'  => $csrfName,
        'value' => $csrfValue
    ];
}

function UniJsonResponse($response, $statusCode, $content = [], $errorMessage = "")
{
    $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
    $response->getBody()->write($serializer->serialize(['error_message' => $errorMessage, 'content' => $content], 'json'));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($statusCode);
}

function pagesRoute(App $app)
{
    $app->get('/admin/login', function (Request $request, Response $response, $args) {
        $view = $this->get('view');
        return $view->render(
            $response,
            'admin/login.html',
            ['csrf' => getCSRF($this), 'site_info' => $this->get('settings')['site_info']]
        );
    });

    $app->get('/admin/users', function (Request $request, Response $response, $args) {
        $view = $this->get('view');
        return $view->render(
            $response,
            'admin/users.html',
            ['page_id' => "users", 'csrf' => getCSRF($this), 'site_info' => $this->get('settings')['site_info']]
        );
    });

    $app->get('/admin/user-create', function (Request $request, Response $response, $args) {
        $view = $this->get('view');
        return $view->render(
            $response,
            'admin/user-create.html',
            ['page_id' => "users", 'csrf' => getCSRF($this), 'site_info' => $this->get('settings')['site_info']]
        );
    });

    $app->get('/admin/user-edit/{id:[0-9]+}', function (Request $request, Response $response, $args) {
        $view = $this->get('view');
        $id = $args['id'];
        return $view->render(
            $response,
            'admin/user-edit.html',
            ['page_id' => "users", 'id' => $id, 'csrf' => getCSRF($this), 'site_info' => $this->get('settings')['site_info']]
        );
    });
};

return function (App $app) {
    pagesRoute($app);

    $app->get('/api/get-users', function (Request $request, Response $response, $args) {
        $em = $this->get('orm');

        $query = $em->createQuery(
            'SELECT p
            FROM UniPage\Domain\User p
            WHERE p.deleted <> 1
            ORDER BY p.id ASC'
        );
        $content = $query->getResult();

        return UniJsonResponse($response, statusCode: 200, content: $content);
    });

    $app->get('/api/get-user/{id:[0-9]+}', function (Request $request, Response $response, $args) {
        $em = $this->get('orm');
        $id = $args['id'];

        $query = $em->createQuery(
            'SELECT p
            FROM UniPage\Domain\User p
            WHERE p.id = :id AND p.deleted <> 1
            ORDER BY p.id ASC'
        )->setParameter('id', $id);
        $content = $query->getResult();

        return UniJsonResponse($response, statusCode: 200, content: $content);
    });

    $app->post('/api/create-user', function (Request $request, Response $response, $args) {
        $em = $this->get('orm');

        $params = (array)$request->getParsedBody();
        try {
            User::validate_new($params);
            $newUser = new User(...$params);
            $em->persist($newUser);
            $em->flush();
        } catch (ValidationException | UniqueConstraintViolationException $ex) {
            return UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
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

        return UniJsonResponse($response, statusCode: 200);
    });

    $app->post('/api/delete-user/{id:[0-9]+}', function (Request $request, Response $response, $args) {
        $em = $this->get('orm');
        $id = $args['id'];

        $query = $em->createQuery(
            'UPDATE UniPage\Domain\User p
            SET p.deleted=1
            WHERE p.id = :id'
        )->setParameter('id', $id);
        $content = $query->getResult();

        return UniJsonResponse($response, statusCode: 200, content: $content);
    });

    $app->post('/api/change-password', function (Request $request, Response $response, $args) {
        $em = $this->get('orm');

        $params = (array)$request->getParsedBody();
        try {
            User::validate_password($params);
            $query = $em->createQuery(
                'UPDATE UniPage\Domain\User p
                SET p.password= :password
                WHERE p.id = :id'
            )->setParameter('id', $params['id'])->setParameter('password', hash('sha256', $params['password']));
            $query->getResult();
        } catch (ValidationException $ex) {
            return UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
        }

        return UniJsonResponse($response, statusCode: 200);
    });

    $app->post('/api/update-user', function (Request $request, Response $response, $args) {
        $em = $this->get('orm');

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
            $user->date = $params['date'];
            $user->position = $params['position'];
            $user->status = $params['status'];
            $user->is_admin = $params['is_admin'];
            $em->flush();
        } catch (ValidationException | UniqueConstraintViolationException $ex) {
            return UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
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

        return UniJsonResponse($response, statusCode: 200);
    });

    $app->post('/api/delete-avatar/{id:[0-9]+}', function (Request $request, Response $response, $args) {
        $em = $this->get('orm');

        try {
            $user = $em->find('UniPage\Domain\User', $args['id']);
            if ($user === null) {
                throw new ValidationException("user doesn't exist");
            }
            $dir_path = $this->get('settings')['media_dir'] . DIRECTORY_SEPARATOR . 'avatar' . DIRECTORY_SEPARATOR;
            $file_path = $dir_path . sprintf('%s.%s', $user->username, 'jpeg');
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        } catch (ValidationException $ex) {
            return UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
        }

        return UniJsonResponse($response, statusCode: 200);
    });
};
