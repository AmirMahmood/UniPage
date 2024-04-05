<?php

namespace UniPage\Middleware;

use Doctrine\DBAL\Connection;
use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
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
            $conn = $this->container->get(Connection::class);
            
            $user = $conn->executeQuery(
                'SELECT username FROM user WHERE user.id = :id AND user.is_admin = 1 AND user.deleted = 0 AND user.status <> :status',
                ['id'=> $_SESSION['user'], 'status' => UserStatusEnum::BLOCKED->value]
            )->fetchAll();

            if (!empty($user)) {
                $response = $handler->handle($request);
                return $response;
            }
        }

        $response = $this->container->get(App::class)->getResponseFactory()->createResponse();
        return $response->withHeader('Location', '/admin/login')->withStatus(302);
    }
}
