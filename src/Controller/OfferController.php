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
}