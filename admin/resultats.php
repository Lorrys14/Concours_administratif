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

// 1. Action : Enregistrer les notes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_notes'])) {
    $notesInput = $_POST['notes'] ?? []; // array[candidature_id][epreuve_id] = note

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
    $msgSuccess = "Les notes ont été enregistrées avec succès.";
}

// 2. Action : Calculer Moyennes & Deliberation (Admis / Liste d'attente / Non admis)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_calculate'])) {
    if ($currentConcours) {
        $stEpr = $pdo->prepare("SELECT * FROM epreuves WHERE concours_id = ?");
        $stEpr->execute([$selectedConcoursId]);
        $epreuves = $stEpr->fetchAll();

        $stCands = $pdo->prepare("SELECT id, user_id, numero_candidat FROM candidatures WHERE concours_id = ? AND statut IN ('valide', 'convoque', 'admis', 'non_admis', 'soumis', 'en_verification')");
        $stCands->execute([$selectedConcoursId]);
        $cands = $stCands->fetchAll();

        $places = (int)$currentConcours['places'];
        $totalCoef = 0;
        foreach ($epreuves as $ep) {
            $totalCoef += (float)$ep['coefficient'];
        }

        if ($totalCoef <= 0) {
            $msgError = "Impossible de calculer : aucune épreuve avec coefficient définie pour ce concours.";
        } else {
            $candAverages = [];

            foreach ($cands as $c) {
                $candId = $c['id'];
                $totalWeightedNotes = 0;

                foreach ($epreuves as $ep) {
                    $stCheckNote = $pdo->prepare("SELECT note FROM notes WHERE candidature_id = ? AND epreuve_id = ?");
                    $stCheckNote->execute([$candId, $ep['id']]);
                    $nRow = $stCheckNote->fetch();
                    $note = $nRow ? (float)$nRow['note'] : 0.0;
                    $totalWeightedNotes += ($note * (float)$ep['coefficient']);
                }

                $avg = round($totalWeightedNotes / $totalCoef, 2);
                $candAverages[] = [
                    'candidature_id' => $candId,
                    'user_id' => $c['user_id'],
                    'numero' => $c['numero_candidat'],
                    'moyenne' => $avg
                ];
            }

            // Trier par moyenne décroissante
            usort($candAverages, function ($a, $b) {
                return $b['moyenne'] <=> $a['moyenne'];
            });

            // Attribution du rang et décision
            $rang = 1;
            foreach ($candAverages as $item) {
                $decision = 'non_admis';
                if ($rang <= $places && $item['moyenne'] >= 10.00) {
                    $decision = 'admis';
                } elseif ($rang <= ($places + 10) && $item['moyenne'] >= 10.00) {
                    $decision = 'liste_attente';
                }

                // Insertion ou Mise à jour dans resultats
                $stRes = $pdo->prepare("INSERT INTO resultats (candidature_id, moyenne, rang, decision, publie)
                                        VALUES (?, ?, ?, ?, 0)
                                        ON DUPLICATE KEY UPDATE moyenne = VALUES(moyenne), rang = VALUES(rang), decision = VALUES(decision)");
                $stRes->execute([$item['candidature_id'], $item['moyenne'], $rang, $decision]);

                // Mise à jour du statut dans candidatures
                $newStatut = ($decision === 'admis') ? 'admis' : (($decision === 'liste_attente') ? 'convoque' : 'non_admis');
                $stUpCand = $pdo->prepare("UPDATE candidatures SET statut = ? WHERE id = ?");
                $stUpCand->execute([$newStatut, $item['candidature_id']]);

                $rang++;
            }

            $msgSuccess = "Calcul des moyennes et délibération effectués avec succès pour " . count($candAverages) . " candidat(s).";
        }
    }
}

// 3. Action : Publier / Masquer les résultats
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_toggle_publish'])) {
    $pubState = (int)$_POST['pub_state'];
    $stPub = $pdo->prepare("UPDATE resultats r
                            JOIN candidatures c ON c.id = r.candidature_id
                            SET r.publie = ?
                            WHERE c.concours_id = ?");
    $stPub->execute([$pubState, $selectedConcoursId]);

    // Notification aux candidats si publiés
    if ($pubState === 1) {
        $stNotifyAll = $pdo->prepare("SELECT DISTINCT user_id FROM candidatures WHERE concours_id = ?");
        $stNotifyAll->execute([$selectedConcoursId]);
        $usersToNotify = $stNotifyAll->fetchAll();

        foreach ($usersToNotify as $uItem) {
            $stNotif = $pdo->prepare("INSERT INTO notifications (user_id, titre, message) VALUES (?, ?, ?)");
            $stNotif->execute([
                $uItem['user_id'],
                'Notification',
                "Les résultats officiels sont publiés. Consultez votre espace."
            ]);
        }
    }

    $msgSuccess = $pubState === 1 ? "Les résultats ont été publiés avec succès !" : "Les résultats ont été masqués du public.";
}

// Charger épreuves et candidats
$epreuves = [];
$candidats = [];
$isPublished = false;

if ($selectedConcoursId > 0) {
    $stE = $pdo->prepare("SELECT * FROM epreuves WHERE concours_id = ? ORDER BY id");
    $stE->execute([$selectedConcoursId]);
    $epreuves = $stE->fetchAll();

    $stCandList = $pdo->prepare("SELECT c.*, u.nom, u.prenom, r.moyenne, r.rang, r.decision, r.publie
                                 FROM candidatures c
                                 JOIN users u ON u.id = c.user_id
                                 LEFT JOIN resultats r ON r.candidature_id = c.id
                                 WHERE c.concours_id = ?
                                 ORDER BY r.rang ASC, c.created_at DESC");
    $stCandList->execute([$selectedConcoursId]);
    $candidats = $stCandList->fetchAll();

    // Charger les notes déjà saisies
    $existingNotes = [];
    $stAllNotes = $pdo->prepare("SELECT n.* FROM notes n JOIN candidatures c ON c.id = n.candidature_id WHERE c.concours_id = ?");
    $stAllNotes->execute([$selectedConcoursId]);
    foreach ($stAllNotes->fetchAll() as $nRow) {
        $existingNotes[$nRow['candidature_id']][$nRow['epreuve_id']] = $nRow['note'];
    }

    foreach ($candidats as $cd) {
        if (!empty($cd['publie']) && $cd['publie'] == 1) {
            $isPublished = true;
            break;
        }
    }
}

$title = 'Publication des résultats — Admin';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-dark m-0"><i class="bi bi-trophy-fill me-2 text-primary"></i>Gestion & Publication des résultats</h1>
        <p class="text-muted m-0">Saisie des notes, calcul automatique du classement et publication officielle.</p>
    </div>
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
    <form method="get" action="resultats.php" class="row g-3 align-items-center">
        <div class="col-md-8">
            <label class="form-label fw-bold m-0 me-2">Choisir un concours :</label>
            <select name="concours_id" class="form-select form-select-lg" onchange="this.form.submit()">
                <?php foreach ($concoursList as $co): ?>
                    <option value="<?= $co['id'] ?>" <?= $selectedConcoursId === (int)$co['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($co['titre']) ?> (Session <?= htmlspecialchars($co['session']) ?> - Places: <?= (int)$co['places'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 text-md-end">
            <span class="badge <?= $isPublished ? 'bg-success' : 'bg-warning text-dark' ?> p-2 fs-6">
                <i class="bi <?= $isPublished ? 'bi-globe' : 'bi-lock-fill' ?> me-1"></i>
                Statut : <?= $isPublished ? 'Publié au grand public' : 'Non publié (Brouillon)' ?>
            </span>
        </div>
    </form>
</div>

<?php if ($currentConcours): ?>

    <!-- Actions de délibération & Publication -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex gap-2">
            <form method="post" action="resultats.php?concours_id=<?= $selectedConcoursId ?>">
                <input type="hidden" name="action_calculate" value="1">
                <button class="btn btn-warning fw-bold text-dark" type="submit" onclick="return confirm('Calculer les moyennes et générer le classement pour ce concours ?')">
                    <i class="bi bi-calculator me-1"></i>1. Calculer Moyennes & Délibérer
                </button>
            </form>
        </div>

        <form method="post" action="resultats.php?concours_id=<?= $selectedConcoursId ?>">
            <input type="hidden" name="action_toggle_publish" value="1">
            <?php if ($isPublished): ?>
                <input type="hidden" name="pub_state" value="0">
                <button class="btn btn-outline-danger fw-bold" type="submit">
                    <i class="bi bi-eye-slash me-1"></i>Masquer les résultats
                </button>
            <?php else: ?>
                <input type="hidden" name="pub_state" value="1">
                <button class="btn btn-success fw-bold px-4" type="submit" onclick="return confirm('Voulez-vous publier officiellement les résultats de ce concours ?')">
                    <i class="bi bi-send-check me-1"></i>2. Publier Officiellement les Résultats
                </button>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tableau de saisie des notes -->
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold text-dark m-0"><i class="bi bi-pencil-square me-2 text-primary"></i>Saisie des notes des épreuves (sur 20)</h5>
        </div>
        <div class="card-body p-0">
            <form method="post" action="resultats.php?concours_id=<?= $selectedConcoursId ?>">
                <input type="hidden" name="action_save_notes" value="1">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">N° Candidat</th>
                                <th>Nom & Prénom</th>
                                <?php foreach ($epreuves as $ep): ?>
                                    <th class="text-center" style="min-width: 120px;">
                                        <?= htmlspecialchars($ep['nom']) ?><br>
                                        <small class="text-muted fw-normal">(Coef. <?= $ep['coefficient'] ?>)</small>
                                    </th>
                                <?php endforeach; ?>
                                <th class="text-center">Moyenne</th>
                                <th class="text-center">Rang</th>
                                <th class="text-center">Décision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($candidats as $cand): ?>
                                <tr>
                                    <td class="ps-3">
                                        <span class="badge bg-light text-dark font-monospace border"><?= htmlspecialchars($cand['numero_candidat']) ?></span>
                                    </td>
                                    <td class="fw-semibold"><?= htmlspecialchars($cand['nom'] . ' ' . $cand['prenom']) ?></td>
                                    <?php foreach ($epreuves as $ep): ?>
                                        <?php $val = $existingNotes[$cand['id']][$ep['id']] ?? ''; ?>
                                        <td class="text-center">
                                            <input type="number"
                                                   step="0.25" min="0" max="20"
                                                   name="notes[<?= $cand['id'] ?>][<?= $ep['id'] ?>]"
                                                   class="form-control form-control-sm text-center mx-auto"
                                                   style="max-width: 90px;"
                                                   value="<?= htmlspecialchars($val) ?>" placeholder="0.0">
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="text-center fw-bold text-primary">
                                        <?= $cand['moyenne'] !== null ? number_format($cand['moyenne'], 2, ',', ' ') . ' / 20' : '-' ?>
                                    </td>
                                    <td class="text-center fw-bold">
                                        <?= $cand['rang'] ? '#' . $cand['rang'] : '-' ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($cand['decision'] === 'admis'): ?>
                                            <span class="badge bg-success px-3 py-1">ADMIS</span>
                                        <?php elseif ($cand['decision'] === 'liste_attente'): ?>
                                            <span class="badge bg-warning text-dark px-2 py-1">LISTE D'ATTENTE</span>
                                        <?php elseif ($cand['decision'] === 'non_admis'): ?>
                                            <span class="badge bg-danger px-2 py-1">NON ADMIS</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">En attente</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($candidats)): ?>
                                <tr>
                                    <td colspan="<?= count($epreuves) + 5 ?>" class="text-center text-muted py-4">
                                        Aucune candidature trouvée pour ce concours.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($candidats)): ?>
                    <div class="p-3 bg-light border-top text-end">
                        <button class="btn btn-primary fw-bold px-4" type="submit">
                            <i class="bi bi-save me-1"></i>Enregistrer les notes
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

<?php endif; ?>

<?php require '../includes/footer.php'; ?>
