<?php
require '../config/database.php';
require '../includes/auth.php';
require_role('admin');

$msgSuccess = '';
$msgError = '';

$editId = (int)($_GET['edit'] ?? 0);
$editConcours = null;

if ($editId > 0) {
    $stE = $pdo->prepare("SELECT * FROM concours WHERE id = ?");
    $stE->execute([$editId]);
    $editConcours = $stE->fetch();
}

// Enregistrement / Modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_concours'])) {
    $id             = (int)($_POST['id'] ?? 0);
    $titre          = trim($_POST['titre'] ?? '');
    $session        = trim($_POST['session'] ?? date('Y'));
    $description    = trim($_POST['description'] ?? '');
    $date_debut     = $_POST['date_debut'] ?? '';
    $date_fin       = $_POST['date_fin'] ?? '';
    $age_min        = (int)($_POST['age_min'] ?? 18);
    $age_max        = (int)($_POST['age_max'] ?? 35);
    $diplome_requis = trim($_POST['diplome_requis'] ?? '');
    $frais          = (int)($_POST['frais'] ?? 0);
    $places         = (int)($_POST['places'] ?? 0);
    $statut         = $_POST['statut'] ?? 'brouillon';

    if (empty($titre) || empty($date_debut) || empty($date_fin)) {
        $msgError = 'Le titre et les dates de début/fin sont obligatoires.';
    } else {
        if ($id > 0) {
            $st = $pdo->prepare("UPDATE concours SET titre = ?, session = ?, description = ?, date_debut = ?, date_fin = ?, age_min = ?, age_max = ?, diplome_requis = ?, frais = ?, places = ?, statut = ? WHERE id = ?");
            $st->execute([$titre, $session, $description, $date_debut, $date_fin, $age_min, $age_max, $diplome_requis, $frais, $places, $statut, $id]);
            $msgSuccess = 'Le concours a été mis à jour avec succès.';
        } else {
            $st = $pdo->prepare("INSERT INTO concours (titre, session, description, date_debut, date_fin, age_min, age_max, diplome_requis, frais, places, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $st->execute([$titre, $session, $description, $date_debut, $date_fin, $age_min, $age_max, $diplome_requis, $frais, $places, $statut]);
            $msgSuccess = 'Le nouveau concours a été créé avec succès.';
        }
        $editConcours = null;
    }
}

// Suppression / Changement rapide de statut
if (isset($_GET['toggle_statut'])) {
    $stId = (int)$_GET['toggle_statut'];
    $newSt = $_GET['status'] ?? 'brouillon';
    if (in_array($newSt, ['brouillon', 'ouvert', 'ferme', 'publie'])) {
        $stUp = $pdo->prepare("UPDATE concours SET statut = ? WHERE id = ?");
        $stUp->execute([$newSt, $stId]);
        $msgSuccess = "Le statut du concours a été mis à jour.";
    }
}

$concoursList = $pdo->query("SELECT c.*,
                            (SELECT COUNT(*) FROM candidatures WHERE concours_id = c.id) as nb_candidats
                             FROM concours c ORDER BY created_at DESC")->fetchAll();

$title = 'Gestion des Concours — Admin';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-dark m-0"><i class="bi bi-award-fill text-primary me-2"></i>Gestion des Concours Administratifs</h1>
        <p class="text-muted m-0">Création, paramétrage des dates, frais, diplômes et ouverture des sessions.</p>
    </div>
    <?php if ($editConcours): ?>
        <a href="concours.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Créer un nouveau concours
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
    <!-- Formulaire de création / édition -->
    <div class="col-lg-5">
        <div class="card p-4 shadow-sm border-0">
            <h4 class="h5 fw-bold text-primary mb-3">
                <i class="bi <?= $editConcours ? 'bi-pencil-square' : 'bi-plus-circle-fill' ?> me-2"></i>
                <?= $editConcours ? 'Modifier le concours' : 'Nouveau Concours' ?>
            </h4>

            <form method="post" action="concours.php" class="needs-validation" novalidate>
                <input type="hidden" name="action_save_concours" value="1">
                <?php if ($editConcours): ?>
                    <input type="hidden" name="id" value="<?= $editConcours['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label fw-bold">Intitulé du concours <span class="text-danger">*</span></label>
                    <input type="text" name="titre" class="form-control" value="<?= htmlspecialchars($editConcours['titre'] ?? '') ?>" placeholder="Ex: Concours d'Administrateur" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Session</label>
                        <input type="text" name="session" class="form-control" value="<?= htmlspecialchars($editConcours['session'] ?? date('Y')) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Places ouvertes / disponibles</label>
                        <input type="number" name="places" class="form-control" value="<?= (int)($editConcours['places'] ?? 50) ?>" min="0" required>
                        <div class="form-text">Nombre de places disponibles. Ce nombre diminue à chaque inscription.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Date de début <span class="text-danger">*</span></label>
                        <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($editConcours['date_debut'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Date de clôture <span class="text-danger">*</span></label>
                        <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($editConcours['date_fin'] ?? date('Y-m-d', strtotime('+30 days'))) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Âge Minimum</label>
                        <input type="number" name="age_min" class="form-control" value="<?= (int)($editConcours['age_min'] ?? 18) ?>" min="16" max="60">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Âge Maximum</label>
                        <input type="number" name="age_max" class="form-control" value="<?= (int)($editConcours['age_max'] ?? 35) ?>" min="18" max="65">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Diplôme exigé</label>
                    <input type="text" name="diplome_requis" class="form-control" value="<?= htmlspecialchars($editConcours['diplome_requis'] ?? 'Licence') ?>" placeholder="Ex: Licence, BAC, BTS">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Frais de dossier (FCFA)</label>
                        <input type="number" name="frais" class="form-control" value="<?= (int)($editConcours['frais'] ?? 10000) ?>" step="500">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Statut</label>
                        <select name="statut" class="form-select">
                            <option value="brouillon" <?= ($editConcours['statut'] ?? '') === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
                            <option value="ouvert" <?= ($editConcours['statut'] ?? '') === 'ouvert' ? 'selected' : '' ?>>Ouvert (Inscriptions)</option>
                            <option value="ferme" <?= ($editConcours['statut'] ?? '') === 'ferme' ? 'selected' : '' ?>>Fermé</option>
                            <option value="publie" <?= ($editConcours['statut'] ?? '') === 'publie' ? 'selected' : '' ?>>Publié</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Description & Instructions</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Informations et conditions particulières..."><?= htmlspecialchars($editConcours['description'] ?? '') ?></textarea>
                </div>

                <button class="btn btn-primary w-100 fw-bold py-2" type="submit">
                    <i class="bi bi-check-circle me-1"></i><?= $editConcours ? 'Mettre à jour' : 'Créer le concours' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Liste des concours -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold text-dark m-0"><i class="bi bi-list-stars me-2 text-primary"></i>Liste des concours enregistrés</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Intitulé</th>
                                <th>Session</th>
                                <th>Places restantes</th>
                                <th>Statut</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($concoursList as $c): ?>
                                <tr>
                                    <td class="ps-3">
                                        <strong class="text-dark d-block"><?= htmlspecialchars($c['titre']) ?></strong>
                                        <small class="text-muted"><i class="bi bi-people me-1"></i><?= (int)$c['nb_candidats'] ?> candidat(s) | Clôture: <?= date('d/m/Y', strtotime($c['date_fin'])) ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($c['session']) ?></span></td>
                                    <td class="fw-bold <?= (int)$c['places'] > 0 ? 'text-primary' : 'text-danger' ?>"><?= (int)$c['places'] ?> place(s)</td>
                                    <td>
                                        <?php if ($c['statut'] === 'ouvert'): ?>
                                            <span class="badge bg-success">OUVERT</span>
                                        <?php elseif ($c['statut'] === 'ferme'): ?>
                                            <span class="badge bg-secondary">FERMÉ</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark"><?= strtoupper($c['statut']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group btn-group-sm">
                                            <a href="concours.php?edit=<?= $c['id'] ?>" class="btn btn-outline-primary" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="epreuves.php?concours_id=<?= $c['id'] ?>" class="btn btn-outline-info" title="Épreuves & Coefficients">
                                                <i class="bi bi-journal-text"></i>
                                            </a>
                                            <?php if ($c['statut'] === 'ouvert'): ?>
                                                <a href="concours.php?toggle_statut=<?= $c['id'] ?>&status=ferme" class="btn btn-outline-warning" title="Fermer les inscriptions">
                                                    <i class="bi bi-lock"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="concours.php?toggle_statut=<?= $c['id'] ?>&status=ouvert" class="btn btn-outline-success" title="Ouvrir les inscriptions">
                                                    <i class="bi bi-unlock"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
