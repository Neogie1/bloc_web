<?php
// src/Controller/WishlistController.php
namespace App\Controller;

use App\Domain\Wishlist;
use App\Domain\Offer;
use App\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SlimSession\Helper as Session;

class WishlistController
{
    public function __construct(
        private EntityManagerInterface $em,
        private Session $session
    ) {}

    public function toggle(Request $request, Response $response, array $args): Response
    {
        $user = $this->session->get('user');
        if (!$user) {
            return $this->jsonResponse($response, ['error' => 'Unauthorized'], 401);
        }

        $offer = $this->em->getRepository(Offer::class)->find($args['id']);
        if (!$offer) {
            return $this->jsonResponse($response, ['error' => 'Offer not found'], 404);
        }

        // Vérifier si l'offre est déjà en wishlist
        $wishlistItem = $this->em->getRepository(Wishlist::class)->findOneBy([
            'user' => $user['id'],
            'offer' => $offer->getId()
        ]);

        if ($wishlistItem) {
            // Supprimer si existe déjà
            $this->em->remove($wishlistItem);
            $action = 'removed';
        } else {
            // Ajouter si n'existe pas
            $wishlist = new Wishlist(
                $this->em->getReference(User::class, $user['id']),
                $offer
            );
            $this->em->persist($wishlist);
            $action = 'added';
        }

        $this->em->flush();

        return $this->jsonResponse($response, [
            'status' => 'success',
            'action' => $action,
            'wishlistCount' => $this->em->getRepository(Wishlist::class)->count(['user' => $user['id']])
        ]);
    }

    /**
     * Helper method to create JSON response
     */
    private function jsonResponse(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    public function checkMultiple(Request $request, Response $response): Response
{
    $user = $this->session->get('user');
    if (!$user) {
        return $this->jsonResponse($response, [], 401);
    }

    $data = $request->getParsedBody();
    $offerIds = $data['offerIds'] ?? [];
    
    $results = [];
    foreach ($offerIds as $offerId) {
        $exists = $this->em->getRepository(Wishlist::class)->findOneBy([
            'user' => $user['id'],
            'offer' => $offerId
        ]);
        $results[$offerId] = $exists !== null;
    }

    return $this->jsonResponse($response, $results);
}
}