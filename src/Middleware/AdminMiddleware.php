<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response;
use App\Domain\User;
use SlimSession\Helper;

class AdminMiddleware
{
    private $session;

    public function __construct(Helper $session)
    {
        $this->session = $session;
    }

    public function __invoke(Request $request, Handler $handler)
    {
        $user = $this->session->get('user');

        if (!$user || $user['role'] !== User::ROLE_ADMIN) {
            $response = new Response();
            return $response->withHeader('Location', '/dashboard')
                           ->withStatus(403);
        }

        return $handler->handle($request);
    }
}