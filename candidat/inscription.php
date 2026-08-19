<?php
require '../config/database.php';
require '../includes/auth.php';
require_role('candidat');

$u = $_SESSION['user'];
$id = (int)($_GET['id'] ?? 0);

$s = $pdo->prepare("SELECT * FROM concours WHERE id = ? AND statut = 'ouvert'");
$s->execute([$id]);
$c = $s->fetch();

if (!$c) {
    die('Concours indisponible ou fermé aux inscriptions.');
}

// Vérifier si déjà inscrit
$check = $pdo->prepare("SELECT id FROM candidatures WHERE user_id = ? AND concours_id = ?");
$check->execute([$u['id'], $id]);
if ($check->fetch()) {
    header('Location: dashboard.php');
    exit;
}

// Récupérer la liste des centres d'examen
$centresList = $pdo->query("SELECT * FROM centres ORDER BY ville ASC, nom ASC")->fetchAll();

// Calcul de l'âge du candidat pour la vérification automatique d'éligibilité
$age = null;
$eligibilityAge = true;
if (!empty($u['date_naissance'])) {
    $dob = new DateTime($u['date_naissance']);
    $now = new DateTime();
    $age = $now->diff($dob)->y;

    if (($c['age_min'] && $age < (int)$c['age_min']) || ($c['age_max'] && $age > (int)$c['age_max'])) {
        $eligibilityAge = false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fichiers obligatoires
    $docTypes = [
        'cni' => 'Pièce d\'identité (CNI / Passeport)',
        'diplome' => 'Diplôme requis',
        'extrait' => 'Extrait de naissance',
        'quittance' => 'Quittance de paiement des frais'
    ];

    $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
    $allowedPhotoExts = ['jpg', 'jpeg', 'png'];

    // Vérification de la photo d'identité
    if (empty($_FILES['photo']['name']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $error = "La photo d'identité est obligatoire.";
    } else {
        $photoExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($photoExt, $allowedPhotoExts)) {
            $error = "Format de la photo d'identité invalide. Seuls JPG, JPEG et PNG sont acceptés.";
        } elseif ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
            $error = "La photo d'identité dépasse la taille maximale de 5 Mo.";
        }
    }

    if (empty($error)) {
        foreach ($docTypes as $inputName => $label) {
            if (empty($_FILES[$inputName]['name']) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
                $error = "Le document \"$label\" est obligatoire.";
                break;
            }

            $ext = strtolower(pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts)) {
                $error = "Format invalide pour \"$label\". Seuls PDF, JPG, JPEG et PNG sont acceptés.";
                break;
            }

            if ($_FILES[$inputName]['size'] > 5 * 1024 * 1024) {
                $error = "Le fichier \"$label\" dépasse la taille maximale de 5 Mo.";
                break;
            }
        }
    }

    if (empty($error)) {
        try {
            $pdo->beginTransaction();

            // Vérification de la disponibilité des places
            $stLock = $pdo->prepare("SELECT places FROM concours WHERE id = ? FOR UPDATE");
            $stLock->execute([$id]);
            $currentPlaces = (int)$stLock->fetchColumn();

            if ($currentPlaces <= 0) {
                throw new Exception("Désolé, il n'y a plus de places disponibles pour ce concours.");
            }

            // Génération d'un numéro de candidature unique
            $numero = 'CONC-' . $c['session'] . '-' . str_pad((string)random_int(1, 999999), 6, '0', STR_PAD_LEFT);

            // Insertion candidature (centre est initialement NULL, il sera attribué lors de la validation par l'administration)
            $st = $pdo->prepare("INSERT INTO candidatures (user_id, concours_id, numero_candidat, statut, centre, date_soumission) VALUES (?, ?, ?, 'soumis', NULL, NOW())");
            $st->execute([$u['id'], $id, $numero]);
            $candidatureId = $pdo->lastInsertId();

            // Décrémenter le nombre de places restantes du concours
            $stDecPlaces = $pdo->prepare("UPDATE concours SET places = places - 1 WHERE id = ? AND places > 0");
            $stDecPlaces->execute([$id]);
            if ($stDecPlaces->rowCount() === 0) {
                throw new Exception("Désolé, le nombre de places disponibles a été atteint pendant la soumission.");
            }

            // Enregistrement de la photo d'identité
            $photosDir = '../uploads/photos/';
            if (!is_dir($photosDir)) {
                mkdir($photosDir, 0777, true);
            }
            $photoExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $photoFileName = 'photo_' . $candidatureId . '_' . time() . '.' . $photoExt;
            $photoTargetPath = $photosDir . $photoFileName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $photoTargetPath)) {
                // Mettre à jour la colonne photo dans la table candidatures
                $stPhoto = $pdo->prepare("UPDATE candidatures SET photo = ? WHERE id = ?");
                $stPhoto->execute([$photoFileName, $candidatureId]);

                // Enregistrer également la photo dans la table documents pour suivi
                $stDocP = $pdo->prepare("INSERT INTO documents (candidature_id, type_document, fichier, statut) VALUES (?, ?, ?, 'en_attente')");
                $stDocP->execute([$candidatureId, "Photo d'identité", $photoFileName]);
            } else {
                throw new Exception("Erreur lors du transfert de la photo d'identité.");
            }

            // Téléversement et enregistrement des documents justificatifs
            $uploadDir = '../uploads/documents/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            foreach ($docTypes as $inputName => $label) {
                $ext = strtolower(pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION));
                $newFileName = 'doc_' . $candidatureId . '_' . $inputName . '_' . time() . '.' . $ext;
                $targetFilePath = $uploadDir . $newFileName;

                if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $targetFilePath)) {
                    $stDoc = $pdo->prepare("INSERT INTO documents (candidature_id, type_document, fichier, statut) VALUES (?, ?, ?, 'en_attente')");
                    $stDoc->execute([$candidatureId, $label, $newFileName]);
                } else {
                    throw new Exception("Erreur lors du transfert du fichier $label.");
                }
            }

            // Notification
            $n = $pdo->prepare("INSERT INTO notifications (user_id, titre, message) VALUES (?, ?, ?)");
            $n->execute([
                $u['id'],
                'Notification',
                "Votre candidature est enregistrée."
            ]);

            $pdo->commit();

            header('Location: dashboard.php?applied=1');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Une erreur s'est produite lors de la soumission : " . $e->getMessage();
        }
    }
}

$title = 'Inscription — ' . htmlspecialchars($c['titre']);
require '../includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 p-4 p-md-5">
            <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                <h2 class="h3 fw-bold text-primary m-0">
                    <i class="bi bi-file-earmark-text me-2"></i>Dossier de Candidature
                </h2>
                <span class="badge bg-success px-3 py-2 fs-6">Session <?= htmlspecialchars($c['session']) ?></span>
            </div>

            <h3 class="h4 text-dark mb-3"><?= htmlspecialchars($c['titre']) ?></h3>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            <?php endif; ?>

            <div class="alert alert-info border-0 shadow-sm mb-4">
                <div class="d-flex gap-2">
                    <i class="bi bi-info-circle-fill fs-5"></i>
                    <div>
                        <strong>Vérification automatique d'éligibilité :</strong>
                        <ul class="mb-0 ps-3 mt-1 small">
                            <li><strong>Places disponibles :</strong> <span class="badge <?= (int)$c['places'] > 0 ? 'bg-success' : 'bg-danger' ?>"><?= (int)$c['places'] ?> place(s) restante(s)</span></li>
                            <li><strong>Limite d'âge :</strong> De <?= (int)$c['age_min'] ?> à <?= (int)$c['age_max'] ?> ans.
                                <?php if ($age !== null): ?>
                                    (Votre âge calculé : <strong><?= $age ?> ans</strong> —
                                    <?= $eligibilityAge ? '<span class="badge bg-success">Conforme</span>' : '<span class="badge bg-danger">Non éligible</span>' ?>)
                                <?php endif; ?>
                            </li>
                            <li><strong>Diplôme requis :</strong> <?= htmlspecialchars($c['diplome_requis'] ?? 'Tous diplômes') ?></li>
                            <li><strong>Frais de dossier :</strong> <?= number_format($c['frais'], 0, ',', ' ') ?> FCFA</li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php if ((int)$c['places'] <= 0): ?>
                <div class="alert alert-danger p-3 shadow-sm border-0 mb-4">
                    <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
                    <strong>Concours Complet :</strong> Il n'y a plus de places disponibles pour ce concours. Vous ne pouvez pas soumettre de candidature.
                </div>
            <?php elseif (!$eligibilityAge && $age !== null): ?>
                <div class="alert alert-danger p-3 shadow-sm border-0 mb-4">
                    <i class="bi bi-x-circle-fill me-2 fs-5"></i>
                    <strong>Attention :</strong> Vous ne remplissez pas la condition d'âge requise pour ce concours (<?= $age ?> ans vs <?= (int)$c['age_min'] ?> - <?= (int)$c['age_max'] ?> ans). Vous ne pouvez pas soumettre de dossier.
                </div>
            <?php else: ?>

            <form method="post" action="inscription.php?id=<?= $c['id'] ?>" enctype="multipart/form-data" class="needs-validation" novalidate>

                <!-- Information Attribution Centre -->
                <div class="alert alert-light border border-info shadow-sm mb-4">
                    <div class="d-flex gap-2">
                        <i class="bi bi-info-circle-fill text-info fs-5"></i>
                        <div>
                            <strong class="text-dark">Centre de composition :</strong>
                            <p class="mb-0 small text-secondary">
                                Conformément aux règles d'inscription, vous ne choisissez pas votre centre de composition lors de la soumission.
                                Votre centre de composition vous sera attribué par la commission administrative au cours de la validation de votre dossier.
                                Votre convocation sera accessible dans votre espace une fois votre dossier validé.
                            </p>
                        </div>
                    </div>
                </div>

                <h4 class="h5 fw-bold text-secondary mb-3 border-bottom pb-2"><i class="bi bi-camera me-2"></i>1. Photo d'Identité Officielle</h4>

                <div class="mb-4">
                    <label class="form-label fw-bold">Photo d'identité récents <span class="text-danger">*</span></label>
                    <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png" required>
                    <div class="form-text">Photo d'identité au format passeport (JPG, JPEG, PNG, max 5 Mo). Cette photo figurera sur votre convocation officielle.</div>
                </div>

                <h4 class="h5 fw-bold text-secondary mb-3 border-bottom pb-2"><i class="bi bi-folder-check me-2"></i>2. Téléversement des Pièces Justificatives</h4>

                <div class="mb-3">
                    <label class="form-label fw-bold">1. Pièce d'identité originale (CNI / Passeport) <span class="text-danger">*</span></label>
                    <input type="file" name="cni" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                    <div class="form-text">Fichier PDF ou image clair et lisible.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">2. Diplôme exigé (<?= htmlspecialchars($c['diplome_requis'] ?? 'Diplôme requis') ?>) <span class="text-danger">*</span></label>
                    <input type="file" name="diplome" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                    <div class="form-text">Attestation de réussite ou diplôme officiel.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">3. Extrait d'acte de naissance <span class="text-danger">*</span></label>
                    <input type="file" name="extrait" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                    <div class="form-text">Certificat de naissance de moins de 3 mois.</div>
                </div>

                <h4 class="h5 fw-bold text-secondary mb-3 border-bottom pb-2"><i class="bi bi-cash-coin me-2"></i>3. Frais de candidature & Quittance de paiement</h4>

                <div class="bg-light p-3 rounded border mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <span class="fw-semibold text-dark">Montant des frais de dossier :</span>
                        <strong class="fs-5 text-success"><?= number_format($c['frais'], 0, ',', ' ') ?> FCFA</strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Quittance de paiement officielle des frais <span class="text-danger">*</span></label>
                        <input type="file" name="quittance" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                        <div class="form-text">Téléversez le reçu ou la quittance officielle attestant le paiement complet des <?= number_format($c['frais'], 0, ',', ' ') ?> FCFA (PDF, JPG, PNG, max 5 Mo).</div>
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="certifyCheck" required>
                        <label class="form-check-label fw-semibold" for="certifyCheck">
                            Je certifie sur l'honneur avoir réglé les frais de dossier et certifie l'exactitude des pièces fournies.
                        </label>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="../concours.php?id=<?= $c['id'] ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Annuler
                    </a>
                    <button class="btn btn-success px-4 py-2 fw-bold fs-5" type="submit">
                        <i class="bi bi-cloud-arrow-up-fill me-2"></i>Soumettre définitivement mon dossier
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>

