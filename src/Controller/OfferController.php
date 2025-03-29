<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Doctrine\ORM\EntityManagerInterface;
use Slim\Views\Twig;
use App\Domain\Offer;

class OfferController
{
    private $entityManager;
    private $view;

    public function __construct(EntityManagerInterface $entityManager, Twig $view)
    {
        $this->entityManager = $entityManager;
        $this->view = $view;
    }

    public function list(Request $request, Response $response): Response
    {
        $page = $request->getQueryParam('page', 1);
        $limit = 9; // 3 lignes × 3 offres
        $offset = ($page - 1) * $limit;

        $repository = $this->entityManager->getRepository(Offer::class);
        $offers = $repository->findBy([], null, $limit, $offset);
        $totalOffers = $repository->count([]);
        $totalPages = ceil($totalOffers / $limit);

        return $this->view->render($response, 'offres.html.twig', [
            'offers' => $offers,
            'current_page' => $page,
            'total_pages' => $totalPages
        ]);
    }

    public function search(Request $request, Response $response): Response
    {
        // Récupère le terme de recherche
        $queryParams = $request->getQueryParams();
        $searchTerm = $queryParams['q'] ?? '';

        // Requête de base sans filtre si vide
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('o')
           ->from(Offer::class, 'o');

        // Ajoute les conditions de recherche si terme non vide
        if (!empty($searchTerm)) {
            $qb->where($qb->expr()->orX(
                $qb->expr()->like('o.title', ':term'),
                $qb->expr()->like('o.company', ':term'),
                $qb->expr()->like('o.skills', ':term')
            ))
            ->setParameter('term', '%'.$searchTerm.'%');
        }

        // Exécute la requête
        $offers = $qb->getQuery()->getResult();

        // Rend la vue avec les résultats
        return $this->view->render($response, 'offres.html.twig', [
            'offers' => $offers,
            'searchQuery' => $searchTerm
        ]);
    }
}