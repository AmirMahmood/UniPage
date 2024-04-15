<?php

namespace UniPage\Controller;

use DateTimeImmutable;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use UniPage\utils\HelperFuncs as HF;
use UniPage\Domain\Photo;
use UniPage\utils\ValidationException;

class PhotoController
{
    private $view;
    private $settings;
    private $container;
    private $em;

    private $gallery_dir_path;

    public function __construct(Twig $view, EntityManager $em, ContainerInterface $container)
    {
        $this->view = $view;
        $this->em = $em;
        $this->container = $container;
        $this->settings = $container->get('settings');

        $this->gallery_dir_path = $this->settings['media_dir'] . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR;
    }

    public function site_gallery_page(Request $request, Response $response)
    {
        $query = $this->em->createQuery(
            'SELECT p
            FROM UniPage\Domain\Photo p
            ORDER BY p.date DESC, p.created DESC'
        );
        $res = $query->getResult();

        return $this->view->render($response, 'gallery.html', ['page_id' => "gallery", 'photos' => $res]);
    }

    public function admin_photos_list_page(Request $request, Response $response)
    {
        return $this->view->render(
            $response,
            'admin/photos.html',
            ['page_id' => "photos", 'csrf' => HF::getCSRF($this->container), 'site_info' => $this->settings['site_info']]
        );
    }

    public function admin_photo_create_page(Request $request, Response $response)
    {
        return $this->view->render(
            $response,
            'admin/photo-create.html',
            ['page_id' => "photos", 'csrf' => HF::getCSRF($this->container), 'site_info' => $this->settings['site_info']]
        );
    }

    public function admin_photo_edit_page(Request $request, Response $response, $args)
    {
        $id = $args['id'];
        return $this->view->render(
            $response,
            'admin/photo-edit.html',
            ['page_id' => "photos", 'id' => $id, 'csrf' => HF::getCSRF($this->container), 'site_info' => $this->settings['site_info']]
        );
    }

    public function get_photos_list(Request $request, Response $response)
    {
        $query = $this->em->createQuery(
            'SELECT p
        FROM UniPage\Domain\Photo p
        ORDER BY p.id DESC'
        );
        $content = $query->getResult();

        return HF::UniJsonResponse($response, statusCode: 200, content: $content);
    }

    public function create_photo(Request $request, Response $response)
    {
        $params = (array)$request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();

        try {
            if (!array_key_exists('photo', $uploadedFiles)) {
                throw new ValidationException("Please select an image");
            }
            $uploadedFile = $uploadedFiles['photo'];
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                throw new ValidationException("Image upload failed");
            }
            if (!file_exists($this->gallery_dir_path)) {
                mkdir($this->gallery_dir_path, 0777, true);
            }

            $client_filename_spit = explode(".", strtolower($uploadedFile->getClientFilename()));
            $params['filename'] = sprintf(
                '%s.%s',
                date_format(new DateTimeImmutable('now'), 'Ymd-His-u'),
                end($client_filename_spit)
            );
            $file_path = $this->gallery_dir_path . $params['filename'];
            $uploadedFile->moveTo($file_path);
        } catch (ValidationException $ex) {
            return HF::UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
        }

        try {
            $params = Photo::validate_new($params);
            $new_obj = new Photo(...$params);
            $this->em->persist($new_obj);
            $this->em->flush();
        } catch (ValidationException $ex) {
            return HF::UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
        }

        return HF::UniJsonResponse($response, statusCode: 200);
    }

    public function get_photo(Request $request, Response $response, $args)
    {
        $id = $args['id'];

        $query = $this->em->createQuery(
            'SELECT p
        FROM UniPage\Domain\Photo p
        WHERE p.id = :id
        ORDER BY p.id DESC'
        )->setParameter('id', $id);

        // return empty content on not fount
        $content = $query->getResult();

        return HF::UniJsonResponse($response, statusCode: 200, content: $content);
    }

    public function delete_photo(Request $request, Response $response, $args)
    {
        $this->em->getConnection()->delete('photo', ['id' => $args['id']]);

        return HF::UniJsonResponse($response, statusCode: 200);
    }

    public function update_photo(Request $request, Response $response, $args)
    {
        $params = (array)$request->getParsedBody();
        try {
            $params = Photo::validate_update($params);
            $obj = $this->em->find('UniPage\Domain\Photo', $params['id']);
            if ($obj === null) {
                throw new ValidationException("photo doesn't exist");
            }
            $obj->update(...$params);
            $this->em->flush();
        } catch (ValidationException | UniqueConstraintViolationException $ex) {
            return HF::UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
        }
        return HF::UniJsonResponse($response, statusCode: 200);
    }
}
