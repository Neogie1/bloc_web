<?php
namespace App\Domain;

use Doctrine\ORM\Mapping as ORM;
use App\Domain\User;    // Chemin corrigé
use App\Domain\Offer;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Application
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Offer::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Offer $offer;

    #[ORM\Column(length: 255)]
    private string $cvPath;

    #[ORM\Column(length: 255)]
    private string $coverLetterPath;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $appliedAt;

    // Lifecycle callback pour définir automatiquement la date
    #[ORM\PrePersist]
    public function setAppliedAtValue(): void
    {
        $this->appliedAt = new \DateTime();
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getOffer(): Offer
    {
        return $this->offer;
    }

    public function getCvPath(): string
    {
        return $this->cvPath;
    }

    public function getCoverLetterPath(): string
    {
        return $this->coverLetterPath;
    }

    public function getAppliedAt(): \DateTime
    {
        return $this->appliedAt;
    }

    // Setters
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function setOffer(Offer $offer): self
    {
        $this->offer = $offer;
        return $this;
    }

    public function setCvPath(string $cvPath): self
    {
        $this->cvPath = $cvPath;
        return $this;
    }

    public function setCoverLetterPath(string $coverLetterPath): self
    {
        $this->coverLetterPath = $coverLetterPath;
        return $this;
    }

    public function setAppliedAt(\DateTime $appliedAt): self
    {
        $this->appliedAt = $appliedAt;
        return $this;
    }
}