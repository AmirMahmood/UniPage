<?php

namespace UniPage\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Doctrine\ORM\EntityManager;
use UniPage\utils\UserStatusEnum;

class LoginMiddleware
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        if (!empty($_SESSION['user'])) {
            $em = $this->container->get(EntityManager::class);

            $user = $em->getRepository('UniPage\Domain\User')
                ->findOneBy(array('id' => $_SESSION['user'], 'is_admin' => true, 'deleted' => false));
                
            if ($user != null && $user->status != UserStatusEnum::BLOCKED->value) {
                $response = $handler->handle($request);
                return $response;
            }
        }

        $response = new \Slim\Psr7\Response();
        return $response->withHeader('Location', '/admin/login')->withStatus(302);
    }
}
