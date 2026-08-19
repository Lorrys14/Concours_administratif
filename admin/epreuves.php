<?php
require '../config/database.php';
require '../includes/auth.php';
require_role('admin');

$concoursList = $pdo->query("SELECT * FROM concours ORDER BY date_debut DESC")->fetchAll();
$selectedConcoursId = (int)($_GET['concours_id'] ?? ($concoursList[0]['id'] ?? 0));

$msgSuccess = '';
$msgError = '';

$currentConcours = null;
if ($selectedConcoursId > 0) {
    $stC = $pdo->prepare("SELECT * FROM concours WHERE id = ?");
    $stC->execute([$selectedConcoursId]);
    $currentConcours = $stC->fetch();
}

// 1. Ajouter une épreuve
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_epreuve'])) {
    $nom = trim($_POST['nom'] ?? '');
    $coef = (float)str_replace(',', '.', $_POST['coefficient'] ?? 1);

    if (empty($nom) || $coef <= 0) {
        $msgError = "L'intitulé de l'épreuve et un coefficient valide (> 0) sont requis.";
    } else {
        $st = $pdo->prepare("INSERT INTO epreuves (concours_id, nom, coefficient) VALUES (?, ?, ?)");
        $st->execute([$selectedConcoursId, $nom, $coef]);
        $msgSuccess = "L'épreuve \"$nom\" a été ajoutée avec succès.";
    }
}

// 2. Supprimer une épreuve
if (isset($_GET['delete_epreuve'])) {
    $delId = (int)$_GET['delete_epreuve'];
    $stDel = $pdo->prepare("DELETE FROM epreuves WHERE id = ? AND concours_id = ?");
    $stDel->execute([$delId, $selectedConcoursId]);
    $msgSuccess = "L'épreuve a été supprimée.";
}

// Charger les épreuves du concours
$epreuves = [];
if ($selectedConcoursId > 0) {
    $stE = $pdo->prepare("SELECT * FROM epreuves WHERE concours_id = ? ORDER BY id ASC");
    $stE->execute([$selectedConcoursId]);
    $epreuves = $stE->fetchAll();
}

$title = 'Gestion des Épreuves — Admin';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-dark m-0"><i class="bi bi-journal-check text-primary me-2"></i>Gestion des Épreuves & Coefficients</h1>
        <p class="text-muted m-0">Définissez les matières et coefficients d'évaluation pour chaque concours.</p>
    </div>
    <a href="concours.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Retour à la liste des concours
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

<!-- Sélection du Concours -->
<div class="card p-3 shadow-sm border-0 mb-4 bg-light">
    <form method="get" action="epreuves.php" class="row g-3 align-items-center">
        <div class="col-md-8">
            <label class="form-label fw-bold m-0 me-2">Sélectionner un concours :</label>
            <select name="concours_id" class="form-select form-select-lg" onchange="this.form.submit()">
                <?php foreach ($concoursList as $co): ?>
                    <option value="<?= $co['id'] ?>" <?= $selectedConcoursId === (int)$co['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($co['titre']) ?> (Session <?= htmlspecialchars($co['session']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($currentConcours): ?>

    <div class="row g-4">
        <!-- Formulaire d'ajout d'épreuve -->
        <div class="col-lg-5">
            <div class="card p-4 shadow-sm border-0">
                <h4 class="h5 fw-bold text-primary mb-3"><i class="bi bi-plus-circle-fill me-2"></i>Ajouter une épreuve</h4>
                <form method="post" action="epreuves.php?concours_id=<?= $selectedConcoursId ?>" class="needs-validation" novalidate>
                    <input type="hidden" name="action_add_epreuve" value="1">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nom de l'épreuve <span class="text-danger">*</span></label>
                        <input type="text" name="nom" class="form-control" placeholder="Ex: Culture générale, Français, Mathématiques..." required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Coefficient <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" min="0.5" max="10" name="coefficient" class="form-control" value="2.0" required>
                        <div class="form-text">Pondération lors du calcul de la moyenne finale.</div>
                    </div>

                    <button class="btn btn-primary w-100 fw-bold" type="submit">
                        <i class="bi bi-save me-1"></i>Enregistrer l'épreuve
                    </button>
                </form>
            </div>
        </div>

        <!-- Liste des épreuves -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark m-0"><i class="bi bi-list-check me-2 text-primary"></i>Épreuves pour : <?= htmlspecialchars($currentConcours['titre']) ?></h5>
                    <span class="badge bg-primary px-3 py-2"><?= count($epreuves) ?> épreuve(s)</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">N°</th>
                                    <th>Nom de l'épreuve</th>
                                    <th class="text-center">Coefficient</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1;
                                $sumCoef = 0;
                                foreach ($epreuves as $ep): $sumCoef += (float)$ep['coefficient']; ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-muted"><?= $i++ ?></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($ep['nom']) ?></td>
                                        <td class="text-center"><span class="badge bg-light text-primary border font-monospace fs-6">x <?= number_format($ep['coefficient'], 1) ?></span></td>
                                        <td class="text-end pe-3">
                                            <a href="epreuves.php?concours_id=<?= $selectedConcoursId ?>&delete_epreuve=<?= $ep['id'] ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Supprimer cette épreuve ?')"
                                                title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($epreuves)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Aucune épreuve configurée pour ce concours.</td>
                                    </tr>
                                <?php else: ?>
                                    <tr class="table-light fw-bold">
                                        <td colspan="2" class="ps-3 text-dark">Total des Coefficients :</td>
                                        <td class="text-center text-primary fs-6"><?= number_format($sumCoef, 1) ?></td>
                                        <td></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php require '../includes/footer.php'; ?>