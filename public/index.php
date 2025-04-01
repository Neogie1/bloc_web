<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use App\Controller\UserController;
use App\Middleware\AuthMiddleware;
use App\Middleware\ForcedAuthMiddleware;
use App\Middleware\AdminMiddleware;
use Doctrine\ORM\EntityManagerInterface;
use SlimSession\Helper as SessionHelper;
use Slim\Middleware\Session;
use DI\Container;
use App\Controller\OfferController;
use App\Domain\User;
use App\Controller\EntrepriseController;

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
    $twig = Twig::create(__DIR__ . '/../templates', ['cache' => false,'debug'=>true]);
    $twig->addExtension(new \Twig\Extension\DebugExtension());
    return $twig;
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
        $c->get('view'),
        $c->get('session')
    );
});

$container->set(OfferController::class, function (Container $c) {
    return new OfferController(
        $c->get(EntityManagerInterface::class),
        $c->get('view')
    );
});
// Enregistrement du contrôleur EntrepriseController
$container->set(EntrepriseController::class, function (Container $c) {
    return new EntrepriseController(
        $c->get(EntityManagerInterface::class),
        $c->get('view'),
        $c->get('session')
    );
});

$container->set(AuthMiddleware::class, function (Container $c) {
    return new AuthMiddleware($c,$c->get('session'));
});
// Enregistrement du middleware AuthMiddleware dans le conteneur
$container->set(ForcedAuthMiddleware::class, function (Container $c) {
    return new ForcedAuthMiddleware($c,$c->get('session'));
});
$container->set(AdminMiddleware::class, function (Container $c) {
    return new AdminMiddleware($c->get('session'));
});

// ==================================================
// ROUTES
// ==================================================



// Routes pour les pages légales
$app->get('/politique-confidentialite', function ($request, $response) {
    return $this->get('view')->render($response, 'politique-confidentialite.html.twig');
})->setName('politique_confidentialite');

$app->get('/conditions-utilisation', function ($request, $response) {
    return $this->get('view')->render($response, 'conditions-utilisation.html.twig');
})->setName('conditions_utilisation');


// Routes publiques avec middleware conditionnel
$app->get('/offres/search', [OfferController::class, 'search'])->setName('searchOffers')
    ->add($container->get(ForcedAuthMiddleware::class));

$app->get('/offres/list', [OfferController::class, 'search'])->setName('listOffers')
    ->add($container->get(ForcedAuthMiddleware::class));

// Route d'accueil (page publique)
$app->get('/', function (Request $request, Response $response) {
    return $this->get('view')->render($response, 'home.html.twig');
})->setName('home')->add($app->getContainer()->get(AuthMiddleware::class));;

// Route de connexion (GET pour afficher le formulaire)
$app->get('/login', function (Request $request, Response $response) {
    return $this->get('view')->render($response, 'login.html.twig', [
        'old_input' => '',
        'errors' => []
    ]);
})->setName('login');

// Route de connexion (POST pour vérifier et authentifier)
$app->post('/login', [UserController::class, 'login'])->setName('loginUser');

// Route d'inscription (POST pour créer un utilisateur)
$app->post('/user', [UserController::class, 'createUser'])->setName('createUser');

// Routes protégées (nécessitent une authentification)
$app->group('', function ($group) use ($app) {
    
    // Route du tableau de bord principal
    $group->get('/dashboard', function (Request $request, Response $response) {
        return $this->get('view')->render($response, 'dashboard.html.twig', [
        ]);
    })->setName('dashboard');
    
    // Route pour afficher les informations de l'utilisateur actuel
    $group->get('/me', [UserController::class, 'getCurrentUser'])->setName('currentUser');
    
    // Route pour déconnexion
    $group->post('/logout', [UserController::class, 'logout'])->setName('logout');
    
})->add($app->getContainer()->get(ForcedAuthMiddleware::class));



// Routes protégées (nécessitent une authentification)
$app->group('/admin', function ($group) use ($app) {
    
    // Route du tableau de bord admin avec vérification de rôle
    $group->get('/dashboard', function (Request $request, Response $response) {
        return $this->get('view')->render($response, 'admin/dashboard.html.twig', [
        ]);
    })->setName('admin.dashboard');
    

    $group->group('/users', function ($group) {
        // Liste des utilisateurs
        $group->get('', \App\Controller\UserController::class . ':listUsers')
            ->setName('admin.users.list');
        
        // Formulaire de création d'utilisateur (GET)
        $group->get('/create', \App\Controller\UserController::class . ':showCreateForm')
            ->setName('admin.users.form');
        
        // Action de création d'utilisateur (POST)
        $group->post('/create', \App\Controller\UserController::class . ':createUser')
            ->setName('admin.users.create');
        
        // Formulaire d'édition d'utilisateur (GET)
        $group->get('/edit/{id}', \App\Controller\UserController::class . ':showEditForm')
            ->setName('admin.users.edit.form');
        
        // Action d'édition d'utilisateur (POST)
        $group->post('/edit/{id}', \App\Controller\UserController::class . ':editUser')
            ->setName('admin.users.edit');
        
        // Action de suppression d'utilisateur (POST)
        $group->post('/delete/{id}', \App\Controller\UserController::class . ':deleteUser')
            ->setName('admin.users.delete');
    });

    

    // Routes pour les entreprises
    $group->group('/entreprises', function ($group) {
        $group->get('', [EntrepriseController::class, 'list'])->setName('entreprises.list');
        
        // Création (admin et pilote seulement)
        $group->get('/create', [EntrepriseController::class, 'createForm'])->setName('entreprises.create.form');
        $group->post('/create', [EntrepriseController::class, 'create'])->setName('entreprises.create');
        
        // Édition (admin et pilote seulement)
        $group->get('/{id}/edit', [EntrepriseController::class, 'editForm'])->setName('entreprises.edit.form');
        $group->post('/{id}/edit', [EntrepriseController::class, 'edit'])->setName('entreprises.edit');
        
        // Évaluation (tous utilisateurs)
        $group->post('/{id}/evaluate', [EntrepriseController::class, 'evaluate'])->setName('entreprises.evaluate');

            // Suppression (modifiée en POST au lieu de DELETE)
            $group->post('/{id:\d+}/delete', [EntrepriseController::class, 'delete'])->setName('entreprises.delete');
        
        // Statistiques
        $group->get('/{id}/stats', [EntrepriseController::class, 'stats'])->setName('entreprises.stats');
    })->add($app->getContainer()->get(ForcedAuthMiddleware::class));


})->add($app->getContainer()->get(AdminMiddleware::class))->add($app->getContainer()->get(ForcedAuthMiddleware::class));


// ==================================================
// DÉMARRAGE DE L'APPLICATION
// ==================================================
$app->run();