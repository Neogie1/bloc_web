<?php
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

require_once __DIR__ . '/vendor/autoload.php';

$settings = require __DIR__ . '/settings.php';

if (!isset($settings['settings']['doctrine'])) {
    throw new RuntimeException('Configuration Doctrine manquante');
}

$doctrineConfig = $settings['settings']['doctrine'];

// Configuration du cache
$cache = $doctrineConfig['dev_mode'] ?
    new ArrayAdapter() :
    new FilesystemAdapter(directory: $doctrineConfig['cache_dir']);

// Configuration METADATA avec ATTRIBUTS
$config = ORMSetup::createAttributeMetadataConfiguration(
    $doctrineConfig['metadata_dirs'],
    $doctrineConfig['dev_mode'],
    null,
    $cache
);

// Connexion DBAL
$connection = DriverManager::getConnection($doctrineConfig['connection']);

// EntityManager final
return new EntityManager($connection, $config);