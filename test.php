<?php

require __DIR__ . '/vendor/autoload.php';

$container = require __DIR__ . '/bootstrap.php';

/** @var App\Service\UserService $userService */
$userService = $container->get(App\Service\UserService::class);

// Ajouter un utilisateur
$user = $userService->signUp("test@example.com");

echo "Utilisateur ajouté : " . $user->getEmail() . PHP_EOL;
