<?php
require '../config/database.php';
require '../includes/auth.php';
require_role('agent');

$u = $_SESSION['user'];

$totDossiers     = $pdo->query("SELECT COUNT(*) FROM candidatures")->fetchColumn();
$totVerification = $pdo->query("SELECT COUNT(*) FROM candidatures WHERE statut IN ('soumis', 'en_verification')")->fetchColumn();
$totValides      = $pdo->query("SELECT COUNT(*) FROM candidatures WHERE statut = 'valide'")->fetchColumn();
$totRejetes     = $pdo->query("SELECT COUNT(*) FROM candidatures WHERE statut = 'rejete'")->fetchColumn();
$totConvoques   = $pdo->query("SELECT COUNT(*) FROM candidatures WHERE statut = 'convoque'")->fetchColumn();
$totNotes       = $pdo->query("SELECT COUNT(*) FROM notes")->fetchColumn();

// Statistiques par concours
$stStatsConcours = $pdo->query("SELECT co.id, co.titre, co.session,
    COUNT(c.id) as total_candidats,
    SUM(CASE WHEN c.statut IN ('soumis', 'en_verification') THEN 1 ELSE 0 END) as a_verifier,
    SUM(CASE WHEN c.statut = 'valide' THEN 1 ELSE 0 END) as valides,
    SUM(CASE WHEN c.statut = 'rejete' THEN 1 ELSE 0 END) as rejetes
    FROM concours co
    LEFT JOIN candidatures c ON c.concours_id = co.id
    GROUP BY co.id, co.titre, co.session
    ORDER BY co.id DESC");
$statsConcours = $stStatsConcours->fetchAll();

// Dernières candidatures reçues à vérifier
$stRecent = $pdo->query("SELECT c.*, u.nom, u.prenom, u.email, co.titre as concours_titre
                         FROM candidatures c
                         JOIN users u ON u.id = c.user_id
                         JOIN concours co ON co.id = c.concours_id
                         WHERE c.statut IN ('soumis', 'en_verification')
                         ORDER BY c.created_at DESC
                         LIMIT 5");
$recentDossiers = $stRecent->fetchAll();

$title = 'Espace Agent de Gestion';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-dark m-0"><i class="bi bi-person-workspace text-primary me-2"></i>Espace Agent de Gestion</h1>
        <p class="text-muted m-0">Bienvenue <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?>. Vérification des dossiers et saisie des notes des examens.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="dossiers.php" class="btn btn-primary fw-semibold">
            <i class="bi bi-folder-check me-1"></i>Examiner Dossiers
        </a>
        <a href="notes.php" class="btn btn-success fw-semibold">
            <i class="bi bi-pencil-square me-1"></i>Saisir Notes
        </a>
    </div>
</div>

<!-- Cartes Statistiques Globales -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card p-3 shadow-sm border-0 h-100 bg-white">
            <div class="text-muted small fw-semibold">Total Candidats</div>
            <div class="fs-3 fw-bold text-dark mt-1"><?= (int)$totDossiers ?></div>
            <div class="small text-muted"><i class="bi bi-folder me-1 text-primary"></i>Dossiers</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card p-3 shadow-sm border-0 h-100 bg-white">
            <div class="text-muted small fw-semibold">À Vérifier</div>
            <div class="fs-3 fw-bold text-warning mt-1"><?= (int)$totVerification ?></div>
            <div class="small text-muted"><i class="bi bi-hourglass-split me-1 text-warning"></i>En attente</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card p-3 shadow-sm border-0 h-100 bg-white">
            <div class="text-muted small fw-semibold">Validés</div>
            <div class="fs-3 fw-bold text-success mt-1"><?= (int)$totValides ?></div>
            <div class="small text-muted"><i class="bi bi-check-circle me-1 text-success"></i>Conformes</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card p-3 shadow-sm border-0 h-100 bg-white">
            <div class="text-muted small fw-semibold">Rejetés</div>
            <div class="fs-3 fw-bold text-danger mt-1"><?= (int)$totRejetes ?></div>
            <div class="small text-muted"><i class="bi bi-x-circle me-1 text-danger"></i>Incomplets</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card p-3 shadow-sm border-0 h-100 bg-white">
            <div class="text-muted small fw-semibold">Convoqués</div>
            <div class="fs-3 fw-bold text-info mt-1"><?= (int)$totConvoques ?></div>
            <div class="small text-muted"><i class="bi bi-file-earmark-text me-1 text-info"></i>Prêts</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card p-3 shadow-sm border-0 h-100 bg-white">
            <div class="text-muted small fw-semibold">Notes Saisies</div>
            <div class="fs-3 fw-bold text-secondary mt-1"><?= (int)$totNotes ?></div>
            <div class="small text-muted"><i class="bi bi-pencil me-1 text-secondary"></i>Évaluations</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Module Agent 1 : Inscription & Vérification -->
    <div class="col-md-6">
        <div class="card p-4 shadow-sm border-0 h-100 bg-white">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary me-3">
                    <i class="bi bi-folder-check fs-2"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-dark m-0">Vérification des Pièces & Dossiers</h4>
                    <small class="text-muted">Agent d'inscription & de suivi</small>
                </div>
            </div>
            <p class="text-muted">Examinez les pièces justificatives téléversées par les candidats (CNI, Diplôme, Acte de naissance) et validez ou rejetez les candidatures.</p>
            <div class="mt-auto">
                <a href="dossiers.php" class="btn btn-primary fw-bold w-100">
                    <i class="bi bi-eye me-2"></i>Accéder à la liste des Dossiers (<?= (int)$totVerification ?> en attente)
                </a>
            </div>
        </div>
    </div>

    <!-- Module Agent 2 : Saisie des Notes -->
    <div class="col-md-6">
        <div class="card p-4 shadow-sm border-0 h-100 bg-white">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success me-3">
                    <i class="bi bi-pencil-square fs-2"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-dark m-0">Saisie & Correction des Notes</h4>
                    <small class="text-muted">Agent de correction & délibération</small>
                </div>
            </div>
            <p class="text-muted">Saisissez les notes obtenues par les candidats aux épreuves écrites et orales pour chaque concours attribué.</p>
            <div class="mt-auto">
                <a href="notes.php" class="btn btn-success fw-bold w-100">
                    <i class="bi bi-pencil-square me-2"></i>Accéder au Bordereau de Saisie
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Répartition par concours -->
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark m-0"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Aperçu par Concours</h5>
                <span class="badge bg-light text-dark border"><?= count($statsConcours) ?> concours</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Concours</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">À vérifier</th>
                                <th class="text-center">Validés</th>
                                <th class="text-center">Rejetés</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($statsConcours as $sc): ?>
                                <tr>
                                    <td class="ps-3">
                                        <strong class="text-dark d-block"><?= htmlspecialchars($sc['titre']) ?></strong>
                                        <small class="text-muted">Session <?= htmlspecialchars($sc['session']) ?></small>
                                    </td>
                                    <td class="text-center fw-bold"><?= (int)$sc['total_candidats'] ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark"><?= (int)$sc['a_verifier'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success"><?= (int)$sc['valides'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger"><?= (int)$sc['rejetes'] ?></span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="dossiers.php" class="btn btn-sm btn-outline-primary py-0 px-2">
                                            Vérifier
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Derniers dossiers à examiner -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark m-0"><i class="bi bi-clock-history me-2 text-primary"></i>Dossiers prioritaires</h5>
                <a href="dossiers.php" class="small text-decoration-none">Voir tout</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($recentDossiers as $rd): ?>
                        <div class="list-group-item p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="text-dark d-block"><?= htmlspecialchars($rd['nom'] . ' ' . $rd['prenom']) ?></strong>
                                <small class="text-muted font-monospace me-2"><?= htmlspecialchars($rd['numero_candidat']) ?></small>
                                <small class="text-primary d-block"><?= htmlspecialchars($rd['concours_titre']) ?></small>
                            </div>
                            <a href="../admin/candidat_detail.php?id=<?= $rd['id'] ?>" class="btn btn-sm btn-outline-primary fw-semibold">
                                <i class="bi bi-eye me-1"></i>Examiner
                            </a>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($recentDossiers)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-check-circle fs-2 text-success d-block mb-1"></i>
                            Aucun dossier en attente de vérification !
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
