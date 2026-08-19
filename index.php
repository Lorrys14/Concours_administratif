<?php
require 'config/database.php';
$title = 'Accueil — Concours Administratifs';

$concours = $pdo->query("SELECT * FROM concours WHERE statut='ouvert' ORDER BY date_debut ASC")->fetchAll();

$u = $_SESSION['user'] ?? null;
$appliedConcoursIds = [];

if ($u && $u['role'] === 'candidat') {
    $stApp = $pdo->prepare("SELECT concours_id FROM candidatures WHERE user_id = ?");
    $stApp->execute([$u['id']]);
    $appliedConcoursIds = $stApp->fetchAll(PDO::FETCH_COLUMN);
}

require 'includes/header.php';
?>

<section class="hero text-center mb-5 p-5 text-white rounded-4 shadow-sm">
    <div class="container py-3">
        <h1 class="display-4 fw-bold mb-3">Portail des Concours Administratifs</h1>
        <p class="lead mb-4 max-w-700 mx-auto">
            Inscrivez-vous en ligne, suivez l'avancement de votre dossier et consultez vos résultats en toute simplicité.
        </p>

        <?php if (empty($u)): ?>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a class="btn btn-light btn-lg px-4 fw-bold text-success" href="register.php">
                    <i class="bi bi-person-plus-fill me-2"></i>Créer mon compte
                </a>
                <a class="btn btn-outline-light btn-lg px-4" href="login.php">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                </a>
            </div>
        <?php else: ?>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <?php if ($u['role'] === 'admin'): ?>
                    <a class="btn btn-light btn-lg px-4 fw-bold text-primary shadow-sm" href="admin/dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i>Tableau de Bord Admin
                    </a>
                    <a class="btn btn-outline-light btn-lg px-4 fw-semibold" href="admin/concours.php">
                        <i class="bi bi-award me-2"></i>Gérer les Concours
                    </a>
                    <a class="btn btn-outline-light btn-lg px-4 fw-semibold" href="admin/candidatures.php">
                        <i class="bi bi-folder-check me-2"></i>Voir les Candidatures
                    </a>
                <?php elseif ($u['role'] === 'agent'): ?>
                    <a class="btn btn-light btn-lg px-4 fw-bold text-primary shadow-sm" href="agent/dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i>Tableau de Bord Agent
                    </a>
                    <a class="btn btn-outline-light btn-lg px-4 fw-semibold" href="agent/dossiers.php">
                        <i class="bi bi-folder-check me-2"></i>Vérifier les Dossiers
                    </a>
                    <a class="btn btn-outline-light btn-lg px-4 fw-semibold" href="agent/notes.php">
                        <i class="bi bi-pencil-square me-2"></i>Saisie des Notes
                    </a>
                <?php else: ?>
                    <a class="btn btn-light btn-lg px-4 fw-bold text-success shadow-sm" href="candidat/dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i>Mon Tableau de Bord
                    </a>
                    <a class="btn btn-outline-light btn-lg px-4 fw-semibold" href="candidat/candidatures.php">
                        <i class="bi bi-folder2-open me-2"></i>Mes Candidatures
                    </a>
                    <a class="btn btn-outline-light btn-lg px-4 fw-semibold" href="candidat/resultats.php">
                        <i class="bi bi-trophy me-2"></i>Mes Résultats
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 fw-bold m-0"><i class="bi bi-megaphone-fill me-2 text-primary"></i>Concours actuellement ouverts</h2>
    <span class="badge bg-primary px-3 py-2 fs-6"><?= count($concours) ?> concours disponible(s)</span>
</div>

<div class="row g-4 mb-5">
    <?php if (!empty($concours)): ?>
        <?php foreach ($concours as $c): ?>
            <?php $isApplied = in_array($c['id'], $appliedConcoursIds); ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0 position-relative hover-shadow transition">
                    <div class="card-body d-flex flex-column p-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-success">Session <?= htmlspecialchars($c['session']) ?></span>
                            <?php if ($isApplied): ?>
                                <span class="badge bg-info text-dark"><i class="bi bi-check-circle me-1"></i>Déjà inscrit</span>
                            <?php elseif ((int)$c['places'] <= 0): ?>
                                <span class="badge bg-danger"><i class="bi bi-exclamation-octagon me-1"></i>Complet</span>
                            <?php else: ?>
                                <small class="text-primary fw-semibold"><i class="bi bi-people me-1"></i><?= (int)$c['places'] ?> place(s) restante(s)</small>
                            <?php endif; ?>
                        </div>

                        <h3 class="h5 card-title fw-bold text-dark mt-1"><?= htmlspecialchars($c['titre']) ?></h3>

                        <p class="card-text text-muted small flex-grow-1">
                            <?= htmlspecialchars(mb_strimwidth($c['description'] ?? '', 0, 140, '...')) ?>
                        </p>

                        <div class="border-top pt-3 mt-3">
                            <div class="d-flex justify-content-between text-muted small mb-3">
                                <span><i class="bi bi-calendar-event me-1"></i>Clôture :</span>
                                <strong class="text-dark"><?= date('d/m/Y', strtotime($c['date_fin'])) ?></strong>
                            </div>

                            <?php if ($u && $u['role'] === 'candidat'): ?>
                                <?php if ($isApplied): ?>
                                    <a href="candidat/dashboard.php" class="btn btn-outline-info w-100 fw-semibold">
                                        <i class="bi bi-folder2-open me-1"></i>Voir ma candidature
                                    </a>
                                <?php elseif ((int)$c['places'] <= 0): ?>
                                    <button class="btn btn-secondary w-100 fw-bold" disabled>
                                        <i class="bi bi-x-circle me-1"></i>Complet - 0 place
                                    </button>
                                <?php else: ?>
                                    <a href="candidat/inscription.php?id=<?= $c['id'] ?>" class="btn btn-success w-100 fw-bold">
                                        <i class="bi bi-pencil-square me-1"></i>S'inscrire à ce concours
                                    </a>
                                <?php endif; ?>
                            <?php elseif ($u && $u['role'] === 'agent'): ?>
                                <a href="agent/dossiers.php?search=<?= urlencode($c['titre']) ?>" class="btn btn-primary w-100 fw-semibold">
                                    <i class="bi bi-folder-check me-1"></i>Vérifier les dossiers
                                </a>
                            <?php elseif ($u && $u['role'] === 'admin'): ?>
                                <a href="admin/concours.php?edit=<?= $c['id'] ?>" class="btn btn-primary w-100 fw-semibold">
                                    <i class="bi bi-gear me-1"></i>Gérer ce concours
                                </a>
                            <?php else: ?>
                                <a href="concours.php?id=<?= $c['id'] ?>" class="btn btn-outline-primary w-100 fw-semibold">
                                   Voir les détails <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info text-center py-4">
                <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                Aucun concours n'est actuellement ouvert aux inscriptions. Veuillez revenir ultérieurement.
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>
