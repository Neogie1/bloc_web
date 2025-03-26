<?php
namespace app\Domain;

// src/Domain/User.php

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

#[Entity, Table(name: 'users')]
final class User
{
    #[Id, Column(type: 'integer'), GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[Column(type: 'string', unique: true, nullable: false)]
    private string $email;

    #[Column(name: 'registered_at', type: 'datetimetz_immutable', nullable: false)]
    private DateTimeImmutable $registeredAt;

    #[Column(type: 'string', nullable: false)] // Colonne pour le mot de passe
    private string $password;

    // Constructeur modifié pour accepter un mot de passe en texte clair
    public function __construct(string $email, string $password)
    {
        $this->email = $email;
        $this->setPassword($password); // Utilisation du setter pour hacher le mot de passe
        $this->registeredAt = new DateTimeImmutable('now');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRegisteredAt(): DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    // Utilisation de cette méthode pour hacher le mot de passe avant de le sauvegarder
    public function setPassword(string $password): void
    {
        // Hachage du mot de passe avec bcrypt
        $this->password = password_hash($password, PASSWORD_BCRYPT);
    }
}
