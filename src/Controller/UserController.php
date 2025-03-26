<?php

namespace App\Controller;

use App\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class UserController
{
    private $entityManager;

    // Injection de dépendances pour l'EntityManager
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    // Méthode pour créer un utilisateur
    public function createUser(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();

        // Récupérer les informations de l'utilisateur
        $email = $data['email'];
        $password = $data['password'];  // Mot de passe en texte clair

        // Créer un nouvel utilisateur
        $user = new User($email, $password);

        // Sauvegarder l'utilisateur dans la base de données
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Retourner une réponse JSON indiquant que l'utilisateur a été créé
        return $response->withJson(['message' => 'User created successfully'], 201);
    }

    // Méthode pour la connexion
    public function login(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $email = $data['email'];
        $password = $data['password'];

        // Récupérer l'utilisateur depuis la base de données en fonction de l'email
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        // Vérifier si l'utilisateur existe
        if (!$user) {
            return $response->withJson(['error' => 'Invalid email or password'], 401);
        }

        // Vérifier si le mot de passe est correct
        if (password_verify($password, $user->getPassword())) {
            return $response->withJson(['message' => 'Login successful']);
        } else {
            return $response->withJson(['error' => 'Invalid email or password'], 401);
        }
    }
}
