<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Création de l'application Slim
$app = AppFactory::create();

// ==================================================
// 1. MIDDLEWARE D'ERREURS (PRODUCTION)
// ==================================================
$errorMiddleware = $app->addErrorMiddleware(
    false,  // displayErrorDetails -> DÉSACTIVÉ en prod
    true,   // logErrors -> ACTIVÉ
    true    // logErrorDetails -> ACTIVÉ
);

// ==================================================
// 2. CONFIGURATION DE TWIG
// ==================================================
$twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

// ==================================================
// 3. VOS ROUTES
// ==================================================
$app->get('/', function (Request $request, Response $response) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'home.html.twig', [
        'name' => 'John',
    ]);
});

// ==================================================
// 4. DÉMARRAGE DE L'APPLICATION
// ==================================================
$app->run();