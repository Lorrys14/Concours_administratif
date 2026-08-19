<?php
require '../config/database.php';
require '../includes/auth.php';
require_role('candidat');

$u = $_SESSION['user'];

$st = $pdo->prepare("SELECT d.*, c.numero_candidat, co.titre 
                     FROM documents d 
                     JOIN candidatures c ON c.id = d.candidature_id 
                     JOIN concours co ON co.id = c.concours_id 
                     WHERE c.user_id = ? 
                     ORDER BY d.id DESC");
$st->execute([$u['id']]);
$docs = $st->fetchAll();

$title = 'Mes Documents — Espace Candidat';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 fw-bold text-dark m-0"><i class="bi bi-file-earmark-check-fill text-primary me-2"></i>Mes Documents Justificatifs</h1>
        <p class="text-muted m-0">Consultez l'état de vérification de vos pièces scannées.</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Retour au tableau de bord
    </a>
</div>

<div class="card shadow-sm border-0 p-4">
    <div class="row g-4">
        <?php foreach ($docs as $d): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border p-3 shadow-sm bg-white">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($d['numero_candidat']) ?></span>
                        <span class="badge <?= $d['statut'] === 'valide' ? 'bg-success' : ($d['statut'] === 'rejete' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                            <?= ucfirst($d['statut']) ?>
                        </span>
                    </div>

                    <h5 class="fw-bold text-dark h6 mb-1"><?= htmlspecialchars($d['type_document']) ?></h5>
                    <small class="text-muted d-block mb-3"><?= htmlspecialchars($d['titre']) ?></small>

                    <div class="mt-auto border-top pt-2 text-end">
                        <a href="../uploads/documents/<?= htmlspecialchars($d['fichier']) ?>" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-eye me-1"></i>Visualiser le document
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($docs)): ?>
            <div class="col-12 text-center py-5 text-muted">
                <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                Aucun document téléversé pour le moment.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require '../includes/footer.php'; ?>