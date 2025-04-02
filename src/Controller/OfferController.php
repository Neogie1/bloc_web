<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Doctrine\ORM\EntityManagerInterface;
use Slim\Views\Twig;
use App\Domain\Offer;
use Slim\Routing\RouteContext;
use App\Domain\User;

class OfferController
{
    private $entityManager;
    private $view;
    private $session;

    public function __construct(EntityManagerInterface $entityManager, Twig $view, $session)
    {
        $this->entityManager = $entityManager;
        $this->view = $view;
        $this->session = $session;
    }

    public function list(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
        $limit = 9;
        $offset = ($page - 1) * $limit;

        $repository = $this->entityManager->getRepository(Offer::class);
        
        // Requête paginée avec tri
        $offers = $repository->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $totalOffers = $repository->count([]);
        $totalPages = max(1, ceil($totalOffers / $limit));

        return $this->view->render($response, 'offres.html.twig', [
            'offers' => $offers,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_offers' => $totalOffers,
            'is_search' => false,
            'searchQuery' => null
        ]);
    }

    public function search(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $searchTerm = $queryParams['q'] ?? '';
        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
        $limit = 9;
        $offset = ($page - 1) * $limit;

        $qb = $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(Offer::class, 'o')
            ->orderBy('o.createdAt', 'DESC');

        if (!empty($searchTerm)) {
            $qb->where($qb->expr()->orX(
                $qb->expr()->like('o.title', ':term'),
                $qb->expr()->like('o.company', ':term'),
                $qb->expr()->like('o.skills', ':term')
            ))->setParameter('term', '%'.$searchTerm.'%');
        }

        // Compte total pour pagination
        $countQb = clone $qb;
        $countQb->select('COUNT(o.id)');
        $totalOffers = (int)$countQb->getQuery()->getSingleScalarResult();

        // Pagination
        $offers = $qb->setFirstResult($offset)
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();

        $totalPages = max(1, ceil($totalOffers / $limit));

        return $this->view->render($response, 'offres.html.twig', [
            'offers' => $offers,
            'searchQuery' => $searchTerm,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_offers' => $totalOffers,
            'is_search' => !empty($searchTerm)
        ]);
    }

    public function addToWishlist(int $offerId): Response
    {
        $user = $this->getUser(); // Récupère l'utilisateur connecté
        $offer = $this->offerRepository->find($offerId);

        if ($offer) {
            $wishlistItem = new Wishlist($user, $offer->getJobTitle(), $offer->getCompany(), $offer->getLocation(), $offer->getSkills());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($wishlistItem);
            $entityManager->flush();
        }

        return $this->redirectToRoute('offer_list'); // Ou recharger la page actuelle
    }

    public function createForm(Request $request, Response $response): Response {
        $user = $this->session->get('user');
        
        if (!in_array($user['role'], [User::ROLE_ADMIN, User::ROLE_PILOTE])) {
            return $response->withHeader('Location', '/dashboard')->withStatus(403);
        }
        
        return $this->view->render($response, 'admin/offres/create.html.twig', [
            'user_role' => $user['role'],
            'old_input' => $this->session->get('old_input', []),
            'errors' => $this->session->get('form_errors', [])
        ]);
    }

    public function create(Request $request, Response $response): Response {
        $user = $this->session->get('user');
        $data = $request->getParsedBody();
        
        if (!in_array($user['role'], [User::ROLE_ADMIN, User::ROLE_PILOTE])) {
            return $response->withHeader('Location', '/dashboard')->withStatus(403);
        }
        
        // Validation des données
        $errors = [];
        $oldInput = $data;
        
        if (empty($data['title'])) {
            $errors['title'] = 'Le titre est obligatoire';
        }
        
        if (!empty($errors)) {
            $this->session->set('old_input', $oldInput);
            $this->session->set('form_errors', $errors);
            return $response->withHeader('Location', $this->urlFor('offres.create.form'));
        }
        
        // Création de l'offre
        $offre = new Offer();
        $offre->setTitle($data['title'] ?? '');
        $offre->setDescription($data['description'] ?? null);
        $offre->setCompany($data['company'] ?? null);
        $offre->setSkills($data['skills'] ?? null);
        $offre->setLocation($data['location'] ?? null);
        $offre->setSalary($data['salary'] ?? null);
        $offre->setContractType($data['contractType'] ?? null);

        $this->entityManager->persist($offre);
        $this->entityManager->flush();
        
        $this->session->set('flash', [
            'type' => 'success',
            'message' => 'Offre créée avec succès'
        ]);
        
        // Redirige vers le formulaire avec un message flash
        $this->session->set('flash', [
            'type' => 'success',
            'message' => 'Offre créée avec succès'
        ]);

        // Obtenir le routeur et générer l'URL
        $routeContext = RouteContext::fromRequest($request);
        $routeParser = $routeContext->getRouteParser();
        $url = $routeParser->urlFor('offres.create.form');

        // Redirection après la création
        return $response->withHeader('Location', $url)->withStatus(302);
    }


    public function editForm(Request $request, Response $response, array $args): Response {
        $id = (int) $args['id'];
        $offre = $this->entityManager->getRepository(Offer::class)->find($id);
    
        if (!$offre) {
            throw new HttpNotFoundException($request, "Offre non trouvée");
        }
    
        return $this->view->render($response, 'admin/offres/edit.html.twig', [
            'offre' => $offre,
            'old_input' => $this->session->get('old_input', []),
            'errors' => $this->session->get('form_errors', [])
        ]);
    }

    public function edit(Request $request, Response $response, array $args): Response {
        $id = (int) $args['id'];
        $data = $request->getParsedBody();
        $offre = $this->entityManager->getRepository(Offer::class)->find($id);
    
        if (!$offre) {
            $this->session->set('flash', [
                'type' => 'error',
                'message' => 'Offre non trouvée'
            ]);
            return $response->withHeader('Location', '/offres')->withStatus(302);
        }
    
        // Validation des données
        $errors = [];
        $oldInput = $data;
    
        if (empty($data['titre'])) {
            $errors['titre'] = 'Le titre est obligatoire';
        }
    
        if (!empty($errors)) {
            $this->session->set('form_errors', $errors);
            $this->session->set('old_input', $oldInput);
            return $response->withHeader('Location', "/offres/$id/edit")->withStatus(302);
        }
    
                // Mise à jour de l'offre
                try {
                    $offre->setTitre($data['titre'] ?? '');
                    $offre->setDescription($data['description'] ?? null);
                    $offre->setEntreprise($data['entreprise'] ?? null);
            
                    $this->entityManager->flush();
            
                    $this->session->set('flash', [
                        'type' => 'success',
                        'message' => 'Offre modifiée avec succès'
                    ]);
                } catch (\Exception $e) {
                    $this->session->set('flash', [
                        'type' => 'error',
                        'message' => 'Erreur lors de la modification : ' . $e->getMessage()
                    ]);
                }
            
                return $response->withHeader('Location', '/offres')->withStatus(302);
            }
        
            public function delete(Request $request, Response $response, array $args): Response {
                $id = (int) $args['id'];
                $offre = $this->entityManager->getRepository(Offer::class)->find($id);
            
                if (!$offre) {
                    $this->session->set('flash', [
                        'type' => 'error',
                        'message' => 'Offre non trouvée'
                    ]);
                    return $response->withHeader('Location', '/admin/offres/admin')->withStatus(302);
                }
            
                try {
                    $this->entityManager->remove($offre);
                    $this->entityManager->flush();
                    
                    $this->session->set('flash', [
                        'type' => 'success',
                        'message' => 'Offre supprimée avec succès'
                    ]);
                } catch (\Exception $e) {
                    $this->session->set('flash', [
                        'type' => 'error',
                        'message' => 'Erreur lors de la suppression de l\'offre'
                    ]);
                }
            
                return $response->withHeader('Location', '/admin/offres/admin')->withStatus(302);
            }
            

            public function adminList(Request $request, Response $response): Response
            {
                $repository = $this->entityManager->getRepository(Offer::class);
                $offres = $repository->findAll();
            
                return $this->view->render($response, 'admin/offres/list.html.twig', [
                    'offres' => $offres,
                    'user_role' => $this->session->get('user')['role']
                ]);
            }
            
}
        