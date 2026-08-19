<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$baseUrl = defined('BASE_URL') ? BASE_URL : '/';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Concours Administratifs') ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= $baseUrl ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= $baseUrl ?>index.php">
            <i class="bi bi-award-fill"></i>
            <span>CONCOURS ADMINISTRATIFS</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>index.php"><i class="bi bi-house-door me-1"></i>Accueil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>resultats.php"><i class="bi bi-trophy me-1"></i>Résultats</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>faq.php"><i class="bi bi-question-circle me-1"></i>FAQ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>contact.php"><i class="bi bi-envelope me-1"></i>Contact</a>
                </li>
                <?php if (!empty($_SESSION['user'])): ?>
                    <?php
                        $user = $_SESSION['user'];
                        $userRole = $user['role'];
                        $userInitials = strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1));
                        $userName = htmlspecialchars($user['prenom'] . ' ' . $user['nom']);
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white fw-semibold d-flex align-items-center gap-2 px-2 py-1 rounded-3 bg-white bg-opacity-10 border border-light border-opacity-25 ms-lg-2" href="#" id="userDrop" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.8rem;">
                                <?= $userInitials ?>
                            </span>
                            <span class="d-none d-sm-inline"><?= htmlspecialchars($user['prenom']) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userDrop">
                            <li class="px-3 py-2 bg-light border-bottom rounded-top">
                                <div class="fw-bold text-dark"><?= $userName ?></div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">
                                    <i class="bi bi-shield-check me-1 text-primary"></i>
                                    <?= $userRole === 'admin' ? 'Administrateur' : ($userRole === 'agent' ? 'Agent de Gestion' : 'Candidat') ?>
                                </small>
                            </li>
                            <?php if ($userRole === 'admin'): ?>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>admin/dashboard.php"><i class="bi bi-speedometer2 me-2 text-primary"></i>Tableau de bord</a></li>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>admin/concours.php"><i class="bi bi-award me-2 text-primary"></i>Concours</a></li>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>admin/candidats.php"><i class="bi bi-people me-2 text-primary"></i>Candidats</a></li>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>admin/candidatures.php"><i class="bi bi-folder-check me-2 text-primary"></i>Dossiers</a></li>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>admin/centres.php"><i class="bi bi-building me-2 text-primary"></i>Centres</a></li>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>admin/epreuves.php"><i class="bi bi-journal-check me-2 text-primary"></i>Épreuves & Coef.</a></li>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>admin/notes.php"><i class="bi bi-pencil-square me-2 text-primary"></i>Notes</a></li>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>admin/resultats.php"><i class="bi bi-trophy me-2 text-primary"></i>Délibérations & Pub.</a></li>
                            <?php elseif ($userRole === 'agent'): ?>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>agent/dashboard.php"><i class="bi bi-speedometer2 me-2 text-primary"></i>Tableau de bord</a></li>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>agent/dossiers.php"><i class="bi bi-folder-check me-2 text-primary"></i>Vérification dossiers</a></li>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>agent/notes.php"><i class="bi bi-pencil-square me-2 text-primary"></i>Saisie notes</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>candidat/dashboard.php"><i class="bi bi-speedometer2 me-2 text-primary"></i>Tableau de bord</a></li>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>candidat/profil.php"><i class="bi bi-person-vcard me-2 text-primary"></i>Mon Profil</a></li>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>candidat/candidatures.php"><i class="bi bi-folder2-open me-2 text-primary"></i>Mes Candidatures</a></li>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>candidat/documents.php"><i class="bi bi-file-earmark-check me-2 text-primary"></i>Mes Documents</a></li>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>candidat/resultats.php"><i class="bi bi-trophy me-2 text-primary"></i>Mes Résultats</a></li>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>candidat/notifications.php"><i class="bi bi-bell me-2 text-primary"></i>Notifications</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger fw-semibold" href="<?= $baseUrl ?>logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $baseUrl ?>login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Connexion</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-light text-primary fw-semibold btn-sm ms-lg-2" href="<?= $baseUrl ?>register.php">
                            <i class="bi bi-person-plus me-1"></i>Inscription
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4 min-vh-100-container">

