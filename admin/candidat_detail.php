<?php
require '../config/database.php';
require '../includes/auth.php';
require_role(['admin', 'agent']);

$id = (int)($_GET['id'] ?? 0);

// Récupération de la candidature avec l'utilisateur et le concours
$st = $pdo->prepare("SELECT c.*, u.nom, u.prenom, u.email, u.telephone, co.titre, co.session, co.diplome_requis
                     FROM candidatures c
                     JOIN users u ON u.id = c.user_id
                     JOIN concours co ON co.id = c.concours_id
                     WHERE c.id = ?");
$st->execute([$id]);
$cand = $st->fetch();

if (!$cand) {
    die("Candidature introuvable.");
}

// Liste des centres de composition disponibles
$centresList = $pdo->query("SELECT * FROM centres ORDER BY ville ASC, nom ASC")->fetchAll();

$msgSuccess = '';
$msgError = '';

// Action de validation/rejet d'un document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_doc'])) {
    $docId = (int)($_POST['doc_id'] ?? 0);
    $docStatut = trim($_POST['doc_statut'] ?? '');

    if ($docId > 0 && in_array($docStatut, ['valide', 'rejete', 'en_attente'], true)) {
        $stDoc = $pdo->prepare("UPDATE documents SET statut = ? WHERE id = ? AND candidature_id = ?");
        $stDoc->execute([$docStatut, $docId, $id]);
        $msgSuccess = "Le statut du document a été mis à jour.";
    }
}

// Action de mise à jour du statut global de la candidature
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_candidature'])) {
    $statut = trim($_POST['statut'] ?? '');
    $motif = trim($_POST['motif_rejet'] ?? '');
    $centre = trim($_POST['centre'] ?? '');

    $allowedStatuses = ['brouillon', 'soumis', 'en_verification', 'valide', 'rejete', 'convoque', 'admis', 'non_admis'];

    if (in_array($statut, $allowedStatuses, true)) {
        if (!empty($centre)) {
            $stUp = $pdo->prepare("UPDATE candidatures SET statut = ?, motif_rejet = ?, centre = ? WHERE id = ?");
            $stUp->execute([$statut, $motif, $centre, $id]);
        } else {
            $stUp = $pdo->prepare("UPDATE candidatures SET statut = ?, motif_rejet = ? WHERE id = ?");
            $stUp->execute([$statut, $motif, $id]);
        }

        // Notification au candidat
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

        $msgSuccess = "Le statut de la candidature a été mis à jour avec succès.";
        // Recharger les données
        $st->execute([$id]);
        $cand = $st->fetch();
    }
}

// Récupération des documents joints
$stDocs = $pdo->prepare("SELECT * FROM documents WHERE candidature_id = ?");
$stDocs->execute([$id]);
$documents = $stDocs->fetchAll();

$title = 'Détails du candidat — ' . htmlspecialchars($cand['numero_candidat']);
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-dark m-0">Dossier de candidature</h1>
        <p class="text-muted m-0">Candidat : <strong><?= htmlspecialchars($cand['nom'] . ' ' . $cand['prenom']) ?></strong> (N° <?= htmlspecialchars($cand['numero_candidat']) ?>)</p>
    </div>
    <?php $backUrl = ($_SESSION['user']['role'] ?? '') === 'agent' ? '../agent/dossiers.php' : 'candidatures.php'; ?>
    <a href="<?= $backUrl ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Retour
    </a>
</div>

<?php if (!empty($msgSuccess)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($msgSuccess) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Colonne gauche : Informations Candidat & Concours -->
    <div class="col-lg-5">
        <div class="card p-4 shadow-sm border-0 mb-4 text-center">
            <h4 class="h5 fw-bold text-primary mb-3 text-start"><i class="bi bi-person-bounding-box me-2"></i>Photo du candidat</h4>
            <?php if (!empty($cand['photo']) && file_exists('../uploads/photos/' . $cand['photo'])): ?>
                <img src="../uploads/photos/<?= htmlspecialchars($cand['photo']) ?>" alt="Photo d'identité" class="img-thumbnail rounded shadow-sm mx-auto d-block" style="max-height: 180px; width: auto; object-fit: cover;">
            <?php else: ?>
                <div class="bg-light p-4 rounded border text-muted">
                    <i class="bi bi-person-circle fs-1 d-block mb-1"></i>
                    <small>Aucune photo d'identité spécifique</small>
                </div>
            <?php endif; ?>
        </div>

        <div class="card p-4 shadow-sm border-0 mb-4">
            <h4 class="h5 fw-bold text-primary mb-3"><i class="bi bi-person-lines-fill me-2"></i>Profil du candidat</h4>
            <table class="table table-borderless table-sm mb-0">
                <tr>
                    <th class="text-muted" style="width: 40%;">Nom & Prénom :</th>
                    <td class="fw-bold"><?= htmlspecialchars($cand['nom'] . ' ' . $cand['prenom']) ?></td>
                </tr>
                <tr>
                    <th class="text-muted">Email :</th>
                    <td><?= htmlspecialchars($cand['email']) ?></td>
                </tr>
                <tr>
                    <th class="text-muted">Téléphone :</th>
                    <td><?= htmlspecialchars($cand['telephone'] ?? 'Non renseigné') ?></td>
                </tr>
            </table>
        </div>

        <div class="card p-4 shadow-sm border-0 mb-4">
            <h4 class="h5 fw-bold text-primary mb-3"><i class="bi bi-award-fill me-2"></i>Détails du concours</h4>
            <table class="table table-borderless table-sm mb-0">
                <tr>
                    <th class="text-muted" style="width: 40%;">Concours :</th>
                    <td class="fw-bold"><?= htmlspecialchars($cand['titre']) ?></td>
                </tr>
                <tr>
                    <th class="text-muted">Session :</th>
                    <td><?= htmlspecialchars($cand['session']) ?></td>
                </tr>
                <tr>
                    <th class="text-muted">Diplôme requis :</th>
                    <td><?= htmlspecialchars($cand['diplome_requis'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <th class="text-muted">Centre :</th>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($cand['centre'] ?? 'Non attribué') ?></span></td>
                </tr>
                <tr>
                    <th class="text-muted">Date de soumission :</th>
                    <td><?= date('d/m/Y à H:i', strtotime($cand['date_soumission'])) ?></td>
                </tr>
            </table>
        </div>

        <!-- Modification globale du statut -->
        <div class="card p-4 shadow-sm border-0 bg-light">
            <h4 class="h5 fw-bold text-dark mb-3"><i class="bi bi-gear-fill me-2 text-primary"></i>Mise à jour du dossier</h4>
            <form method="post" action="candidat_detail.php?id=<?= $id ?>">
                <input type="hidden" name="action_candidature" value="1">
                <div class="mb-3">
                    <label class="form-label fw-bold">Statut de la candidature</label>
                    <select name="statut" class="form-select" onchange="toggleRejetMotif(this)">
                        <option value="soumis" <?= $cand['statut'] === 'soumis' ? 'selected' : '' ?>>Soumis</option>
                        <option value="en_verification" <?= $cand['statut'] === 'en_verification' ? 'selected' : '' ?>>En vérification</option>
                        <option value="valide" <?= $cand['statut'] === 'valide' ? 'selected' : '' ?>>Validé (Dossier conforme)</option>
                        <option value="convoque" <?= $cand['statut'] === 'convoque' ? 'selected' : '' ?>>Convoqué aux épreuves</option>
                        <option value="admis" <?= $cand['statut'] === 'admis' ? 'selected' : '' ?>>Admis</option>
                        <option value="non_admis" <?= $cand['statut'] === 'non_admis' ? 'selected' : '' ?>>Non admis</option>
                        <option value="rejete" <?= $cand['statut'] === 'rejete' ? 'selected' : '' ?>>Rejeté</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Attribution du centre de composition</label>
                    <select name="centre" class="form-select">
                        <option value="">-- Sélectionner un centre d'examen --</option>
                        <?php foreach ($centresList as $ctr): ?>
                            <?php $cName = $ctr['ville'] . ' - ' . $ctr['nom']; ?>
                            <option value="<?= htmlspecialchars($cName) ?>" <?= ($cand['centre'] === $cName) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ctr['ville']) ?> — <?= htmlspecialchars($ctr['nom']) ?> (Capacité : <?= (int)$ctr['capacite'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Ce centre sera attribué au candidat et figurera sur sa convocation lors de la validation.</div>
                </div>

                <div class="mb-3 <?= $cand['statut'] === 'rejete' ? '' : 'd-none' ?>" id="motifRejetGroup">
                    <label class="form-label fw-bold text-danger">Motif du rejet</label>
                    <textarea name="motif_rejet" class="form-control" rows="3" placeholder="Précisez la raison du rejet (ex: Diplôme non conforme, pièce d'identité périmée...)"><?= htmlspecialchars($cand['motif_rejet'] ?? '') ?></textarea>
                </div>

                <button class="btn btn-primary w-100 fw-bold" type="submit">
                    <i class="bi bi-save me-1"></i>Enregistrer les modifications
                </button>
            </form>
        </div>
    </div>

    <!-- Colonne droite : Pièces justificatives -->
    <div class="col-lg-7">
        <div class="card p-4 shadow-sm border-0">
            <h4 class="h5 fw-bold text-primary mb-3"><i class="bi bi-folder2-open me-2"></i>Pièces justificatives téléversées</h4>

            <?php if (!empty($documents)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($documents as $doc): ?>
                        <div class="list-group-item p-3 mb-3 border rounded-3 bg-white shadow-sm">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                <h6 class="fw-bold text-dark m-0"><i class="bi bi-file-earmark-check me-2 text-primary"></i><?= htmlspecialchars($doc['type_document']) ?></h6>
                                <span class="badge <?= $doc['statut'] === 'valide' ? 'bg-success' : ($doc['statut'] === 'rejete' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                    <?= htmlspecialchars(ucfirst($doc['statut'])) ?>
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                <a href="../uploads/documents/<?= htmlspecialchars($doc['fichier']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>Consulter le fichier
                                </a>

                                <form method="post" action="candidat_detail.php?id=<?= $id ?>" class="d-flex gap-2">
                                    <input type="hidden" name="action_doc" value="1">
                                    <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                    <button name="doc_statut" value="valide" class="btn btn-sm btn-success fw-semibold" type="submit">
                                        <i class="bi bi-check-lg me-1"></i>Valider
                                    </button>
                                    <button name="doc_statut" value="rejete" class="btn btn-sm btn-outline-danger fw-semibold" type="submit">
                                        <i class="bi bi-x-lg me-1"></i>Rejeter
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning text-center py-4">
                    <i class="bi bi-exclamation-circle fs-3 d-block mb-2"></i>
                    Aucun document téléversé pour cette candidature.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleRejetMotif(selectElem) {
    const group = document.getElementById('motifRejetGroup');
    if (selectElem.value === 'rejete') {
        group.classList.remove('d-none');
    } else {
        group.classList.add('d-none');
    }
}
</script>

<?php require '../includes/footer.php'; ?>
