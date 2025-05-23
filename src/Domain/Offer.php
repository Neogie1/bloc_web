<?php
namespace App\Domain;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'offers')]
class Offer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[OneToMany(targetEntity: Wishlist::class, mappedBy: 'user')]
private Collection $wishlists;


    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'string', length: 255)]
    private string $company;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)] // <-- nullable: true
    private ?string $skills = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $location;

    #[ORM\Column(type: 'string', length: 100)]
    private string $salary;

    #[ORM\Column(type: 'datetime', options: ["default" => "CURRENT_TIMESTAMP"])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'string', length: 50)]
    private string $contractType;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->wishlists = new ArrayCollection();
    }

    // Getters
    public function getId(): ?int {
        return $this->id;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function getCompany(): string {
        return $this->company;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function getSkills(): string {
        return $this->skills;
    }

    public function getLocation(): string {
        return $this->location;
    }

    public function getSalary(): string {
        return $this->salary;
    }

    public function getCreatedAt(): \DateTimeInterface {
        return $this->createdAt;
    }

    public function getContractType(): string {
        return $this->contractType;
    }

    // Setters
    public function setTitle(string $title): self {
        $this->title = $title;
        return $this;
    }

    public function setCompany(string $company): self {
        $this->company = $company;
        return $this;
    }

    public function setDescription(?string $description): self {
        $this->description = $description;
        return $this;
    }

    public function setSkills(string $skills): self {
        $this->skills = $skills;
        return $this;
    }

    public function setLocation(string $location): self {
        $this->location = $location;
        return $this;
    }

    public function setSalary(string $salary): self {
        $this->salary = $salary;
        return $this;
    }

    public function setContractType(string $contractType): self {
        $this->contractType = $contractType;
        return $this;
    }

    public function isInWishlist(?int $userId): bool
    {
        if ($userId === null) {
            return false;
        }
    
        foreach ($this->wishlists as $wishlist) {
            if ($wishlist->getUser()->getId() === $userId) {
                return true;
            }
        }
        return false;
    }

    public function getWishlists(): Collection
{
    return $this->wishlists;
}

public function hasUserApplied(int $userId): bool
{
    foreach ($this->applications as $application) {
        if ($application->getUser()->getId() === $userId) {
            return true;
        }
    }
    return false;
}

}