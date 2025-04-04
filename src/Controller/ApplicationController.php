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

    // Ajoutez cette méthode à votre ApplicationController
    private function jsonResponse(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    public function checkApplication(Request $request, Response $response, array $args): Response
    {
        $user = $this->session->get('user');
        if (!$user) {
            return $this->jsonResponse($response, ['error' => 'Unauthorized'], 401);
        }

        $hasApplied = $this->em->getRepository(Application::class)->count([
            'user' => $user['id'],
            'offer' => $args['id']
        ]) > 0;

        return $this->jsonResponse($response, ['hasApplied' => $hasApplied]);
    }

    public function submitApplication(Request $request, Response $response, array $args): Response
    {
        // 1. Vérification utilisateur connecté
        $user = $this->session->get('user');
        if (!$user) {
            return $this->jsonResponse($response, ['error' => 'Unauthorized'], 401);
        }

        // Vérification si déjà postulé
        $alreadyApplied = $this->em->getRepository(Application::class)->count([
            'user' => $user['id'], 
            'offer' => $args['id']
        ]) > 0;

        if ($alreadyApplied) {
            return $this->jsonResponse($response, [
                'error' => 'Vous avez déjà postulé à cette offre'
            ], 400);
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
            return $this->jsonResponse($response, ['errors' => $errors], 400);
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

            // 7. Réponse uniforme
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Candidature envoyée avec succès !'
            ]);

        } catch (\Exception $e) {
            // 8. Gestion des erreurs
            return $this->jsonResponse($response, [
                'error' => 'Erreur lors de l\'envoi de la candidature'
            ], 500);
        }
    }

    
}