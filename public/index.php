<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use App\Controller\UserController;
use App\Middleware\AuthMiddleware;
use Doctrine\ORM\EntityManagerInterface;
use SlimSession\Helper as SessionHelper;
use Slim\Middleware\Session;
use DI\Container;
use App\Controller\OfferController;

require __DIR__ . '/../vendor/autoload.php';

// ==================================================
// INITIALISATION DU CONTENEUR & APPLICATION
// ==================================================
$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

// ==================================================
// CONFIGURATION TWIG DANS LE CONTENEUR
// ==================================================
$container->set('view', function () {
    return Twig::create(__DIR__ . '/../templates', ['cache' => false]); // Désactive le cache pour le développement
});

// ==================================================
// MIDDLEWARES
// ==================================================
// Middleware de session pour gérer les sessions utilisateur
$app->add(new Session([
    'name' => 'my_app_session',
    'autorefresh' => true,
    'lifetime' => '1 hour',
    'secure' => false,  // À mettre à true si en production avec HTTPS
    'httponly' => true,
    'samesite' => 'Lax'  // 'Strict' ou 'Lax' selon tes besoins
]));

// Middleware Twig pour rendre les vues
$app->add(TwigMiddleware::createFromContainer($app));

// Middleware pour la gestion des erreurs
$app->addErrorMiddleware(true, true, true);

// ==================================================
// CONFIGURATION DOCTRINE
// ==================================================
$entityManager = require __DIR__ . '/../bootstrap.php';
$container->set(EntityManager::class, $entityManager);
$container->set(EntityManagerInterface::class, $entityManager); // Alias pour l'interface

// Enregistrement de la session dans le conteneur
$container->set('session', function () {
    return new SessionHelper();
});

// ==================================================
// CONFIGURATION DES SERVICES
// ==================================================
// Enregistrement du contrôleur UserController dans le conteneur
$container->set(UserController::class, function (Container $c) {
    return new UserController(
        $c->get(EntityManagerInterface::class),
        $c->get('session')
    );
});

$container->set(OfferController::class, function (Container $c) {
    return new OfferController(
        $c->get(EntityManagerInterface::class),
        $c->get('view')
    );
});

// Enregistrement du middleware AuthMiddleware dans le conteneur
$container->set(AuthMiddleware::class, function (Container $c) {
    return new AuthMiddleware($c->get('session'));
});

// ==================================================
// ROUTES
// ==================================================

$app->get('/offres', [OfferController::class, 'search'])->setName('searchOffers');
//$app->get('/offres?', [OfferController::class, 'list'])->setName('offers_list');

// Route d'accueil (page publique)
$app->get('/', function (Request $request, Response $response) use ($app) {
    $view = $app->getContainer()->get('view');
    return $view->render($response, 'home.html.twig');
})->setName('home');

// Route de connexion (GET pour afficher le formulaire)
$app->get('/login', function (Request $request, Response $response) use ($app) {
    $view = $app->getContainer()->get('view');
    return $view->render($response, 'login.html.twig');
})->setName('login');

// Route de connexion (POST pour vérifier et authentifier)
$app->post('/login', [UserController::class, 'login'])->setName('loginUser');

// Route d'inscription (POST pour créer un utilisateur)
$app->post('/user', [UserController::class, 'createUser'])->setName('createUser');

// Routes protégées (nécessitent une authentification)
$app->group('', function ($group) use ($app) {
    
    // Route du tableau de bord (accessible après connexion)
    $group->get('/dashboard', function (Request $request, Response $response) use ($app) {
        $view = $app->getContainer()->get('view');
        return $view->render($response, 'dashboard.html.twig');
    })->setName('dashboard');
    
    // Route pour afficher les informations de l'utilisateur actuel
    $group->get('/me', [UserController::class, 'getCurrentUser'])->setName('currentUser');
    
    // Route pour déconnexion
    $group->post('/logout', [UserController::class, 'logout'])->setName('logout');
    
})->add($app->getContainer()->get(AuthMiddleware::class));  // Applique le middleware AuthMiddleware à ce groupe de routes

// ==================================================
// DÉMARRAGE DE L'APPLICATION
// ==================================================
$app->run();