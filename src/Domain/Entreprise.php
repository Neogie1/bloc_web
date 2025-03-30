<?php
namespace App\Domain;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'entreprises')]
class Entreprise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $nom;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(name: 'nombre_stagiaires', type: 'integer', options: ['default' => 0])]
    private int $nombreStagiaires = 0;

    #[ORM\Column(name: 'moyenne_evaluations', type: 'float', nullable: true)]
    private ?float $moyenneEvaluations = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $secteur = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $pays = null;

    // GETTERS ET SETTERS COMPLETS
    public function getId(): ?int { return $this->id; }

    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): self { $this->email = $email; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): self { $this->telephone = $telephone; return $this; }

    public function getNombreStagiaires(): int { return $this->nombreStagiaires; }
    public function setNombreStagiaires(int $nombreStagiaires): self { $this->nombreStagiaires = $nombreStagiaires; return $this; }

    public function getMoyenneEvaluations(): ?float { return $this->moyenneEvaluations; }
    public function setMoyenneEvaluations(?float $moyenneEvaluations): self { $this->moyenneEvaluations = $moyenneEvaluations; return $this; }

    public function getSecteur(): ?string { return $this->secteur; }
    public function setSecteur(?string $secteur): self { $this->secteur = $secteur; return $this; }

    public function getVille(): ?string { return $this->ville; }
    public function setVille(?string $ville): self { $this->ville = $ville; return $this; }

    public function getPays(): ?string { return $this->pays; }
    public function setPays(?string $pays): self { $this->pays = $pays; return $this; }
}