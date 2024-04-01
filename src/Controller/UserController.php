<?php

namespace UniPage\Controller;

use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Exception;
use UniPage\utils\HelperFuncs as HF;
use UniPage\Domain\User;
use UniPage\utils\UserStatusEnum;
use UniPage\utils\ValidationException;

class UserController
{
    private $view;
    private $settings;
    private $container;
    private $em;

    public function __construct(Twig $view, EntityManager $em, ContainerInterface $container)
    {
        $this->view = $view;
        $this->em = $em;
        $this->container = $container;
        $this->settings = $container->get('settings');
    }

    public function site_people_page(Request $request, Response $response)
    {
        $query = $this->em->createQuery(
            'SELECT p
            FROM UniPage\Domain\User p
            WHERE p.status <> :status
            ORDER BY p.start_date ASC'
        )->setParameter('status', UserStatusEnum::BLOCKED->value);
        $res = $query->getResult();

        return $this->view->render($response, 'people.html', ['page_id' => "people", 'users' => $res]);
    }

    public function site_alumni_page(Request $request, Response $response)
    {
        $query = $this->em->createQuery(
            'SELECT p
            FROM UniPage\Domain\User p
            WHERE p.status <> :status
            ORDER BY p.start_date ASC'
        )->setParameter('status', UserStatusEnum::BLOCKED->value);
        $res = $query->getResult();

        return $this->view->render($response, 'alumni.html', ['page_id' => "alumni", 'users' => $res]);
    }

    public function admin_users_list_page(Request $request, Response $response)
    {
        return $this->view->render(
            $response,
            'admin/users.html',
            ['page_id' => "users", 'csrf' => HF::getCSRF($this->container), 'site_info' => $this->settings['site_info']]
        );
    }

    public function admin_user_create_page(Request $request, Response $response)
    {
        return $this->view->render(
            $response,
            'admin/user-create.html',
            ['page_id' => "users", 'csrf' => HF::getCSRF($this->container), 'site_info' => $this->settings['site_info']]
        );
    }

    public function admin_user_edit_page(Request $request, Response $response, $args)
    {
        $id = $args['id'];
        return $this->view->render(
            $response,
            'admin/user-edit.html',
            ['page_id' => "users", 'id' => $id, 'csrf' => HF::getCSRF($this->container), 'site_info' => $this->settings['site_info']]
        );
    }

    public function get_users_list(Request $request, Response $response)
    {
        $query = $this->em->createQuery(
            'SELECT p
        FROM UniPage\Domain\User p
        WHERE p.deleted <> 1
        ORDER BY p.id ASC'
        );
        $content = $query->getResult();

        return HF::UniJsonResponse($response, statusCode: 200, content: $content);
    }

    public function create_user(Request $request, Response $response)
    {
        $params = (array)$request->getParsedBody();
        try {
            $params = User::validate_new($params);
            $newUser = new User(...$params);
            $this->em->persist($newUser);
            $this->em->flush();
        } catch (ValidationException | UniqueConstraintViolationException $ex) {
            return HF::UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
        }

        try {
            $uploadedFiles = $request->getUploadedFiles();
            if (array_key_exists('avatar', $uploadedFiles)) {
                $uploadedFile = $uploadedFiles['avatar'];
                if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                    $dir_path = $this->settings['media_dir'] . DIRECTORY_SEPARATOR . 'avatar' . DIRECTORY_SEPARATOR;
                    if (!file_exists($dir_path)) {
                        mkdir($dir_path, 0777, true);
                    }
                    $file_path = $dir_path . sprintf('%s.%s', $params['username'], 'jpg');
                    $uploadedFile->moveTo($file_path);
                }
            }
        } catch (Exception $ex) {
        }

        return HF::UniJsonResponse($response, statusCode: 200);
    }

    public function get_user(Request $request, Response $response, $args)
    {
        $id = $args['id'];

        $query = $this->em->createQuery(
            'SELECT p
        FROM UniPage\Domain\User p
        WHERE p.id = :id AND p.deleted <> 1
        ORDER BY p.id ASC'
        )->setParameter('id', $id);

        // return empty content on not fount
        $content = $query->getResult();

        return HF::UniJsonResponse($response, statusCode: 200, content: $content);
    }

    public function delete_user(Request $request, Response $response, $args)
    {
        $id = $args['id'];

        $query = $this->em->createQuery(
            'UPDATE UniPage\Domain\User p
        SET p.deleted=1, p.last_modification=:last_modification
        WHERE p.id = :id'
        )->setParameter('id', $id)->setParameter('last_modification', new DateTimeImmutable('now'));
        $content = $query->getResult();

        return HF::UniJsonResponse($response, statusCode: 200, content: $content);
    }

    public function update_user(Request $request, Response $response, $args)
    {
        $params = (array)$request->getParsedBody();
        try {
            $params = User::validate_update($params);
            $user = $this->em->find('UniPage\Domain\User', $params['id']);
            if ($user === null) {
                throw new ValidationException("user doesn't exist");
            }
            $user->update(...$params);
            $this->em->flush();
        } catch (ValidationException | UniqueConstraintViolationException $ex) {
            return HF::UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
        }

        try {
            $uploadedFiles = $request->getUploadedFiles();
            if (array_key_exists('avatar', $uploadedFiles)) {
                $uploadedFile = $uploadedFiles['avatar'];
                if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                    $dir_path = $this->settings['media_dir'] . DIRECTORY_SEPARATOR . 'avatar' . DIRECTORY_SEPARATOR;
                    if (!file_exists($dir_path)) {
                        mkdir($dir_path, 0777, true);
                    }
                    $file_path = $dir_path . sprintf('%s.%s', $user->username, 'jpg');
                    $uploadedFile->moveTo($file_path);
                }
            }
        } catch (Exception $ex) {
        }

        return HF::UniJsonResponse($response, statusCode: 200);
    }

    public function change_password(Request $request, Response $response, $args)
    {
        $params = (array)$request->getParsedBody();
        try {
            $params = User::validate_password($params);
            $query = $this->em->createQuery(
                'UPDATE UniPage\Domain\User p
                SET p.password= :password, p.last_modification= :last_modification
                WHERE p.id = :id'
            )->setParameter('id', $params['id'])->setParameter('password', $params['password'])
                ->setParameter('last_modification', new DateTimeImmutable('now'));
            $query->getResult();
        } catch (ValidationException $ex) {
            return HF::UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
        }

        return HF::UniJsonResponse($response, statusCode: 200);
    }

    public function delete_avatar(Request $request, Response $response, $args)
    {
        try {
            $user = $this->em->find('UniPage\Domain\User', $args['id']);
            if ($user === null) {
                throw new ValidationException("user doesn't exist");
            }
            $dir_path = $this->settings['media_dir'] . DIRECTORY_SEPARATOR . 'avatar' . DIRECTORY_SEPARATOR;
            $file_path = $dir_path . sprintf('%s.%s', $user->username, 'jpg');
            if (file_exists($file_path)) {
                unlink($file_path);
                $user->last_modification = new DateTimeImmutable('now');
                $this->em->flush();
            }
        } catch (ValidationException $ex) {
            return HF::UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
        }

        return HF::UniJsonResponse($response, statusCode: 200);
    }
}
