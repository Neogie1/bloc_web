<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use App\Controller\UserController;
use Doctrine\ORM\EntityManagerInterface;

require __DIR__ . '/../vendor/autoload.php';

// Création de l'application Slim
$app = AppFactory::create();

// ==================================================
// 1. MIDDLEWARE D'ERREURS (AFFICHAGE DES ERREURS)
// ==================================================
$errorMiddleware = $app->addErrorMiddleware(true, true, true); // Affichage des erreurs pour le dev

// ==================================================
// 2. CONFIGURATION DE TWIG
// ==================================================
$twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

// ==================================================
// 3. CONFIGURATION DE DOCTRINE (EntityManager)
// ==================================================
$entityManager = require __DIR__ . '/../bootstrap.php';

// ==================================================
// 4. CONFIGURATION DU CONTAINER DE DÉPENDANCES
// ==================================================
$container = $app->getContainer();

// Ajout de l'EntityManager au conteneur
$container[EntityManagerInterface::class] = function () use ($entityManager) {
    return $entityManager;
};

// Créer une instance de UserController avec l'injection de dépendances
$container[UserController::class] = function ($container) {
    return new UserController($container->get(EntityManagerInterface::class));
};

// ==================================================
// 5. ROUTES
// ==================================================

// Route d'accueil
$app->get('/', function (Request $request, Response $response) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'home.html.twig');
})->setName('home');

// Route pour le formulaire de login
$app->get('/login', function (Request $request, Response $response) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'login.html.twig');  // Retirer le 'security/' du chemin
})->setName('login');

// Route pour la création d'un utilisateur
$app->post('/user', [UserController::class, 'createUser'])->setName('createUser');

// Route pour la connexion de l'utilisateur
$app->post('/login', [UserController::class, 'login'])->setName('loginUser');

// ==================================================
// 6. DÉMARRAGE DE L'APPLICATION
// ==================================================
$app->run();
