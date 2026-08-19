<?php
require '../config/database.php';
require '../includes/auth.php';
require_role('agent');

$msgSuccess = '';
$msgError = '';

$allowedStatuses = ['soumis', 'en_verification', 'valide', 'rejete', 'convoque'];

// Liste des centres de composition
$centresList = $pdo->query("SELECT * FROM centres ORDER BY ville ASC, nom ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $statut = trim($_POST['statut'] ?? '');
    $motif  = trim($_POST['motif'] ?? '');
    $centre = trim($_POST['centre'] ?? '');

    if ($id > 0 && in_array($statut, $allowedStatuses, true)) {
        if (!empty($centre)) {
            $st = $pdo->prepare("UPDATE candidatures SET statut = ?, motif_rejet = ?, centre = ? WHERE id = ?");
            $st->execute([$statut, $motif, $centre, $id]);
        } else {
            $st = $pdo->prepare("UPDATE candidatures SET statut = ?, motif_rejet = ? WHERE id = ?");
            $st->execute([$statut, $motif, $id]);
        }

        // Notifier le candidat
        $stCand = $pdo->prepare("SELECT user_id, numero_candidat FROM candidatures WHERE id = ?");
        $stCand->execute([$id]);
        $cand = $stCand->fetch();

        if ($cand) {
            if ($statut === 'valide') {
                $msgNotif = "Votre dossier est validé.";
            } elseif ($statut === 'rejete') {
                $msgNotif = "Votre candidature est rejetée." . ($motif ? " Motif : $motif" : "");
            } elseif ($statut === 'convoque') {
                $msgNotif = "Votre dossier est retenu. Vous êtes convoqué.";
            } elseif ($statut === 'admis') {
                $msgNotif = "Félicitations, votre candidature est admise !";
            } elseif ($statut === 'non_admis') {
                $msgNotif = "Votre candidature n'a pas été retenue.";
            } else {
                $msgNotif = "Votre dossier est passé au statut : " . $statut . ".";
            }

            $n = $pdo->prepare("INSERT INTO notifications (user_id, titre, message) VALUES (?, ?, ?)");
            $n->execute([$cand['user_id'], 'Notification', $msgNotif]);
        }

        $msgSuccess = "Le statut de la candidature #{$id} a été mis à jour avec succès.";
    } else {
        $msgError = "Erreur lors de la mise à jour du statut.";
    }
}

// Filtres et recherche
$search = trim($_GET['search'] ?? '');
$filterStatut = trim($_GET['filter_statut'] ?? '');

$sql = "SELECT c.*, u.nom, u.prenom, u.email, u.telephone, co.titre
        FROM candidatures c
        JOIN users u ON u.id = c.user_id
        JOIN concours co ON co.id = c.concours_id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (c.numero_candidat LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ? OR co.titre LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filterStatut) && in_array($filterStatut, $allowedStatuses, true)) {
    $sql .= " AND c.statut = ?";
    $params[] = $filterStatut;
}

$sql .= " ORDER BY c.created_at DESC";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

$title = 'Vérification des Dossiers — Agent';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-dark m-0"><i class="bi bi-folder-check text-primary me-2"></i>Vérification & Examen des Dossiers</h1>
        <p class="text-muted m-0">Examinez les pièces téléversées, validez les candidatures ou motivez un rejet.</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Tableau de bord
    </a>
</div>

<?php if (!empty($msgSuccess)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($msgSuccess) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endif; ?>

<?php if (!empty($msgError)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($msgError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endif; ?>

<!-- Barre de Recherche et Filtres -->
<div class="card p-3 shadow-sm border-0 mb-4 bg-light">
    <form method="get" action="dossiers.php" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Rechercher par N° Candidat, Nom, Prénom, Concours..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        <div class="col-md-4">
            <select name="filter_statut" class="form-select">
                <option value="">-- Tous les statuts --</option>
                <option value="soumis" <?= $filterStatut === 'soumis' ? 'selected' : '' ?>>Soumis (À vérifier)</option>
                <option value="en_verification" <?= $filterStatut === 'en_verification' ? 'selected' : '' ?>>En vérification</option>
                <option value="valide" <?= $filterStatut === 'valide' ? 'selected' : '' ?>>Validé (Conforme)</option>
                <option value="rejete" <?= $filterStatut === 'rejete' ? 'selected' : '' ?>>Rejeté</option>
                <option value="convoque" <?= $filterStatut === 'convoque' ? 'selected' : '' ?>>Convoqué</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary w-100 fw-semibold" type="submit">
                <i class="bi bi-funnel me-1"></i>Filtrer
            </button>
            <?php if (!empty($search) || !empty($filterStatut)): ?>
                <a href="dossiers.php" class="btn btn-outline-secondary">Effacer</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold text-dark m-0"><i class="bi bi-list-task me-2 text-primary"></i>Liste des Candidatures (<?= count($rows) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">N° Candidat</th>
                        <th>Candidat</th>
                        <th>Concours & Centre</th>
                        <th>Statut actuel</th>
                        <th class="pe-3 text-end" style="min-width: 300px;">Action / Modification</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td class="ps-3">
                                <span class="badge bg-light text-dark font-monospace border"><?= htmlspecialchars($r['numero_candidat']) ?></span>
                                <small class="text-muted d-block mt-1"><?= date('d/m/Y', strtotime($r['created_at'])) ?></small>
                            </td>
                            <td>
                                <strong class="text-dark d-block"><?= htmlspecialchars($r['nom'] . ' ' . $r['prenom']) ?></strong>
                                <small class="text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($r['email']) ?></small>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($r['titre']) ?></span><br>
                                <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($r['centre'] ?? 'Non défini') ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $r['statut'] === 'valide' ? 'bg-success' : ($r['statut'] === 'rejete' ? 'bg-danger' : ($r['statut'] === 'convoque' ? 'bg-info text-dark' : 'bg-warning text-dark')) ?>">
                                    <?= ucfirst(str_replace('_', ' ', $r['statut'])) ?>
                                </span>
                                <?php if (!empty($r['motif_rejet'])): ?>
                                    <small class="d-block text-danger mt-1" style="font-size: 0.8rem;">
                                        Motif : <?= htmlspecialchars($r['motif_rejet']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td class="pe-3 text-end">
                                <div class="d-flex justify-content-end align-items-center gap-2">
                                    <a href="../admin/candidat_detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary fw-semibold" title="Examiner les pièces scannées">
                                        <i class="bi bi-folder2-open me-1"></i>Examiner Pièces
                                    </a>

                                    <!-- Formulaire rapide de changement de statut et attribution centre -->
                                    <form method="post" action="dossiers.php" class="d-inline-flex flex-wrap gap-1 align-items-center justify-content-end">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <select name="statut" class="form-select form-select-sm" style="width: auto;" onchange="toggleAgentMotif(this)">
                                            <option value="soumis" <?= $r['statut'] === 'soumis' ? 'selected' : '' ?>>Soumis</option>
                                            <option value="en_verification" <?= $r['statut'] === 'en_verification' ? 'selected' : '' ?>>En vérification</option>
                                            <option value="valide" <?= $r['statut'] === 'valide' ? 'selected' : '' ?>>Valider</option>
                                            <option value="convoque" <?= $r['statut'] === 'convoque' ? 'selected' : '' ?>>Convoquer</option>
                                            <option value="rejete" <?= $r['statut'] === 'rejete' ? 'selected' : '' ?>>Rejeter</option>
                                        </select>
                                        <select name="centre" class="form-select form-select-sm" style="max-width: 150px;">
                                            <option value="">-- Centre --</option>
                                            <?php foreach ($centresList as $ctr): ?>
                                                <?php $cName = $ctr['ville'] . ' - ' . $ctr['nom']; ?>
                                                <option value="<?= htmlspecialchars($cName) ?>" <?= ($r['centre'] === $cName) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($ctr['ville'] . ' - ' . $ctr['nom']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" name="motif" class="form-control form-control-sm agent-motif <?= $r['statut'] === 'rejete' ? '' : 'd-none' ?>" placeholder="Motif du rejet..." value="<?= htmlspecialchars($r['motif_rejet'] ?? '') ?>" style="max-width: 140px;">
                                        <button class="btn btn-sm btn-success py-1 px-2" type="submit" title="Enregistrer">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                                Aucune candidature trouvée selon vos critères.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleAgentMotif(selectElem) {
    const form = selectElem.closest('form');
    const motifInput = form.querySelector('.agent-motif');
    if (selectElem.value === 'rejete') {
        motifInput.classList.remove('d-none');
        motifInput.focus();
    } else {
        motifInput.classList.add('d-none');
    }
}
</script>

<?php require '../includes/footer.php'; ?>
