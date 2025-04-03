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
        // 1. Vérification utilisateur connecté
        $user = $this->session->get('user');
        if (!$user) {
            $this->session->set('flash', [
                'type' => 'error',
                'message' => 'Veuillez vous connecter'
            ]);
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        // 2. Récupération des fichiers
        $uploadedFiles = $request->getUploadedFiles();
        
        // 3. Validation des fichiers
        $errors = [];
        $allowedTypes = ['application/pdf'];

        foreach (['cv', 'cover_letter'] as $field) {
            $file = $uploadedFiles[$field] ?? null;
            
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

        // 4. Si erreurs, retourner avec messages
        if (!empty($errors)) {
            $this->session->set('flash', [
                'type' => 'error',
                'messages' => $errors
            ]);
            return $response->withHeader('Location', '/offres/list')->withStatus(302);
        }

        try {
            // 5. Enregistrement des fichiers
            $uploadDir = __DIR__ . '/../../public/uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $cvFilename = 'cv_' . bin2hex(random_bytes(8)) . '.pdf';
            $coverLetterFilename = 'letter_' . bin2hex(random_bytes(8)) . '.pdf';

            $uploadedFiles['cv']->moveTo($uploadDir . $cvFilename);
            $uploadedFiles['cover_letter']->moveTo($uploadDir . $coverLetterFilename);

            // 6. Création de la candidature
            $application = new Application();
            $application->setUser($this->em->getReference(User::class, $user['id']))
                       ->setOffer($this->em->getReference(Offer::class, $args['id']))
                       ->setCvPath('/uploads/' . $cvFilename)
                       ->setCoverLetterPath('/uploads/' . $coverLetterFilename)
                       ->setAppliedAt(new \DateTime());

            $this->em->persist($application);
            $this->em->flush();

            // 7. Succès - prépare la réponse
            $isAjax = strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';

            if ($isAjax) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Candidature envoyée avec succès !'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            }

            $this->session->set('flash', [
                'type' => 'success',
                'message' => 'Candidature envoyée avec succès !'
            ]);
            return $response->withHeader('Location', '/offres/list')->withStatus(302);

        } catch (\Exception $e) {
            // 8. Gestion des erreurs
            $isAjax = strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';

            if ($isAjax) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi de la candidature'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }

            $this->session->set('flash', [
                'type' => 'error',
                'message' => 'Erreur lors de l\'envoi de la candidature'
            ]);
            return $response->withHeader('Location', '/offres/list')->withStatus(302);
        }
    }
}