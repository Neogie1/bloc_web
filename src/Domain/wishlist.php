<?php

namespace App\Domain;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use App\Domain\User;
use App\Domain\Offer; // Supposons que vous avez une entité Offer

#[ORM\Entity]
#[ORM\Table(name: 'wishlists')]
class Wishlist
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'wishlists')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Offer::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Offer $offer;

    #[ORM\Column(name: 'added_at', type: 'datetimetz_immutable', nullable: false)]
    private DateTimeImmutable $addedAt;

    public function __construct(User $user, Offer $offer)
    {
        $this->user = $user;
        $this->offer = $offer;
        $this->addedAt = new DateTimeImmutable('now');
    }

    // Getters
    public function getUser(): User
    {
        return $this->user;
    }

    public function getOffer(): Offer
    {
        return $this->offer;
    }

    public function getAddedAt(): DateTimeImmutable
    {
        return $this->addedAt;
    }

    // Setters
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function setOffer(Offer $offer): void
    {
        $this->offer = $offer;
    }
}