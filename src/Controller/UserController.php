<?php
namespace App\Controller;

use App\Domain\User;
use Doctrine\ORM\EntityManager;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use SlimSession\Helper;

class UserController
{
    private $entityManager;
    private $session;

    public function __construct(EntityManager $entityManager, Helper $session)
    {
        $this->entityManager = $entityManager;
        $this->session = $session;
    }

    public function createUser(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        
        // Validation
        if (empty($data['email']) || empty($data['password'])) {
            $this->session->set('error', 'Email et mot de passe requis');
            return $response->withHeader('Location', '/register')->withStatus(302);
        }

        // Vérification email existant
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);
            
        if ($existingUser) {
            $this->session->set('error', 'Cet email est déjà utilisé');
            return $response->withHeader('Location', '/register')->withStatus(302);
        }

        // Création utilisateur
        $user = new User(
            $data['email'],
            $data['password'],
            $data['role'] ?? User::ROLE_ETUDIANT
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Connexion automatique
        $this->session->set('user', [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole()
        ]);

        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }

    public function login(Request $request, Response $response, $args)
    {
        // =============================================
        // TEST TEMPORAIRE - DÉCOMMENTEZ CES 6 LIGNES POUR TESTER
        // =============================================
        $this->session->set('user', [
            'id' => 1,
            'email' => 'test@test.com',
            'password' => 'password',
            'role' => 'etudiant'
        ]);
        error_log('TEST: Session forcée - Redirection vers /dashboard');
        return $response->withHeader('Location', '/dashboard')->withStatus(302);
        // =============================================
        // FIN DU TEST TEMPORAIRE
        // =============================================

        $data = $request->getParsedBody();
        
        error_log('Tentative de connexion avec: ' . print_r($data, true));

        // Validation
        if (empty($data['email']) || empty($data['password'])) {
            $this->session->set('login_error', 'Email et mot de passe requis');
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);

        error_log('Utilisateur trouvé: ' . ($user ? $user->getEmail() : 'null'));

        // Vérification identifiants
        if (!$user || !password_verify($data['password'], $user->getPassword())) {
            error_log('Échec de connexion: ' . ($user ? 'Mot de passe incorrect' : 'Email introuvable'));
            $this->session->set('login_error', 'Email ou mot de passe incorrect');
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        // Connexion réussie - Double méthode de sauvegarde
        $_SESSION['user'] = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole()
        ];
        $this->session->set('user', $_SESSION['user']);

        error_log('Connexion réussie pour: ' . $user->getEmail());
        error_log('Données de session: ' . print_r($_SESSION['user'], true));

        // Redirection selon le rôle - Version absolue garantie
        $baseUrl = 'http://' . $_SERVER['HTTP_HOST'];
        $redirectUrl = match($user->getRole()) {
            User::ROLE_ADMIN => $baseUrl . '/admin/dashboard',
            User::ROLE_PILOTE => $baseUrl . '/pilote/dashboard',
            default => $baseUrl . '/dashboard'
        };

        return $response->withHeader('Location', $redirectUrl)->withStatus(302);
    }

    public function logout(Request $request, Response $response, $args)
    {
        // Destruction complète de la session
        unset($_SESSION['user']);
        $this->session->delete('user');
        $this->session->destroy();
        
        error_log('Déconnexion effectuée');
        return $response->withHeader('Location', '/')->withStatus(302);
    }

    public function getCurrentUser(Request $request, Response $response, $args)
    {
        $userSession = $_SESSION['user'] ?? $this->session->get('user');
        
        if (!$userSession) {
            error_log('Accès non autorisé: aucune session utilisateur');
            return $response->withJson(['error' => 'Non authentifié'], 401);
        }

        // Récupération des données fraîches
        $user = $this->entityManager->getRepository(User::class)
            ->find($userSession['id']);

        return $response->withJson([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'registered_at' => $user->getRegisteredAt()->format('Y-m-d H:i:s')
        ]);
    }
}