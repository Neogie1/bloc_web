<?php
namespace App\Controller;

use App\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use SlimSession\Helper;
use Slim\Views\Twig;

class UserController
{
    private $twig;
    private $entityManager;
    private $session;

    public function __construct(EntityManagerInterface $entityManager, Twig $twig, Helper $session)
    {
        $this->entityManager = $entityManager;
        $this->twig = $twig;
        $this->session = $session;
    }

    private function getUserRole()
    {
        $user = $this->session->get('user');
        return $user['role'] ?? null;
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
        // $this->session->set('user', [
        //     'id' => 1,
        //     'email' => 'test@test.com',
        //     'password' => 'password',
        //     'role' => 'etudiant'
        // ]);
        // error_log('TEST: Session forcée - Redirection vers /dashboard');
        // return $response->withHeader('Location', '/dashboard')->withStatus(302);
        // =============================================
        // FIN DU TEST TEMPORAIRE
        // =============================================

        $data = $request->getParsedBody();
        
        error_log('Tentative de connexion avec: ' . print_r($data, true));

        // Validation améliorée
        $errors = [];
        $oldInput = ['email' => $data['email'] ?? ''];

        if (empty($data['email'])) {
            $errors[] = 'L\'email est requis';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format d\'email invalide';
        }

        if (empty($data['password'])) {
            $errors[] = 'Le mot de passe est requis';
        }

        if (!empty($errors)) {
            return $this->twig->render($response, 'login.html.twig', [
                'old_input' => $oldInput,
                'errors' => $errors
            ]);
        }

        // Recherche utilisateur
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);

        error_log('Utilisateur trouvé: ' . ($user ? $user->getEmail() : 'null'));

        // Vérification identifiants
        if (!$user) {
            error_log('Échec de connexion: Email introuvable');
            $errors[] = 'Aucun compte trouvé avec cet email';
            return $this->twig->render($response, 'login.html.twig', [
                'old_input' => $oldInput,
                'errors' => $errors
            ]);
        }

        if (!password_verify($data['password'], $user->getPassword())) {
            error_log('Échec de connexion: Mot de passe incorrect');
            $errors[] ='Mot de passe incorrect';
            return $this->twig->render($response, 'login.html.twig', [
                'old_input' => $oldInput,
                'errors' => $errors
            ]);
        }

        // Connexion réussie
        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole()
        ];
        
        $_SESSION['user'] = $userData;
        $this->session->set('user', $userData);

        error_log('Connexion réussie pour: ' . $user->getEmail());

        // Nettoyage des erreurs et anciennes entrées
        $this->session->delete('login_errors');
        $this->session->delete('old_input');
        $this->session->delete('login_error');

        // Redirection selon le rôle
        $redirectUrl = match($user->getRole()) {
            User::ROLE_ADMIN => '/admin/dashboard',
            User::ROLE_PILOTE => '/pilote/dashboard',
            default => '/dashboard'
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

    public function listUsers(Request $request, Response $response, $args): Response
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();

        // Ici, on transforme les entités en tableaux associatifs si nécessaire
        $userArray = array_map(function($user) {
            return [
                'id'    => $user->getId(),
                'nom'   => $user->getNom(),
                'prenom'=> $user->getPrenom(),
                'email' => $user->getEmail(),
                'role'  => $user->getRole(),
            ];
        }, $users);

        return $this->twig->render($response, 'admin/users/list.html.twig', [
            'users'     => $userArray,
            'user_role' => $this->session->get('user')['role'] ?? null,
        ]);
    }

    public function showCreateForm(Request $request, Response $response, $args)
    {
        return $this->twig->render($response, 'admin/users/create.html.twig', [
            'user_role' => $this->getUserRole()
        ]);
    }

    public function showEditForm(Request $request, Response $response, $args)
    {
        $userId = (int) $args['id'];
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        
        if (!$user) {
            $this->session->set('error', 'Utilisateur non trouvé');
            return $response->withHeader('Location', '/admin/users')->withStatus(302);
        }

        return $this->twig->render($response, 'admin/users/edit.html.twig', [
            'user' => $user,
            'user_role' => $this->getUserRole()
        ]);
    }

    public function editUser(Request $request, Response $response, $args)
{
    $userId = (int) $args['id'];
    $user = $this->entityManager->getRepository(User::class)->find($userId);
    
    if (!$user) {
        // Si l'utilisateur n'existe pas, redirection vers la liste des utilisateurs
        $this->session->set('error', 'Utilisateur non trouvé');
        return $response->withHeader('Location', '/admin/users')->withStatus(302);
    }

    $data = $request->getParsedBody();
    
    // Validation
    if (empty($data['email'])) {
        $this->session->set('error', 'Email est requis');
        return $response->withHeader('Location', '/admin/users/edit/'.$userId)->withStatus(302);
    }
    
    // Mise à jour des champs
    $user->setEmail($data['email']);
    
    if (!empty($data['password'])) {
        $user->setPassword($data['password']);
    }
    
    $user->setRole($data['role'] ?? $user->getRole());

    // Sauvegarde dans la base de données
    $this->entityManager->flush();

    // Redirection avec message de succès
    $this->session->set('flash', ['type' => 'success', 'message' => 'Utilisateur mis à jour']);
    return $response->withHeader('Location', '/admin/users')->withStatus(302);
}

public function deleteUser(Request $request, Response $response, $args)
{
    $userId = (int) $args['id'];
    $user = $this->entityManager->getRepository(User::class)->find($userId);
    
    if (!$user) {
        // Si l'utilisateur n'existe pas, redirection vers la liste des utilisateurs
        $this->session->set('error', 'Utilisateur non trouvé');
        return $response->withHeader('Location', '/admin/users')->withStatus(302);
    }

    // Suppression de l'utilisateur
    $this->entityManager->remove($user);
    $this->entityManager->flush();

    // Redirection avec message de succès
    $this->session->set('flash', ['type' => 'success', 'message' => 'Utilisateur supprimé']);
    return $response->withHeader('Location', '/admin/users')->withStatus(302);
}
}