<?php
require '../config/database.php';
require '../includes/auth.php';
require_role('admin');

$title = 'Administration — Tableau de bord';

$total = $pdo->query("SELECT COUNT(*) FROM candidatures")->fetchColumn();
$val   = $pdo->query("SELECT COUNT(*) FROM candidatures WHERE statut = 'valide'")->fetchColumn();
$att   = $pdo->query("SELECT COUNT(*) FROM candidatures WHERE statut IN ('soumis', 'en_verification')")->fetchColumn();
$rej   = $pdo->query("SELECT COUNT(*) FROM candidatures WHERE statut = 'rejete'")->fetchColumn();

$rows = $pdo->query("SELECT c.*, u.nom, u.prenom, co.titre
                     FROM candidatures c
                     JOIN users u ON u.id = c.user_id
                     JOIN concours co ON co.id = c.concours_id
                     ORDER BY c.created_at DESC
                     LIMIT 10")->fetchAll();

require '../includes/header.php';

if (!function_exists('getAdminStatusBadgeClass')) {
    function getAdminStatusBadgeClass($statut) {
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
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-dark m-0"><i class="bi bi-speedometer2 text-primary me-2"></i>Tableau de bord administration</h1>
        <p class="text-muted m-0">Aperçu global des concours et candidatures enregistrées.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="candidatures.php" class="btn btn-outline-primary fw-semibold">
            <i class="bi bi-kanban me-1"></i>Gérer les candidatures
        </a>
        <a href="resultats.php" class="btn btn-primary fw-semibold">
            <i class="bi bi-trophy me-1"></i>Délibération & Résultats
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card p-4 shadow-sm border-0 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">Total Candidatures</div>
                    <div class="stat text-dark"><?= (int)$total ?></div>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                    <i class="bi bi-folder-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card p-4 shadow-sm border-0 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">Validées</div>
                    <div class="stat text-success"><?= (int)$val ?></div>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                    <i class="bi bi-check-circle-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card p-4 shadow-sm border-0 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">En attente</div>
                    <div class="stat text-warning"><?= (int)$att ?></div>
                </div>
                <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                    <i class="bi bi-hourglass-split fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card p-4 shadow-sm border-0 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">Rejetées</div>
                    <div class="stat text-danger"><?= (int)$rej ?></div>
                </div>
                <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
                    <i class="bi bi-x-circle-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card p-4 shadow-sm border-0 h-100">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Répartition par Statut</h5>
            <div style="height: 240px; position: relative;">
                <canvas id="chartStatuts"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-4 shadow-sm border-0 h-100">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-bar-chart-line-fill me-2 text-primary"></i>Candidats par Concours</h5>
            <div style="height: 240px; position: relative;">
                <canvas id="chartConcours"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx1 = document.getElementById('chartStatuts');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: ['Validées', 'En attente', 'Rejetées'],
                datasets: [{
                    data: [<?= (int)$val ?>, <?= (int)$att ?>, <?= (int)$rej ?>],
                    backgroundColor: ['#198754', '#ffc107', '#dc3545']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    <?php
    $concoursData = $pdo->query("SELECT co.titre, COUNT(c.id) as total FROM concours co LEFT JOIN candidatures c ON c.concours_id = co.id GROUP BY co.id")->fetchAll();
    $labelsC = array_column($concoursData, 'titre');
    $countsC = array_column($concoursData, 'total');
    ?>
    const ctx2 = document.getElementById('chartConcours');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labelsC) ?>,
                datasets: [{
                    label: 'Nombre de candidats',
                    data: <?= json_encode($countsC) ?>,
                    backgroundColor: '#0d6efd'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, precision: 0 } }
            }
        });
    }
});
</script>

<div class="card p-4 shadow-sm border-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="h5 fw-bold text-dark m-0"><i class="bi bi-clock-history me-2 text-primary"></i>Dernières candidatures</h4>
        <a href="candidatures.php" class="btn btn-sm btn-outline-primary">Tout afficher</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>N° Candidat</th>
                    <th>Candidat</th>
                    <th>Concours</th>
                    <th>Date</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td><span class="badge bg-light text-dark font-monospace border"><?= htmlspecialchars($r['numero_candidat']) ?></span></td>
                    <td class="fw-semibold"><?= htmlspecialchars($r['nom'] . ' ' . $r['prenom']) ?></td>
                    <td><?= htmlspecialchars($r['titre']) ?></td>
                    <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                    <td>
                        <span class="badge <?= getAdminStatusBadgeClass($r['statut']) ?> px-2 py-1">
                            <?= htmlspecialchars($r['statut']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Aucune candidature enregistrée.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
