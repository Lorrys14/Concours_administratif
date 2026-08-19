<?php
require '../config/database.php';
require '../includes/auth.php';
require_role('admin');

$concoursList = $pdo->query("SELECT * FROM concours ORDER BY date_debut DESC")->fetchAll();
$selectedConcoursId = (int)($_GET['concours_id'] ?? ($concoursList[0]['id'] ?? 0));

$msgSuccess = '';
$msgError = '';

// Enregistrement des notes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_notes'])) {
    $notesInput = $_POST['notes'] ?? [];

    foreach ($notesInput as $candId => $epreuveNotes) {
        foreach ($epreuveNotes as $epreuveId => $val) {
            $noteVal = (float)str_replace(',', '.', $val);
            if ($noteVal < 0) $noteVal = 0;
            if ($noteVal > 20) $noteVal = 20;

            $stN = $pdo->prepare("INSERT INTO notes (candidature_id, epreuve_id, note) 
                                  VALUES (?, ?, ?) 
                                  ON DUPLICATE KEY UPDATE note = VALUES(note)");
            $stN->execute([(int)$candId, (int)$epreuveId, $noteVal]);
        }
    }
    $msgSuccess = "Saisie des notes enregistrée avec succès.";
}

// Charger épreuves et candidats
$epreuves = [];
$candidats = [];
$existingNotes = [];

if ($selectedConcoursId > 0) {
    $stE = $pdo->prepare("SELECT * FROM epreuves WHERE concours_id = ? ORDER BY id");
    $stE->execute([$selectedConcoursId]);
    $epreuves = $stE->fetchAll();

    $stCandList = $pdo->prepare("SELECT c.*, u.nom, u.prenom 
                                 FROM candidatures c 
                                 JOIN users u ON u.id = c.user_id 
                                 WHERE c.concours_id = ? AND c.statut IN ('valide', 'convoque', 'admis', 'non_admis', 'soumis', 'en_verification') 
                                 ORDER BY c.numero_candidat ASC");
    $stCandList->execute([$selectedConcoursId]);
    $candidats = $stCandList->fetchAll();

    $stAllNotes = $pdo->prepare("SELECT n.* FROM notes n JOIN candidatures c ON c.id = n.candidature_id WHERE c.concours_id = ?");
    $stAllNotes->execute([$selectedConcoursId]);
    foreach ($stAllNotes->fetchAll() as $nRow) {
        $existingNotes[$nRow['candidature_id']][$nRow['epreuve_id']] = $nRow['note'];
    }
}

$title = 'Saisie des Notes — Admin';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-dark m-0"><i class="bi bi-pencil-square text-primary me-2"></i>Saisie & Gestion des Notes</h1>
        <p class="text-muted m-0">Saisissez les notes obtenues par chaque candidat aux épreuves écrites et orales.</p>
    </div>
    <a href="resultats.php?concours_id=<?= $selectedConcoursId ?>" class="btn btn-warning fw-bold text-dark">
        <i class="bi bi-calculator me-1"></i>Accéder aux Délibérations & Calculs
    </a>
</div>

<?php if (!empty($msgSuccess)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($msgSuccess) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endif; ?>

<!-- Sélection du Concours -->
<div class="card p-3 shadow-sm border-0 mb-4 bg-light">
    <form method="get" action="notes.php" class="row g-3 align-items-center">
        <div class="col-md-8">
            <label class="form-label fw-bold m-0 me-2">Choisir un concours :</label>
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

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-0">
        <form method="post" action="notes.php?concours_id=<?= $selectedConcoursId ?>">
            <input type="hidden" name="action_save_notes" value="1">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">N° Candidat</th>
                            <th>Candidat</th>
                            <?php foreach ($epreuves as $ep): ?>
                                <th class="text-center" style="min-width: 140px;">
                                    <?= htmlspecialchars($ep['nom']) ?><br>
                                    <small class="text-muted fw-normal">(Coef. <?= $ep['coefficient'] ?>)</small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($candidats as $cand): ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-light text-dark font-monospace border"><?= htmlspecialchars($cand['numero_candidat']) ?></span>
                                </td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($cand['nom'] . ' ' . $cand['prenom']) ?></td>
                                <?php foreach ($epreuves as $ep): ?>
                                    <?php $val = $existingNotes[$cand['id']][$ep['id']] ?? ''; ?>
                                    <td class="text-center">
                                        <input type="number"
                                            step="0.25" min="0" max="20"
                                            name="notes[<?= $cand['id'] ?>][<?= $ep['id'] ?>]"
                                            class="form-control form-control-sm text-center mx-auto"
                                            style="max-width: 100px;"
                                            value="<?= htmlspecialchars($val) ?>" placeholder="0.00">
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($candidats)): ?>
                            <tr>
                                <td colspan="<?= count($epreuves) + 2 ?>" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-2 text-secondary d-block mb-2"></i>
                                    Aucun candidat autorisé à composer pour ce concours.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($candidats) && !empty($epreuves)): ?>
                <div class="p-3 bg-light border-top text-end">
                    <button class="btn btn-primary fw-bold px-4" type="submit">
                        <i class="bi bi-save me-1"></i>Enregistrer toutes les notes
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php require '../includes/footer.php'; ?>