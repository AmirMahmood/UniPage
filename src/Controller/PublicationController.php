<?php

namespace UniPage\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use UniPage\utils\HelperFuncs as HF;
use UniPage\Domain\Publication;
use UniPage\utils\ValidationException;

class PublicationController
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

    public function site_publications_page(Request $request, Response $response)
    {
        $query = $this->em->createQuery(
            'SELECT p
            FROM UniPage\Domain\Publication p
            ORDER BY p.year DESC'
        );
        $res = $query->getResult();

        return $this->view->render($response, 'publications.html', ['page_id' => "publications", 'publications' => $res]);
    }

    public function admin_publications_list_page(Request $request, Response $response)
    {
        return $this->view->render(
            $response,
            'admin/publications.html',
            ['page_id' => "publications", 'csrf' => HF::getCSRF($this->container), 'site_info' => $this->settings['site_info']]
        );
    }

    public function admin_publication_create_page(Request $request, Response $response)
    {
        return $this->view->render(
            $response,
            'admin/publication-create.html',
            ['page_id' => "publications", 'csrf' => HF::getCSRF($this->container), 'site_info' => $this->settings['site_info']]
        );
    }

    public function admin_publication_edit_page(Request $request, Response $response, $args)
    {
        $id = $args['id'];
        return $this->view->render(
            $response,
            'admin/publication-edit.html',
            ['page_id' => "publications", 'id' => $id, 'csrf' => HF::getCSRF($this->container), 'site_info' => $this->settings['site_info']]
        );
    }

    public function get_publications_list(Request $request, Response $response)
    {
        $query = $this->em->createQuery(
            'SELECT p
        FROM UniPage\Domain\Publication p
        ORDER BY p.year DESC'
        );
        $content = $query->getResult();

        return HF::UniJsonResponse($response, statusCode: 200, content: $content);
    }

    public function create_publication(Request $request, Response $response)
    {
        $params = (array)$request->getParsedBody();
        try {
            $params = Publication::validate_new($params);
            $new_pub = new Publication(...$params);
            $this->em->persist($new_pub);
            $this->em->flush();
        } catch (ValidationException $ex) {
            return HF::UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
        }

        return HF::UniJsonResponse($response, statusCode: 200);
    }

    public function get_publication(Request $request, Response $response, $args)
    {
        $id = $args['id'];

        $query = $this->em->createQuery(
            'SELECT p
        FROM UniPage\Domain\Publication p
        WHERE p.id = :id
        ORDER BY p.id DESC'
        )->setParameter('id', $id);

        // return empty content on not fount
        $content = $query->getResult();

        return HF::UniJsonResponse($response, statusCode: 200, content: $content);
    }

    public function delete_publication(Request $request, Response $response, $args)
    {
        $this->em->getConnection()->delete('publication', ['id' => $args['id']]);

        return HF::UniJsonResponse($response, statusCode: 200);
    }

    public function update_publication(Request $request, Response $response, $args)
    {
        $params = (array)$request->getParsedBody();
        try {
            $params = Publication::validate_update($params);
            $publication = $this->em->find('UniPage\Domain\Publication', $params['id']);
            if ($publication === null) {
                throw new ValidationException("publication doesn't exist");
            }
            $publication->update(...$params);
            $this->em->flush();
        } catch (ValidationException | UniqueConstraintViolationException $ex) {
            return HF::UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
        }
        return HF::UniJsonResponse($response, statusCode: 200);
    }
}
