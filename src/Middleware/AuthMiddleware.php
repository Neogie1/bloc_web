<?php
namespace App\Middleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use SlimSession\Helper;
use DI\Container;
use Doctrine\ORM\EntityManagerInterface;
use App\Domain\User;

class AuthMiddleware
{
    private $session;
    private $container;

    public function __construct(Container $c,Helper $session)
    {
        $this->session = $session;
        $this->container = $c;
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

        $em = $this->container->get(EntityManagerInterface::class);

        $idUser = $this->container->get('session')->get('user')['id']; 
        $user = $em->getRepository(User::class)->find($idUser);
        $this->container->get('view')->getEnvironment()->addGlobal('user', $user);
        $request = $request->withAttribute('user', $user);

        error_log('AuthMiddleware: User authenticated, proceeding');
        return $handler->handle($request);
    }
}