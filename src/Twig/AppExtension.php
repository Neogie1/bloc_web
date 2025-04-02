<?php
// Créez ce fichier dans src/Twig/AppExtension.php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use SlimSession\Helper as SessionHelper;

class AppExtension extends AbstractExtension
{
    private $session;

    public function __construct(SessionHelper $session)
    {
        $this->session = $session;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('is_logged_in', [$this, 'isLoggedIn']),
            new TwigFunction('is_admin', [$this, 'isAdmin']),
            new TwigFunction('is_pilote', [$this, 'isPilote']),
            new TwigFunction('get_user', [$this, 'getUser']),
            new TwigFunction('base_path', [$this, 'getBasePath']),
        ];
    }

    public function isLoggedIn()
    {
        return $this->session->exists('user');
    }

    public function isAdmin()
    {
        $user = $this->session->get('user');
        return $user && $user['role'] === 'admin';
    }

    public function isPilote()
    {
        $user = $this->session->get('user');
        return $user && $user['role'] === 'pilote';
    }

    public function getUser()
    {
        return $this->session->get('user');
    }
    
    public function getBasePath()
    {
        // Retourne le chemin de base de l'application
        return '';
    }
}
