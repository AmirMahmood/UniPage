<?php

namespace UniPage\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use UniPage\utils\HelperFuncs as HF;
use UniPage\Domain\Link;
use UniPage\utils\ValidationException;

class LinkController
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

    public function site_links_page(Request $request, Response $response)
    {
        $query = $this->em->createQuery(
            'SELECT p
            FROM UniPage\Domain\Link p
            ORDER BY p.title ASC'
        );
        $res = $query->getResult();

        return $this->view->render($response, 'links.html', ['page_id' => "links", 'links' => $res]);
    }

    public function admin_links_list_page(Request $request, Response $response)
    {
        return $this->view->render(
            $response,
            'admin/links.html',
            ['page_id' => "links", 'csrf' => HF::getCSRF($this->container), 'site_info' => $this->settings['site_info']]
        );
    }

    public function admin_link_create_page(Request $request, Response $response)
    {
        return $this->view->render(
            $response,
            'admin/link-create.html',
            ['page_id' => "links", 'csrf' => HF::getCSRF($this->container), 'site_info' => $this->settings['site_info']]
        );
    }

    public function admin_link_edit_page(Request $request, Response $response, $args)
    {
        $id = $args['id'];
        return $this->view->render(
            $response,
            'admin/link-edit.html',
            ['page_id' => "links", 'id' => $id, 'csrf' => HF::getCSRF($this->container), 'site_info' => $this->settings['site_info']]
        );
    }

    public function get_links_list(Request $request, Response $response)
    {
        $query = $this->em->createQuery(
            'SELECT p
        FROM UniPage\Domain\Link p
        ORDER BY p.id ASC'
        );
        $content = $query->getResult();

        return HF::UniJsonResponse($response, statusCode: 200, content: $content);
    }

    public function create_link(Request $request, Response $response)
    {
        $params = (array)$request->getParsedBody();
        try {
            $params = Link::validate_new($params);
            $new_link = new Link(...$params);
            $this->em->persist($new_link);
            $this->em->flush();
        } catch (ValidationException $ex) {
            return HF::UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
        }

        return HF::UniJsonResponse($response, statusCode: 200);
    }

    public function get_link(Request $request, Response $response, $args)
    {
        $id = $args['id'];

        $query = $this->em->createQuery(
            'SELECT p
        FROM UniPage\Domain\Link p
        WHERE p.id = :id
        ORDER BY p.id ASC'
        )->setParameter('id', $id);

        // return empty content on not fount
        $content = $query->getResult();

        return HF::UniJsonResponse($response, statusCode: 200, content: $content);
    }

    public function delete_link(Request $request, Response $response, $args)
    {
        $this->em->getConnection()->delete('link', ['id' => $args['id']]);

        return HF::UniJsonResponse($response, statusCode: 200);
    }

    public function update_link(Request $request, Response $response, $args)
    {
        $params = (array)$request->getParsedBody();
        try {
            $params = Link::validate_update($params);
            $link = $this->em->find('UniPage\Domain\Link', $params['id']);
            if ($link === null) {
                throw new ValidationException("link doesn't exist");
            }
            $link->update(...$params);
            $this->em->flush();
        } catch (ValidationException | UniqueConstraintViolationException $ex) {
            return HF::UniJsonResponse($response, statusCode: 400, errorMessage: $ex->getMessage());
        }
        return HF::UniJsonResponse($response, statusCode: 200);
    }
}
