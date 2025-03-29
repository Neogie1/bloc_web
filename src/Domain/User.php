<?php
namespace App\Domain;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity, Table(name: 'users')]
final class User
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_PILOTE = 'pilote';
    public const ROLE_ETUDIANT = 'etudiant';

    #[Id, Column(type: 'integer'), GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[Column(type: 'string', unique: true, nullable: false)]
    private string $email;

    #[Column(name: 'registered_at', type: 'datetimetz_immutable', nullable: false)]
    private DateTimeImmutable $registeredAt;

    #[Column(type: 'string', nullable: false)]
    private string $password;

    #[Column(type: 'string', length: 20, nullable: false)]
    private string $role;

    public function __construct(string $email, string $password, string $role = self::ROLE_ETUDIANT)
    {
        $this->email = $email;
        $this->setPassword($password);
        $this->setRole($role);
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

    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        if (!in_array($role, [self::ROLE_ADMIN, self::ROLE_PILOTE, self::ROLE_ETUDIANT])) {
            throw new \InvalidArgumentException("Rôle invalide. Les rôles valides sont : admin, pilote, etudiant");
        }
        $this->role = $role;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isPilote(): bool
    {
        return $this->role === self::ROLE_PILOTE;
    }

    public function isEtudiant(): bool
    {
        return $this->role === self::ROLE_ETUDIANT;
    }

    public function getRoleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Administrateur',
            self::ROLE_PILOTE => 'Pilote',
            self::ROLE_ETUDIANT => 'Étudiant',
            default => 'Inconnu',
        };
    }
}