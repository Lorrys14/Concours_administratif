<?php
require '../config/database.php';
require '../includes/auth.php';
require_role('agent');

$concoursList = $pdo->query("SELECT * FROM concours ORDER BY date_debut DESC")->fetchAll();
$selectedConcoursId = (int)($_GET['concours_id'] ?? ($concoursList[0]['id'] ?? 0));
$search = trim($_GET['search'] ?? '');

$msgSuccess = '';
$msgError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_notes'])) {
    $notesInput = $_POST['notes'] ?? [];

    foreach ($notesInput as $candId => $epreuveNotes) {
        foreach ($epreuveNotes as $epreuveId => $val) {
            if ($val === '' || $val === null) continue;
            $noteVal = (float)str_replace(',', '.', $val);
            if ($noteVal < 0) $noteVal = 0;
            if ($noteVal > 20) $noteVal = 20;

            $stN = $pdo->prepare("INSERT INTO notes (candidature_id, epreuve_id, note)
                                  VALUES (?, ?, ?)
                                  ON DUPLICATE KEY UPDATE note = VALUES(note)");
            $stN->execute([(int)$candId, (int)$epreuveId, $noteVal]);
        }
    }
    $msgSuccess = "Toutes les notes saisies ont été enregistrées avec succès.";
}

$epreuves = [];
$candidats = [];
$existingNotes = [];
$totalCoef = 0;

if ($selectedConcoursId > 0) {
    $stE = $pdo->prepare("SELECT * FROM epreuves WHERE concours_id = ? ORDER BY id");
    $stE->execute([$selectedConcoursId]);
    $epreuves = $stE->fetchAll();

    foreach ($epreuves as $ep) {
        $totalCoef += (float)$ep['coefficient'];
    }

    $sqlCand = "SELECT c.*, u.nom, u.prenom, u.email
                FROM candidatures c
                JOIN users u ON u.id = c.user_id
                WHERE c.concours_id = ?";
    $params = [$selectedConcoursId];

    if (!empty($search)) {
        $sqlCand .= " AND (c.numero_candidat LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $sqlCand .= " ORDER BY c.numero_candidat ASC";
    $stCandList = $pdo->prepare($sqlCand);
    $stCandList->execute($params);
    $candidats = $stCandList->fetchAll();

    $stAllNotes = $pdo->prepare("SELECT n.* FROM notes n JOIN candidatures c ON c.id = n.candidature_id WHERE c.concours_id = ?");
    $stAllNotes->execute([$selectedConcoursId]);
    foreach ($stAllNotes->fetchAll() as $nRow) {
        $existingNotes[$nRow['candidature_id']][$nRow['epreuve_id']] = $nRow['note'];
    }
}

$title = 'Saisie des Notes — Agent';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-dark m-0"><i class="bi bi-pencil-square text-success me-2"></i>Saisie & Correction des Notes</h1>
        <p class="text-muted m-0">Espace réservé à l'agent de correction pour la saisie des notes d'épreuves.</p>
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

<!-- Sélection du concours & Barre de recherche -->
<div class="card p-3 shadow-sm border-0 mb-4 bg-light">
    <form method="get" action="notes.php" class="row g-3 align-items-center">
        <div class="col-md-6">
            <label class="form-label fw-bold m-0 me-2">Choisir un concours :</label>
            <select name="concours_id" class="form-select form-select-lg" onchange="this.form.submit()">
                <?php foreach ($concoursList as $co): ?>
                    <option value="<?= $co['id'] ?>" <?= $selectedConcoursId === (int)$co['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($co['titre']) ?> (Session <?= htmlspecialchars($co['session']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold m-0 me-2">Rechercher un candidat :</label>
            <div class="input-group">
                <input type="text" name="search" class="form-control form-control-lg" placeholder="N° Candidat, Nom ou Prénom..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-success fw-bold" type="submit">Rechercher</button>
                <?php if (!empty($search)): ?>
                    <a href="notes.php?concours_id=<?= $selectedConcoursId ?>" class="btn btn-outline-secondary">Effacer</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<?php if (empty($epreuves)): ?>
    <div class="alert alert-warning text-center p-4 border-0 shadow-sm">
        <i class="bi bi-exclamation-circle fs-1 d-block mb-2 text-warning"></i>
        <strong>Aucune épreuve configurée pour ce concours.</strong>
        <p class="m-0 mt-1">L'administrateur doit définir les épreuves et coefficients avant la saisie des notes.</p>
    </div>
<?php else: ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold text-dark m-0"><i class="bi bi-journal-check text-success me-2"></i>Bordereau de notes (<?= count($candidats) ?> candidat(s) | Coef. Total : <?= $totalCoef ?>)</h5>
            <span class="badge bg-light text-dark border"><?= count($epreuves) ?> Épreuve(s)</span>
        </div>
        <div class="card-body p-0">
            <form method="post" action="notes.php?concours_id=<?= $selectedConcoursId ?>&search=<?= urlencode($search) ?>">
                <input type="hidden" name="action_save_notes" value="1">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">N° Candidat</th>
                                <th>Nom & Prénom</th>
                                <?php foreach ($epreuves as $ep): ?>
                                    <th class="text-center" style="min-width: 130px;">
                                        <?= htmlspecialchars($ep['nom']) ?><br>
                                        <small class="text-muted fw-normal">(Coef. <?= $ep['coefficient'] ?>)</small>
                                    </th>
                                <?php endforeach; ?>
                                <th class="text-center bg-light">Moyenne Prov.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($candidats as $cand): ?>
                                <tr>
                                    <td class="ps-3">
                                        <span class="badge bg-light text-dark font-monospace border"><?= htmlspecialchars($cand['numero_candidat']) ?></span>
                                    </td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($cand['nom'] . ' ' . $cand['prenom']) ?></td>

                                    <?php
                                    $sumNotesWeighted = 0;
                                    $hasAllNotes = true;
                                    foreach ($epreuves as $ep):
                                        $val = $existingNotes[$cand['id']][$ep['id']] ?? '';
                                        if ($val !== '') {
                                            $sumNotesWeighted += ((float)$val * (float)$ep['coefficient']);
                                        } else {
                                            $hasAllNotes = false;
                                        }
                                    ?>
                                        <td class="text-center">
                                            <input type="number"
                                                step="0.25" min="0" max="20"
                                                name="notes[<?= $cand['id'] ?>][<?= $ep['id'] ?>]"
                                                class="form-control form-control-sm text-center mx-auto"
                                                style="max-width: 95px;"
                                                value="<?= htmlspecialchars($val) ?>" placeholder="0.00">
                                        </td>
                                    <?php endforeach; ?>

                                    <td class="text-center fw-bold text-primary bg-light">
                                        <?php if ($totalCoef > 0 && $hasAllNotes): ?>
                                            <?= number_format($sumNotesWeighted / $totalCoef, 2, ',', ' ') ?> / 20
                                        <?php else: ?>
                                            <span class="text-muted small">Incomplète</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($candidats)): ?>
                                <tr>
                                    <td colspan="<?= count($epreuves) + 3 ?>" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-2 text-secondary d-block mb-2"></i>
                                        Aucun candidat trouvé pour ce concours.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($candidats)): ?>
                    <div class="p-3 bg-light border-top text-end">
                        <button class="btn btn-success fw-bold px-4 fs-5" type="submit">
                            <i class="bi bi-save me-2"></i>Enregistrer toutes les notes
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

<?php endif; ?>

<?php require '../includes/footer.php'; ?>
