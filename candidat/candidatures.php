<?php
require '../config/database.php';
require '../includes/auth.php';
require_role('candidat');

$u = $_SESSION['user'];

$st = $pdo->prepare("SELECT c.*, co.titre, co.session, co.frais
                     FROM candidatures c
                     JOIN concours co ON co.id = c.concours_id
                     WHERE c.user_id = ?
                     ORDER BY c.created_at DESC");
$st->execute([$u['id']]);
$candidatures = $st->fetchAll();

if (!function_exists('getCandStatusBadgeClass')) {
    function getCandStatusBadgeClass($statut)
    {
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

$title = 'Mes Candidatures — Espace Candidat';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-dark m-0"><i class="bi bi-folder-check text-primary me-2"></i>Mes Candidatures</h1>
        <p class="text-muted m-0">Suivez l'état de validation de vos dossiers de candidature.</p>
    </div>
    <a href="../index.php" class="btn btn-success fw-bold">
        <i class="bi bi-plus-circle me-1"></i>S'inscrire à un concours
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">N° Candidature</th>
                        <th>Concours</th>
                        <th>Session</th>
                        <th>Centre</th>
                        <th>Date Soumission</th>
                        <th>Statut</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidatures as $c): ?>
                        <tr>
                            <td class="ps-3">
                                <span class="badge bg-light text-dark font-monospace border fs-6"><?= htmlspecialchars($c['numero_candidat']) ?></span>
                            </td>
                            <td>
                                <strong class="text-dark d-block"><?= htmlspecialchars($c['titre']) ?></strong>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($c['session']) ?></span></td>
                            <td><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($c['centre'] ?? 'Non attribué') ?></td>
                            <td class="small text-muted"><?= date('d/m/Y H:i', strtotime(!empty($c['date_soumission']) ? $c['date_soumission'] : $c['created_at'])) ?></td>
                            <td>
                                <span class="badge <?= getCandStatusBadgeClass($c['statut']) ?> px-2 py-1 fs-6">
                                    <?= ucfirst(str_replace('_', ' ', $c['statut'])) ?>
                                </span>
                                <?php if ($c['statut'] === 'rejete' && !empty($c['motif_rejet'])): ?>
                                    <div class="small text-danger mt-1">
                                        <i class="bi bi-info-circle me-1"></i>Motif : <?= htmlspecialchars($c['motif_rejet']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <?php if (in_array($c['statut'], ['valide', 'convoque', 'admis', 'non_admis'], true) && !empty($c['centre'])): ?>
                                    <div class="btn-group btn-group-sm">
                                        <a href="convocation.php?id=<?= $c['id'] ?>" class="btn btn-outline-primary" title="Imprimer convocation / récépissé">
                                            <i class="bi bi-printer me-1"></i>Convocation
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border fw-normal py-1 px-2">
                                        <i class="bi bi-clock me-1"></i>Validation requise
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($candidatures)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 text-secondary d-block mb-2"></i>
                                Vous n'avez encore soumis aucune candidature.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>