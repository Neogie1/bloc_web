<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Domain\Entreprise;
use Doctrine\ORM\EntityManagerInterface;
use App\Domain\User;

class EntrepriseController
{
    private $entityManager;
    private $view;
    private $session;

    public function delete(Request $request, Response $response, array $args): Response
    {
        $user = $this->session->get('user');
        
        // Vérification des permissions
        if (!in_array($user['role'], [User::ROLE_ADMIN, User::ROLE_PILOTE])) {
            $this->session->set('flash', [
                'type' => 'error',
                'message' => 'Vous n\'avez pas les droits pour effectuer cette action'
            ]);
            return $response->withHeader('Location', '/entreprises')
                           ->withStatus(403);
        }

        $id = (int)$args['id'];
        $entreprise = $this->entityManager->getRepository(Entreprise::class)->find($id);

        if (!$entreprise) {
            $this->session->set('flash', [
                'type' => 'error',
                'message' => 'Entreprise non trouvée'
            ]);
            return $response->withHeader('Location', '/entreprises')
                           ->withStatus(404);
        }

        try {
            $this->entityManager->remove($entreprise);
            $this->entityManager->flush();
            
            $this->session->set('flash', [
                'type' => 'success',
                'message' => 'Entreprise supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            $this->session->set('flash', [
                'type' => 'error',
                'message' => 'Erreur lors de la suppression de l\'entreprise'
            ]);
        }

        return $response->withHeader('Location', '/entreprises')
                       ->withStatus(302);
    }

    public function __construct(EntityManagerInterface $entityManager, $view, $session)
    {
        $this->entityManager = $entityManager;
        $this->view = $view;
        $this->session = $session;
    }

    public function list(Request $request, Response $response): Response
    {
        $user = $this->session->get('user');
        $repository = $this->entityManager->getRepository(Entreprise::class);
        $entreprises = $repository->findAll();
    
        return $this->view->render($response, 'admin/entreprises/list.html.twig', [
            'entreprises' => $entreprises,
            'user_role' => $user['role'],
            'flash' => $this->session->get('flash')
        ]);
    }

    public function createForm(Request $request, Response $response): Response
    {
        $user = $this->session->get('user');
        
        if (!in_array($user['role'], [User::ROLE_ADMIN, User::ROLE_PILOTE])) {
            return $response->withHeader('Location', '/dashboard')
                           ->withStatus(403);
        }
        
        return $this->view->render($response, 'admin/entreprises/create.html.twig', [
            'user_role' => $user['role'],
            'old_input' => $this->session->get('old_input', []),
            'errors' => $this->session->get('form_errors', [])
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $user = $this->session->get('user');
        $data = $request->getParsedBody();
        
        if (!in_array($user['role'], [User::ROLE_ADMIN, User::ROLE_PILOTE])) {
            return $response->withHeader('Location', '/dashboard')
                           ->withStatus(403);
        }
        
        // Validation des données
        $errors = [];
        $oldInput = $data;
        
        // Validation du nom
        if (empty($data['nom'])) {
            $errors['nom'] = 'Le nom est obligatoire';
        }
        
        // Validation de l'URL si fournie
        if (!empty($data['site_web'])) {
            $siteWeb = $data['site_web'];
            
            // Ajoute http:// si absent
            if (!preg_match("~^(?:f|ht)tps?://~i", $siteWeb)) {
                $siteWeb = "http://" . $siteWeb;
            }
            
            if (!filter_var($siteWeb, FILTER_VALIDATE_URL)) {
                $errors['site_web'] = 'Veuillez entrer une URL valide (ex: http://www.exemple.com)';
            } else {
                $data['site_web'] = $siteWeb; // On conserve l'URL formatée
            }
        }
        
        // Si erreurs, on redirige avec les messages
        if (!empty($errors)) {
            $this->session->set('old_input', $oldInput);
            $this->session->set('form_errors', $errors);
            return $response->withHeader('Location', '/entreprises/create')
                           ->withStatus(302);
        }
        
        // Création de l'entreprise
        $entreprise = new Entreprise();
        $entreprise->setNom($data['nom'] ?? '');
        $entreprise->setDescription($data['description'] ?? null);
        $entreprise->setSecteur($data['secteur'] ?? null);
        $entreprise->setVille($data['ville'] ?? null);
        $entreprise->setPays($data['pays'] ?? null);
        $entreprise->setEmail($data['email'] ?? null);
        $entreprise->setTelephone($data['telephone'] ?? null);
        
        $this->entityManager->persist($entreprise);
        $this->entityManager->flush();
        
        // Message de succès
        $this->session->set('flash', [
            'type' => 'success',
            'message' => 'Entreprise créée avec succès'
        ]);
        
        return $response->withHeader('Location', '/entreprises')
                       ->withStatus(302);
    }

    
    public function editForm(Request $request, Response $response, array $args): Response
    {
        $user = $this->session->get('user');
        $id = (int)$args['id'];
        
        // Vérification des permissions
        if (!in_array($user['role'], ['admin', 'pilote'])) {
            return $response->withHeader('Location', '/entreprises')->withStatus(403);
        }
    
        // Récupération de l'entreprise
        $entreprise = $this->entityManager->getRepository(Entreprise::class)->find($id);
        
        if (!$entreprise) {
            return $response->withHeader('Location', '/entreprises')->withStatus(404);
        }
    
        // Rendu du formulaire
        return $this->view->render($response, 'admin/entreprises/edit.html.twig', [
            'entreprise' => $entreprise,
            'user_role' => $user['role']
        ]);
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $entityManager = $this->entityManager;
        $entreprise = $entityManager->getRepository(Entreprise::class)->find($id);
    
        if (!$entreprise) {
            throw new HttpNotFoundException($request, "Entreprise non trouvée");
        }
    
        if ($request->getMethod() === 'POST') {
            // Traitement du formulaire
            $data = $request->getParsedBody();
            
            // Valider et mettre à jour l'entreprise
            $entreprise->setNom($data['nom']);
            // autres champs...
    
            $entityManager->flush();
    
            // Redirection après succès
            return $response->withHeader('Location', '/entreprises/' . $id)
                           ->withStatus(302);
        }
    
        // Affichage du formulaire
        return $this->view->render($response, 'entreprise/edit.html.twig', [
            'entreprise' => $entreprise,
        ]);
    }

    public function evaluate(Request $request, Response $response, array $args): Response
    {
        // Implémentez cette méthode
    }

    public function stats(Request $request, Response $response, array $args): Response
    {
        // Implémentez cette méthode
    }
}