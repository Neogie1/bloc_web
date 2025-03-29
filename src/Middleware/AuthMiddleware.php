<?php
namespace App\Middleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use SlimSession\Helper;

class AuthMiddleware
{
    private $session;

    public function __construct(Helper $session)
    {
        $this->session = $session;
    }

    public function __invoke(Request $request, Handler $handler): Response
    {
        // Debug: Vérifiez le contenu de la session
        error_log('AuthMiddleware - Session content: ' . print_r($this->session->get('user'), true));
        
        if (!$this->session->exists('user')) {
            error_log('AuthMiddleware: User not authenticated, redirecting to login');
            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        error_log('AuthMiddleware: User authenticated, proceeding');
        return $handler->handle($request);
    }
}