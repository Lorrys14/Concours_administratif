<?php
require '../config/database.php';
require '../includes/auth.php';
require_role('admin');

$msgSuccess = '';
$msgError   = '';

$allowedStatuses = ['brouillon', 'soumis', 'en_verification', 'valide', 'rejete', 'convoque', 'admis', 'non_admis'];

// Liste des centres d'examen
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

        $q = $pdo->prepare("SELECT user_id, numero_candidat FROM candidatures WHERE id = ?");
        $q->execute([$id]);
        $x = $q->fetch();

        if ($x) {
            if ($statut === 'valide') {
                $msg = "Votre dossier est validé.";
            } elseif ($statut === 'rejete') {
                $msg = "Votre candidature est rejetée." . ($motif ? " Motif : $motif" : "");
            } elseif ($statut === 'convoque') {
                $msg = "Votre dossier est retenu. Vous êtes convoqué.";
            } elseif ($statut === 'admis') {
                $msg = "Félicitations, votre candidature est admise !";
            } elseif ($statut === 'non_admis') {
                $msg = "Votre candidature n'a pas été retenue.";
            } else {
                $msg = "Votre dossier est passé au statut : " . $statut . ".";
            }

            $n = $pdo->prepare("INSERT INTO notifications (user_id, titre, message) VALUES (?, ?, ?)");
            $n->execute([$x['user_id'], 'Notification', $msg]);
        }

        $msgSuccess = "Statut de la candidature $id mis à jour avec succès.";
    } else {
        $msgError = "Statut non valide ou candidature introuvable.";
    }
}

// Filtre par statut ou recherche
$search = trim($_GET['search'] ?? '');
$filterStatut = trim($_GET['filter_statut'] ?? '');

$sql = "SELECT c.*, u.nom, u.prenom, u.email, co.titre
        FROM candidatures c
        JOIN users u ON u.id = c.user_id
        JOIN concours co ON co.id = c.concours_id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (c.numero_candidat LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)";
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

$title = 'Gestion des candidatures — Admin';
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
        <h1 class="h2 fw-bold text-dark m-0"><i class="bi bi-kanban me-2 text-primary"></i>Gestion des candidatures</h1>
        <p class="text-muted m-0">Examinez, validez ou rejetez les dossiers des candidats.</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Retour au tableau de bord
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

<!-- Barre de recherche et filtre -->
<div class="card p-3 shadow-sm border-0 mb-4 bg-light">
    <form method="get" action="candidatures.php" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Rechercher par N° candidat, Nom, Prénom..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        <div class="col-md-4">
            <select name="filter_statut" class="form-select">
                <option value="">-- Tous les statuts --</option>
                <?php foreach ($allowedStatuses as $stOption): ?>
                    <option value="<?= $stOption ?>" <?= $filterStatut === $stOption ? 'selected' : '' ?>>
                        <?= ucfirst(str_replace('_', ' ', $stOption)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary w-100 fw-semibold" type="submit">Filtrer</button>
            <?php if (!empty($search) || !empty($filterStatut)): ?>
                <a href="candidatures.php" class="btn btn-outline-secondary">Réinitialiser</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">N° Candidat</th>
                        <th>Candidat</th>
                        <th>Concours</th>
                        <th>Statut actuel</th>
                        <th class="pe-3" style="min-width: 320px;">Action / Modification</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="ps-3">
                            <span class="badge bg-light text-dark font-monospace border"><?= htmlspecialchars($r['numero_candidat']) ?></span>
                        </td>
                        <td>
                            <strong class="text-dark d-block"><?= htmlspecialchars($r['nom'] . ' ' . $r['prenom']) ?></strong>
                            <small class="text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($r['email']) ?></small><br>
                            <a href="candidat_detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-info mt-1 py-0 px-2 style-btn-doc">
                                <i class="bi bi-folder2-open me-1"></i>Dossier & Pièces
                            </a>
                        </td>
                        <td>
                            <span class="fw-medium text-dark"><?= htmlspecialchars($r['titre']) ?></span><br>
                            <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($r['centre'] ?? 'Non défini') ?></small>
                        </td>
                        <td>
                            <span class="badge <?= getAdminStatusBadgeClass($r['statut']) ?> px-2 py-1">
                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $r['statut']))) ?>
                            </span>
                            <?php if (!empty($r['motif_rejet'])): ?>
                                <small class="d-block text-danger mt-1" style="font-size: 0.8rem;">
                                    Motif: <?= htmlspecialchars($r['motif_rejet']) ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td class="pe-3">
                            <form method="post" action="candidatures.php" class="d-flex flex-column gap-1">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <div class="d-flex gap-1 flex-wrap">
                                    <select name="statut" class="form-select form-select-sm" style="min-width: 120px;" onchange="toggleMotifInput(this)">
                                        <option value="soumis" <?= $r['statut'] === 'soumis' ? 'selected' : '' ?>>Soumis</option>
                                        <option value="en_verification" <?= $r['statut'] === 'en_verification' ? 'selected' : '' ?>>En vérification</option>
                                        <option value="valide" <?= $r['statut'] === 'valide' ? 'selected' : '' ?>>Valider</option>
                                        <option value="convoque" <?= $r['statut'] === 'convoque' ? 'selected' : '' ?>>Convoquer</option>
                                        <option value="admis" <?= $r['statut'] === 'admis' ? 'selected' : '' ?>>Admettre</option>
                                        <option value="non_admis" <?= $r['statut'] === 'non_admis' ? 'selected' : '' ?>>Non admis</option>
                                        <option value="rejete" <?= $r['statut'] === 'rejete' ? 'selected' : '' ?>>Rejeter</option>
                                    </select>
                                    <select name="centre" class="form-select form-select-sm" style="min-width: 140px;">
                                        <option value="">-- Centre --</option>
                                        <?php foreach ($centresList as $ctr): ?>
                                            <?php $cName = $ctr['ville'] . ' - ' . $ctr['nom']; ?>
                                            <option value="<?= htmlspecialchars($cName) ?>" <?= ($r['centre'] === $cName) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($ctr['ville'] . ' - ' . $ctr['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-primary fw-semibold px-2" type="submit">
                                        Enregistrer
                                    </button>
                                </div>
                                <input name="motif"
                                       class="form-control form-control-sm motif-input mt-1 <?= $r['statut'] === 'rejete' ? '' : 'd-none' ?>"
                                       placeholder="Motif (ex: Pièce d'identité illisible)"
                                       value="<?= htmlspecialchars($r['motif_rejet'] ?? '') ?>">
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary"></i>
                            Aucune candidature ne correspond à votre recherche.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleMotifInput(selectElem) {
    const form = selectElem.closest('form');
    const motifInput = form.querySelector('.motif-input');
    if (selectElem.value === 'rejete') {
        motifInput.classList.remove('d-none');
        motifInput.focus();
    } else {
        motifInput.classList.add('d-none');
    }
}
</script>

<?php require '../includes/footer.php'; ?>
