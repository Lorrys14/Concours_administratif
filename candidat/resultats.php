<?php
require '../config/database.php';
require '../includes/auth.php';
require_role('candidat');

$u = $_SESSION['user'];

$stRes = $pdo->prepare("SELECT c.*, co.titre as concours_titre, co.session, r.moyenne, r.rang, r.decision, r.publie 
                        FROM candidatures c 
                        JOIN concours co ON co.id = c.concours_id 
                        JOIN resultats r ON r.candidature_id = c.id 
                        WHERE c.user_id = ? AND r.publie = 1 
                        ORDER BY c.created_at DESC");
$stRes->execute([$u['id']]);
$results = $stRes->fetchAll();

$title = 'Mes Résultats — Espace Candidat';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 fw-bold text-dark m-0"><i class="bi bi-trophy-fill text-primary me-2"></i>Mes Résultats & Relevés de Notes</h1>
        <p class="text-muted m-0">Consultez les résultats officiels publiés pour vos candidatures.</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Retour au tableau de bord
    </a>
</div>

<?php if (!empty($results)): ?>
    <?php foreach ($results as $res): ?>
        <div class="card p-4 shadow-sm border-0 mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom pb-3 mb-3">
                <div>
                    <h3 class="h4 fw-bold text-dark m-0"><?= htmlspecialchars($res['concours_titre']) ?></h3>
                    <span class="text-muted small">Session <?= htmlspecialchars($res['session']) ?> | N° Candidat : <strong><?= htmlspecialchars($res['numero_candidat']) ?></strong></span>
                </div>
                <div>
                    <?php if ($res['decision'] === 'admis'): ?>
                        <span class="badge bg-success fs-5 px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i>ADMIS</span>
                    <?php elseif ($res['decision'] === 'liste_attente'): ?>
                        <span class="badge bg-warning text-dark fs-5 px-3 py-2"><i class="bi bi-hourglass-split me-1"></i>LISTE D'ATTENTE</span>
                    <?php else: ?>
                        <span class="badge bg-danger fs-5 px-3 py-2"><i class="bi bi-x-circle-fill me-1"></i>NON ADMIS</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row text-center bg-light p-3 rounded-3 align-items-center mb-4">
                <div class="col-md-4 mb-2 mb-md-0">
                    <span class="text-muted small d-block">Moyenne Générale</span>
                    <strong class="fs-4 text-primary"><?= number_format($res['moyenne'], 2, ',', ' ') ?> / 20</strong>
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                    <span class="text-muted small d-block">Rang de classement</span>
                    <strong class="fs-4 text-dark">#<?= (int)$res['rang'] ?></strong>
                </div>
                <div class="col-md-4">
                    <span class="text-muted small d-block">Décision Finale</span>
                    <strong class="fs-4 text-uppercase"><?= str_replace('_', ' ', $res['decision']) ?></strong>
                </div>
            </div>

            <div class="text-end">
                <button onclick="window.print()" class="btn btn-outline-primary fw-bold">
                    <i class="bi bi-printer me-1"></i>Imprimer mon relevé de notes
                </button>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="card p-5 shadow-sm border-0 text-center">
        <i class="bi bi-clock-history fs-1 text-primary mb-3"></i>
        <h4 class="fw-bold text-dark">Aucun résultat actuellement disponible</h4>
        <p class="text-muted">Les délibérations n'ont pas encore été publiées pour vos concours en cours. Un message vous sera notifié dès leur publication.</p>
    </div>
<?php endif; ?>

<?php require '../includes/footer.php'; ?>