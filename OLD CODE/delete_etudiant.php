<?php
session_start();

require_once __DIR__ . '/../includes/auth_check.php';

checkAuth('pilote');

$role = $_SESSION['role'];
$redirectPage = 'index.php';

if ($role === 'pilote') {
    $redirectPage = 'pilote.php';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Internnect</title>
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon" />
    <link rel="icon" href="assets/img/favicon-32x32.png" type="image/png" sizes="32x32" />
    <link rel="icon" href="assets/img/favicon-16x16.png" type="image/png" sizes="16x16" />

    <!-- Intégration de Bootstrap -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <!-- Intégration de Bootstrap Icons -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="assets/style.css" />
    <style>
      /* Styles personnalisés pour la page */
      .login-container {
        margin: 50px auto;
      }

      .login-body {
        position: relative;
        width: 100%; /* Largeur par défaut */
        max-width: 720px; /* Largeur maximale sur les grands écrans */
        min-height: auto !important;
        height: auto !important;
        margin: 20px auto;
        border: 1px solid #dddd;
        border-radius: 18px;
        overflow: auto !important;
        box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
      }

      .login-box {
        padding: 20px;
      }

      .login-title {
        font-size: 24px;
        font-weight: 700;
      }

      .login-form .form-control {
        border-radius: 50px;
        border: 1px solid #ccc;
        transition: border-color 0.3s, box-shadow 0.3s;
      }

      .login-form .form-control:focus {
        border-color: #8b5cf6;
        box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.2);
        outline: none;
      }

      .btn-primary {
        background-color: rgb(155, 135, 245);
        border: none;
        border-radius: 50px;
      }

      .btn-primary:hover {
        background-color: rgb(130, 110, 220);
      }

      .login-footer {
        font-size: 14px;
      }

      .signup-link {
        color: rgb(155, 135, 245);
        text-decoration: none;
      }

      .signup-link:hover {
        text-decoration: underline;
      }

      @media (max-width: 767px) {
        .login-body {
          width: 100%; /* Pleine largeur sur les petits écrans */
          margin: 10px auto;
        }

        .login-box {
          width: 100%;
        }
      }
    </style>
</head>
<body style="background-color: #fefcfd">
 <!-- Navbar pour PC et tablettes (visible à partir de lg) -->
<nav class="navbar navbar-expand-lg navbar-light bg-white d-none d-lg-flex" style="height: 4rem">
  <div class="container">
  <a class="navbar-brand" href="<?php echo $redirectPage; ?>">internnect</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link mx-3 d-flex align-items-center" href="#">
            <i class="bi bi-search" style="font-size: 18px"></i>
            <span class="ms-2">Rechercher</span>
          </a>
        </li>
        <li class="nav-item mx-3">
          <a class="nav-link" href="#">
            <i class="bi bi-star" style="font-size: 18px; margin-right: 0.5rem"></i>Favoris
          </a>
        </li>
        <li class="nav-item mx-3">
          <a class="nav-link" href="#">
            <i class="bi bi-building" style="font-size: 18px; margin-right: 0.5rem"></i>Entreprises
          </a>
        </li>
        <li class="nav-item d-flex align-items-center mx-2">
          <div style="width: 1px; height: 24px; background-color: rgb(229, 231, 235);"></div>
        </li>
        <li class="nav-item mx-3">
          <a class="nav-link" href="#">
            <i class="bi bi-briefcase" style="font-size: 18px; margin-right: 0.5rem"></i>Déposer une offre
          </a>
        </li>
        <li class="nav-item dropdown mx-3">
          <a class="nav-link btn btn-primary rounded-pill text-white px-3 dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: rgb(155, 135, 245);">
            <i class="bi bi-person" style="font-size: 18px"></i> Profil
          </a>
          <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
            <li><a class="dropdown-item" href="#">Mon Compte</a></li>
            <li><a class="dropdown-item" href="create_etudiant.php">Créer un compte</a></li>
            <li><a class="dropdown-item" href="delete_etudiant.php">Supprimer un compte </a></li>
            <li><a class="dropdown-item" href="#">Paramètres</a></li>
            <li><a class="dropdown-item" href="logout.php">Déconnexion</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Navbar pour Mobile (visible uniquement sur les petits écrans) -->
<nav class="navbar navbar-expand-lg navbar-light bg-white d-lg-none">
  <div class="container">
  <a class="navbar-brand" href="<?php echo $redirectPage; ?>">internnect</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mobileNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mobileNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="#">Rechercher</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">Favoris</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">Entreprises</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">Déposer une offre</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link btn btn-primary rounded-pill text-white px-3 dropdown-toggle" href="#" role="button" id="dropdownMenuLinkMobile" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: rgb(155, 135, 245);">
            <i class="bi bi-person" style="font-size: 18px"></i> Profil
          </a>
          <ul class="dropdown-menu" aria-labelledby="dropdownMenuLinkMobile">
            <li><a class="dropdown-item" href="#">Mon Compte</a></li>
            <li><a class="dropdown-item" href="create_etudiant.php">Créer un compte</a></li>
            <li><a class="dropdown-item" href="delete_etudiant.php">Supprimer un compte </a></li>
            <li><a class="dropdown-item" href="#">Paramètres</a></li>
            <li><a class="dropdown-item" href="logout.php">Déconnexion</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

    <!-- Contenu principal -->
    <div class="login-container">
      <div class="login-body d-md-flex align-items-center justify-content-center">
        <div class="login-box d-flex flex-column h-100 align-items-center justify-content-center">
          <div class="mt-5 text-center">
            <div class="text-center">
              <img
                src="../assets/img/internnect.png"
                alt="Logo Internnect"
                class="w-12 h-12 rounded-lg object-contain bg-gray-50 mb-4"
                style="flex-shrink: 0;"
              />
              <h3 class="login-title">Supprimez un compte étudiant</h3>
              <form class="login-form w-100" method="POST" action="">
                <div class="mb-3">
                  <label for="email" class="form-label">Adresse e-mail</label>
                  <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    placeholder="example@example.com"
                    required
                  />
                </div>
                <div class="mb-3">
                  <label for="text" class="form-label">Prénom de l'étudiant</label>
                  <input
                    type="text"
                    class="form-control"
                    id="prenom"
                    name="prenom"
                    placeholder="Prénom"
                    required
                  />
                </div>
                <div class="mb-3">
                  <label for="text" class="form-label">Nom de l'étudiant</label>
                  <input
                    type="text"
                    class="form-control"
                    id="nom"
                    name="nom"
                    placeholder="Nom"
                    required
                  />
                </div>
                <button type="submit" class="btn btn-primary w-100">
                  Créez le compte
                </button>
              </form>
            </div>
            <div class="mt-auto text-center">
            <p class="login-footer text-muted mb-0 mt-md-4 mt-4">
              Créer un compte étudiant ?
              <a href="create_etudiant.php" class="signup-link">ici</a>
            </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS (nécessaire pour le menu mobile) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>