<?php
namespace App\Domain;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity, Table(name: 'wishlists')]
final class Wishlist
{
    #[Id, Column(type: 'integer'), GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'wishlists')]
    private User $user;

    #[Column(type: 'string', nullable: false)]
    private string $jobTitle;

    #[Column(type: 'string', nullable: false)]
    private string $company;

    #[Column(type: 'string', nullable: false)]
    private string $location;

    #[Column(type: 'string', nullable: true)]
    private ?string $skills;

    #[Column(name: 'added_at', type: 'datetimetz_immutable', nullable: false)]
    private DateTimeImmutable $addedAt;

    public function __construct(User $user, string $jobTitle, string $company, string $location, ?string $skills = null)
    {
        $this->user = $user;
        $this->jobTitle = $jobTitle;
        $this->company = $company;
        $this->location = $location;
        $this->skills = $skills;
        $this->addedAt = new DateTimeImmutable('now');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getJobTitle(): string
    {
        return $this->jobTitle;
    }

    public function getCompany(): string
    {
        return $this->company;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getSkills(): ?string
    {
        return $this->skills;
    }

    public function getAddedAt(): DateTimeImmutable
    {
        return $this->addedAt;
    }
}
