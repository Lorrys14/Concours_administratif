<?php
require '../config/database.php';
require '../includes/auth.php';
require_role('candidat');

$u = $_SESSION['user'];

// Candidatures
$st = $pdo->prepare("SELECT c.*, co.titre, co.session FROM candidatures c JOIN concours co ON co.id = c.concours_id WHERE c.user_id = ? ORDER BY c.created_at DESC");
$st->execute([$u['id']]);
$cands = $st->fetchAll();

// Total count
$stCount = $pdo->prepare("SELECT COUNT(*) FROM candidatures WHERE user_id = ?");
$stCount->execute([$u['id']]);
$totalCount = $stCount->fetchColumn();

// Notifications
$stNotif = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stNotif->execute([$u['id']]);
$notifications = $stNotif->fetchAll();

if (!function_exists('getStatusBadgeClass')) {
    function getStatusBadgeClass($statut) {
        switch ($statut) {
            case 'valide':
            case 'admis':
                return 'bg-success';
            case 'rejete':
            case 'non_admis':
                return 'bg-danger';
            case 'en_verification':
            case 'soumis':
                return 'bg-warning text-dark';
            case 'convoque':
                return 'bg-info text-dark';
            default:
                return 'bg-secondary';
        }
    }
}

if (!function_exists('getStatusLabel')) {
    function getStatusLabel($statut) {
        $labels = [
            'brouillon'       => 'Brouillon',
            'soumis'          => 'Soumis',
            'en_verification' => 'En vérification',
            'valide'          => 'Validé',
            'rejete'          => 'Rejeté',
            'convoque'        => 'Convoqué',
            'admis'           => 'Admis',
            'non_admis'       => 'Non admis'
        ];
        return $labels[$statut] ?? ucfirst($statut);
    }
}

$title = 'Tableau de bord candidat';
require '../includes/header.php';
?>

<?php if (isset($_GET['applied'])): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>Votre candidature a été soumise avec succès !
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold m-0">Bonjour <?= htmlspecialchars($u['prenom']) ?> 👋</h1>
        <p class="text-muted mb-0">Bienvenue sur votre espace candidat.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary fw-semibold" href="profil.php">
            <i class="bi bi-person-badge me-1"></i>Mon profil
        </a>
        <a class="btn btn-success fw-semibold" href="../index.php">
            <i class="bi bi-plus-circle me-1"></i>Découvrir les concours
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-0 h-100">
            <a href="candidatures.php" class="text-decoration-none text-dark d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fw-semibold small mb-1">Candidatures</div>
                    <div class="stat text-primary"><?= (int)$totalCount ?></div>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                    <i class="bi bi-folder-check fs-2"></i>
                </div>
            </a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-0 h-100">
            <a href="documents.php" class="text-decoration-none text-dark d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fw-semibold small mb-1">Documents</div>
                    <div class="stat text-info"><i class="bi bi-file-earmark-check fs-2"></i></div>
                </div>
                <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info">
                    <i class="bi bi-file-earmark-text fs-2"></i>
                </div>
            </a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-0 h-100">
            <a href="resultats.php" class="text-decoration-none text-dark d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fw-semibold small mb-1">Résultats</div>
                    <div class="stat text-success"><i class="bi bi-trophy fs-2"></i></div>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                    <i class="bi bi-award fs-2"></i>
                </div>
            </a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-0 h-100">
            <a href="notifications.php" class="text-decoration-none text-dark d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fw-semibold small mb-1">Notifications</div>
                    <div class="stat text-warning"><?= count($notifications) ?></div>
                </div>
                <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                    <i class="bi bi-bell fs-2"></i>
                </div>
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card p-4 shadow-sm border-0">
            <h4 class="h5 fw-bold mb-3"><i class="bi bi-list-task me-2 text-primary"></i>Mes candidatures</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>N° Candidat</th>
                            <th>Concours</th>
                            <th>Session</th>
                            <th>Centre</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cands as $c): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark font-monospace border"><?= htmlspecialchars($c['numero_candidat']) ?></span></td>
                                <td class="fw-semibold"><?= htmlspecialchars($c['titre']) ?></td>
                                <td><?= htmlspecialchars($c['session']) ?></td>
                                <td><?= htmlspecialchars($c['centre'] ?? 'Non assigné') ?></td>
                                <td>
                                    <span class="badge <?= getStatusBadgeClass($c['statut']) ?> px-2 py-1">
                                        <?= getStatusLabel($c['statut']) ?>
                                    </span>
                                    <?php if ($c['statut'] === 'rejete' && !empty($c['motif_rejet'])): ?>
                                        <div class="small text-danger mt-1">
                                            <i class="bi bi-info-circle me-1"></i>Motif : <?= htmlspecialchars($c['motif_rejet']) ?>
                                        </div>
                                    <?php elseif (in_array($c['statut'], ['valide', 'convoque', 'admis', 'non_admis'], true) && !empty($c['centre'])): ?>
                                        <div class="mt-2">
                                            <a href="convocation.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2 small">
                                                <i class="bi bi-printer me-1"></i>Convocation
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="small text-secondary mt-1">
                                            <i class="bi bi-hourglass-split me-1"></i>Convocation après validation
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($cands)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary"></i>
                                    Aucune candidature soumise pour le moment.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card p-4 shadow-sm border-0">
            <h4 class="h5 fw-bold mb-3"><i class="bi bi-bell me-2 text-primary"></i>Notifications</h4>
            <?php if (!empty($notifications)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $n): ?>
                        <div class="list-group-item px-0 py-2">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <span class="text-dark small fw-semibold"><?= htmlspecialchars($n['message']) ?></span>
                                <small class="text-muted ms-2 text-nowrap" style="font-size: 0.75rem;"><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-3">
                    <small>Aucune notification récente.</small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
