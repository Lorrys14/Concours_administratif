<?php
require '../config/database.php';
require '../includes/auth.php';
require_role('admin');

$msgSuccess = '';
$msgError = '';

$editId = (int)($_GET['edit'] ?? 0);
$editCentre = null;

if ($editId > 0) {
    $stE = $pdo->prepare("SELECT * FROM centres WHERE id = ?");
    $stE->execute([$editId]);
    $editCentre = $stE->fetch();
}

// Action : Ajouter / Modifier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_centre'])) {
    $id       = (int)($_POST['id'] ?? 0);
    $nom      = trim($_POST['nom'] ?? '');
    $ville    = trim($_POST['ville'] ?? '');
    $adresse  = trim($_POST['adresse'] ?? '');
    $capacite = (int)($_POST['capacite'] ?? 500);

    if (empty($nom) || empty($ville)) {
        $msgError = "Le nom du centre et la ville sont obligatoires.";
    } else {
        if ($id > 0) {
            $st = $pdo->prepare("UPDATE centres SET nom = ?, ville = ?, adresse = ?, capacite = ? WHERE id = ?");
            $st->execute([$nom, $ville, $adresse, $capacite, $id]);
            $msgSuccess = "Le centre d'examen a été mis à jour.";
        } else {
            $st = $pdo->prepare("INSERT INTO centres (nom, ville, adresse, capacite) VALUES (?, ?, ?, ?)");
            $st->execute([$nom, $ville, $adresse, $capacite]);
            $msgSuccess = "Le centre d'examen \"$nom\" a été créé avec succès.";
        }
        $editCentre = null;
    }
}

// Action : Supprimer
if (isset($_GET['delete_centre'])) {
    $delId = (int)$_GET['delete_centre'];
    $stDel = $pdo->prepare("DELETE FROM centres WHERE id = ?");
    $stDel->execute([$delId]);
    $msgSuccess = "Le centre d'examen a été supprimé.";
}

$centresList = $pdo->query("SELECT * FROM centres ORDER BY ville ASC, nom ASC")->fetchAll();

$title = 'Gestion des Centres — Admin';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-dark m-0"><i class="bi bi-building text-primary me-2"></i>Gestion des Centres de Composition</h1>
        <p class="text-muted m-0">Définissez les établissements, villes et capacités d'accueil pour le déroulement des épreuves.</p>
    </div>
    <?php if ($editCentre): ?>
        <a href="centres.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Nouveau centre
        </a>
    <?php endif; ?>
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

<div class="row g-4">
    <!-- Formulaire -->
    <div class="col-lg-5">
        <div class="card p-4 shadow-sm border-0">
            <h4 class="h5 fw-bold text-primary mb-3">
                <i class="bi <?= $editCentre ? 'bi-pencil-square' : 'bi-plus-circle-fill' ?> me-2"></i>
                <?= $editCentre ? 'Modifier le centre' : 'Nouveau Centre d\'Examen' ?>
            </h4>

            <form method="post" action="centres.php" class="needs-validation" novalidate>
                <input type="hidden" name="action_save_centre" value="1">
                <?php if ($editCentre): ?>
                    <input type="hidden" name="id" value="<?= $editCentre['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label fw-bold">Nom du centre / établissement <span class="text-danger">*</span></label>
                    <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($editCentre['nom'] ?? '') ?>" placeholder="Ex: Lycée Moderne de Cocody" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Ville / Commune <span class="text-danger">*</span></label>
                    <input type="text" name="ville" class="form-control" value="<?= htmlspecialchars($editCentre['ville'] ?? '') ?>" placeholder="Ex: Abidjan - Cocody" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Adresse / Localisation</label>
                    <input type="text" name="adresse" class="form-control" value="<?= htmlspecialchars($editCentre['adresse'] ?? '') ?>" placeholder="Ex: Boulevard de France">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Capacité totale d'accueil (places)</label>
                    <input type="number" name="capacite" class="form-control" value="<?= (int)($editCentre['capacite'] ?? 500) ?>" min="10" required>
                </div>

                <button class="btn btn-primary w-100 fw-bold py-2" type="submit">
                    <i class="bi bi-save me-1"></i><?= $editCentre ? 'Mettre à jour' : 'Enregistrer le centre' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Liste -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold text-dark m-0"><i class="bi bi-list-ul me-2 text-primary"></i>Centres configurés</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Centre</th>
                                <th>Ville</th>
                                <th class="text-center">Capacité</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($centresList as $ctr): ?>
                                <tr>
                                    <td class="ps-3">
                                        <strong class="text-dark d-block"><?= htmlspecialchars($ctr['nom']) ?></strong>
                                        <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($ctr['adresse'] ?? 'Adresse non spécifiée') ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($ctr['ville']) ?></span></td>
                                    <td class="text-center fw-bold text-primary"><?= number_format($ctr['capacite'], 0, ',', ' ') ?> places</td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group btn-group-sm">
                                            <a href="centres.php?edit=<?= $ctr['id'] ?>" class="btn btn-outline-primary" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="centres.php?delete_centre=<?= $ctr['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Supprimer ce centre ?')" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($centresList)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Aucun centre d'examen configuré.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>