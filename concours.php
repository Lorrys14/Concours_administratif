<?php
require 'config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$id = (int)($_GET['id'] ?? 0);
$s = $pdo->prepare("SELECT * FROM concours WHERE id = ?");
$s->execute([$id]);
$c = $s->fetch();

if (!$c) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$alreadyApplied = false;
if (!empty($_SESSION['user']) && $_SESSION['user']['role'] === 'candidat') {
    $check = $pdo->prepare("SELECT id FROM candidatures WHERE user_id = ? AND concours_id = ?");
    $check->execute([$_SESSION['user']['id'], $c['id']]);
    if ($check->fetch()) {
        $alreadyApplied = true;
    }
}

$title = htmlspecialchars($c['titre']) . ' — Détails';
require 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card shadow-sm border-0 p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-primary px-3 py-2">Session <?= htmlspecialchars($c['session']) ?></span>
                <span class="badge <?= $c['statut'] === 'ouvert' ? 'bg-success' : 'bg-secondary' ?> px-3 py-2">
                    <?= $c['statut'] === 'ouvert' ? 'Inscriptions Ouvertes' : 'Fermé' ?>
                </span>
            </div>

            <h1 class="h2 fw-bold text-dark mb-4"><?= htmlspecialchars($c['titre']) ?></h1>

            <p class="lead text-secondary mb-4"><?= nl2br(htmlspecialchars($c['description'] ?? '')) ?></p>

            <div class="row g-4 bg-light rounded-3 p-4 mb-4">
                <div class="col-md-6">
                    <div class="mb-3">
                        <i class="bi bi-calendar-range text-primary me-2 fs-5"></i>
                        <strong>Période d'inscription :</strong><br>
                        <span class="text-muted ms-4">Du <?= date('d/m/Y', strtotime($c['date_debut'])) ?> au <?= date('d/m/Y', strtotime($c['date_fin'])) ?></span>
                    </div>
                    <div>
                        <i class="bi bi-mortarboard text-primary me-2 fs-5"></i>
                        <strong>Diplôme requis :</strong><br>
                        <span class="text-muted ms-4"><?= htmlspecialchars($c['diplome_requis'] ?? 'Aucun requis spécifique') ?></span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <i class="bi bi-people text-primary me-2 fs-5"></i>
                        <strong>Places disponibles :</strong><br>
                        <span class="ms-4 font-monospace fw-bold <?= (int)$c['places'] > 0 ? 'text-success' : 'text-danger' ?>">
                            <?= (int)$c['places'] ?> place(s) restante(s)
                        </span>
                    </div>
                    <div class="mb-3">
                        <i class="bi bi-person-check text-primary me-2 fs-5"></i>
                        <strong>Limite d'âge :</strong><br>
                        <span class="text-muted ms-4">De <?= (int)$c['age_min'] ?> à <?= (int)$c['age_max'] ?> ans</span>
                    </div>
                    <div>
                        <i class="bi bi-cash-coin text-primary me-2 fs-5"></i>
                        <strong>Frais de dossier :</strong><br>
                        <span class="text-muted ms-4"><?= number_format($c['frais'], 0, ',', ' ') ?> FCFA</span>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Retour à la liste
                </a>

                <?php if ($c['statut'] !== 'ouvert'): ?>
                    <button class="btn btn-secondary disabled" disabled>Inscriptions fermées</button>
                <?php elseif ((int)$c['places'] <= 0 && !$alreadyApplied): ?>
                    <button class="btn btn-danger btn-lg disabled" disabled><i class="bi bi-exclamation-octagon me-1"></i>Concours complet (0 place)</button>
                <?php elseif (!empty($_SESSION['user']) && $_SESSION['user']['role'] === 'candidat'): ?>
                    <?php if ($alreadyApplied): ?>
                        <div class="text-end">
                            <span class="badge bg-info text-dark p-2 mb-2 d-inline-block"><i class="bi bi-check-circle me-1"></i>Vous êtes déjà inscrit à ce concours</span><br>
                            <a href="candidat/dashboard.php" class="btn btn-primary btn-lg">Voir ma candidature</a>
                        </div>
                    <?php else: ?>
                        <a class="btn btn-success btn-lg fw-bold px-4" href="candidat/inscription.php?id=<?= $c['id'] ?>">
                            <i class="bi bi-file-earmark-plus me-2"></i>Commencer l'inscription
                        </a>
                    <?php endif; ?>
                <?php elseif (!empty($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                    <a class="btn btn-primary" href="admin/dashboard.php">Gérer dans l'administration</a>
                <?php else: ?>
                    <a class="btn btn-primary btn-lg fw-bold px-4" href="login.php">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter pour s'inscrire
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
