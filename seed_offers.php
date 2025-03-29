<?php
require __DIR__.'/bootstrap.php';

// Récupération de l'EntityManager
$entityManager = require __DIR__.'/bootstrap.php';

// Données des offres à insérer
$offers = [
    [
        'title' => 'Développeur Symfony',
        'company' => 'TechCorp',
        'description' => 'Poste en CDI avec télétravail',
        'skills' => 'PHP, Symfony, MySQL'
    ],
    [
        'title' => 'Designer UX',
        'company' => 'WebArts',
        'description' => 'Stage de 6 mois',
        'skills' => 'Figma, Adobe XD'
    ],
    [
        'title' => 'designer Agile',
        'company' => 'ProjetPlus',
        'description' => 'CDI avec avantages',
        'skills' => 'Scrum, Management'
    ]
];

foreach ($offers as $offerData) {
    $offer = new App\Domain\Offer();
    $offer->setTitle($offerData['title']);
    $offer->setCompany($offerData['company']);
    $offer->setDescription($offerData['description']);
    $offer->setSkills($offerData['skills']);
    
    $entityManager->persist($offer);
}

try {
    $entityManager->flush();
    echo count($offers) . " offres ont été ajoutées avec succès !\n";
} catch (\Exception $e) {
    echo "Erreur lors de l'ajout des offres : " . $e->getMessage() . "\n";
}