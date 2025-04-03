<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Doctrine\ORM\EntityManagerInterface;
use Slim\Views\Twig;
use SlimSession\Helper as Session;
use App\Domain\Application;
use App\Domain\Offer;
use App\Domain\User;

class ApplicationController
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private Session $session
    ) {}

    public function submitApplication(Request $request, Response $response, array $args): Response
    {
        // Vérification utilisateur connecté
        $user = $this->session->get('user');
        if (!$user) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        // Validation des fichiers
        $uploadedFiles = $request->getUploadedFiles();
        $errors = $this->validateFiles($uploadedFiles);

        if (!empty($errors)) {
            $this->session->set('flash', ['type' => 'error', 'messages' => $errors]);
            return $response->withHeader('Location', '/offres/list')->withStatus(302);
        }

        try {
            // Enregistrement fichiers
            $cvPath = $this->saveFile($uploadedFiles['cv'], 'cv');
            $coverLetterPath = $this->saveFile($uploadedFiles['cover_letter'], 'cover_letter');

            // Création candidature
            $application = new Application();
            $application->setUser($this->em->getReference(\App\Entity\User::class, $user['id']))
                       ->setOffer($this->em->getReference(\App\Entity\Offer::class, $args['id']))
                       ->setCvPath($cvPath)
                       ->setCoverLetterPath($coverLetterPath)
                       ->setAppliedAt(new \DateTime());

            $this->em->persist($application);
            $this->em->flush();

            $this->session->set('flash', ['type' => 'success', 'message' => 'Candidature envoyée !']);

        } catch (\Exception $e) {
            $this->session->set('flash', ['type' => 'error', 'message' => 'Erreur lors de l\'envoi']);
        }

        return $response->withHeader('Location', '/offres/list')->withStatus(302);
    }

    private function validateFiles(array $files): array
    {
        $errors = [];
        $allowedTypes = ['application/pdf'];

        foreach (['cv', 'cover_letter'] as $field) {
            $file = $files[$field] ?? null;
            
            if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
                $errors[$field] = 'Fichier requis';
                continue;
            }

            if ($file->getSize() > 2 * 1024 * 1024) {
                $errors[$field] = 'Fichier trop volumineux (max 2MB)';
            }

            if (!in_array($file->getClientMediaType(), $allowedTypes)) {
                $errors[$field] = 'Seuls les PDF sont acceptés';
            }
        }

        return $errors;
    }

    private function saveFile($uploadedFile, string $prefix): string
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $filename = sprintf('%s_%s.%s', $prefix, bin2hex(random_bytes(8)), $extension);
        $uploadedFile->moveTo(__DIR__ . '/../../public/uploads/' . $filename);
        return $filename;
    }
}