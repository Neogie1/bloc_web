<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Domain\User;

final class UserService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function signUp(string $email): User
    {
        $user = new User($email);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
