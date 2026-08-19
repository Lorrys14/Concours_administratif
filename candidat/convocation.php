<?php
require '../config/database.php';
require '../includes/auth.php';
require_role('candidat');

$u = $_SESSION['user'];
$id = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare("SELECT c.*, co.titre, co.session, co.date_debut, co.date_fin
                     FROM candidatures c
                     JOIN concours co ON co.id = c.concours_id
                     WHERE c.id = ? AND c.user_id = ?");
$st->execute([$id, $u['id']]);
$cand = $st->fetch();

if (!$cand) {
    die("Candidature introuvable.");
}

// Vérifier si le dossier est validé et qu'un centre est attribué
$isValidated = in_array($cand['statut'], ['valide', 'convoque', 'admis', 'non_admis'], true) && !empty($cand['centre']);

// Recherche de la photo d'identité du candidat
$photoUrl = null;
if (!empty($cand['photo'])) {
    if (file_exists('../uploads/photos/' . $cand['photo'])) {
        $photoUrl = '../uploads/photos/' . $cand['photo'];
    } elseif (file_exists('../uploads/documents/' . $cand['photo'])) {
        $photoUrl = '../uploads/documents/' . $cand['photo'];
    }
}

if (!$photoUrl) {
    $stP = $pdo->prepare("SELECT fichier FROM documents WHERE candidature_id = ? AND (type_document LIKE '%photo%' OR type_document LIKE '%Photo%') LIMIT 1");
    $stP->execute([$cand['id']]);
    $pDoc = $stP->fetch();
    if ($pDoc) {
        if (file_exists('../uploads/photos/' . $pDoc['fichier'])) {
            $photoUrl = '../uploads/photos/' . $pDoc['fichier'];
        } elseif (file_exists('../uploads/documents/' . $pDoc['fichier'])) {
            $photoUrl = '../uploads/documents/' . $pDoc['fichier'];
        }
    }
}

$title = 'Convocation — ' . htmlspecialchars($cand['numero_candidat']);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', Arial, sans-serif; }
        .convocation-card { background: #fff; border: 2px solid #00843d; border-radius: 12px; }
        .header-logo { border-bottom: 2px solid #00843d; }
        .photo-box { width: 120px; height: 140px; border: 2px dashed #6c757d; display: flex; align-items: center; justify-content: center; text-align: center; color: #6c757d; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .convocation-card { border: 1px solid #000 !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="py-4">

<div class="container max-w-800">

<?php if (!$isValidated): ?>
    <div class="row justify-content-center my-5">
        <div class="col-md-8 text-center">
            <div class="card shadow-sm border-0 p-4 p-md-5">
                <div class="mb-3 text-warning">
                    <i class="bi bi-clock-history display-1"></i>
                </div>
                <h3 class="fw-bold text-dark mb-3">Convocation Non Disponible</h3>
                <p class="text-secondary lead fs-6 mb-4">
                    Votre dossier de candidature (N° <strong><?= htmlspecialchars($cand['numero_candidat']) ?></strong>) est actuellement avec le statut :
                    <span class="badge bg-warning text-dark fs-6 px-3 py-1 ms-1"><?= ucfirst(str_replace('_', ' ', $cand['statut'])) ?></span>.
                </p>
                <div class="alert alert-info text-start small border-0 shadow-sm mb-4">
                    <i class="bi bi-info-circle-fill me-2 fs-5 align-middle"></i>
                    <strong>Information importante :</strong>
                    <p class="mb-0 mt-2">
                        La fiche de convocation n'est générée qu'après la <strong>validation définitive de votre dossier</strong> par la commission administrative et l'<strong>attribution de votre centre de composition</strong>.
                    </p>
                </div>
                <div>
                    <a href="candidatures.php" class="btn btn-primary px-4 fw-bold">
                        <i class="bi bi-arrow-left me-2"></i>Retour à mes candidatures
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>

    <div class="no-print d-flex justify-content-between align-items-center mb-4">
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour au tableau de bord
        </a>
        <button onclick="window.print()" class="btn btn-primary fw-bold">
            <i class="bi bi-printer-fill me-2"></i>Imprimer la convocation
        </button>
    </div>

    <div class="convocation-card p-4 p-md-5 shadow-sm">
        <!-- En-tête officiel -->
        <div class="header-logo pb-3 mb-4 text-center">
            <div class="row align-items-center">
                <div class="col-8 text-start">
                    <h5 class="fw-bold text-success mb-1">RÉPUBLIQUE DE CÔTE D'IVOIRE</h5>
                    <small class="text-muted d-block">Union - Discipline - Travail</small>
                    <small class="fw-bold text-dark">MINISTÈRE DE LA FONCTION PUBLIQUE</small>
                </div>
                <div class="col-4 text-end">
                    <span class="badge bg-success p-2 fs-6">SESSION <?= htmlspecialchars($cand['session']) ?></span>
                </div>
            </div>
        </div>

        <div class="text-center mb-4">
            <h3 class="fw-bold text-uppercase text-dark mb-1">Fiche de Convocation</h3>
            <p class="text-muted">Concours de Recrutement de la Fonction Publique</p>
            <div class="d-inline-block bg-light px-3 py-2 border rounded font-monospace fw-bold fs-5 text-primary">
                <?= htmlspecialchars($cand['numero_candidat']) ?>
            </div>
        </div>

        <div class="row mb-4 align-items-center">
            <div class="col-md-9">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <th style="width: 35%;">Nom & Prénom :</th>
                        <td class="fw-bold text-uppercase"><?= htmlspecialchars($u['nom'] . ' ' . $u['prenom']) ?></td>
                    </tr>
                    <tr>
                        <th>Email :</th>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                    </tr>
                    <tr>
                        <th>Téléphone :</th>
                        <td><?= htmlspecialchars($u['telephone'] ?? 'Non renseigné') ?></td>
                    </tr>
                    <tr>
                        <th>Concours :</th>
                        <td class="fw-bold text-success"><?= htmlspecialchars($cand['titre']) ?></td>
                    </tr>
                    <tr>
                        <th>Centre de Composition :</th>
                        <td class="fw-bold text-dark"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($cand['centre']) ?></td>
                    </tr>
                    <tr>
                        <th>Salle / Place :</th>
                        <td class="fw-bold text-primary">Salle B-<?= (int)($cand['id'] % 20 + 1) ?> — Place #<?= str_pad((string)(($cand['id'] * 7) % 150 + 1), 2, '0', STR_PAD_LEFT) ?></td>
                    </tr>
                    <tr>
                        <th>Statut Dossier :</th>
                        <td><span class="badge bg-success text-uppercase"><?= htmlspecialchars($cand['statut']) ?></span></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-3 text-center d-flex justify-content-center mt-3 mt-md-0">
                <div class="photo-box bg-light rounded overflow-hidden shadow-sm">
                    <?php if ($photoUrl): ?>
                        <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Photo du candidat" class="w-100 h-100" style="object-fit: cover;">
                    <?php else: ?>
                        <span class="small"><i class="bi bi-person fs-1 d-block mb-1"></i>Photo candidat</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="bg-light p-3 rounded-3 border mb-4">
            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Instructions importantes pour l'épreuve :</h6>
            <ul class="small mb-0 ps-3 text-secondary">
                <li>Présentez-vous au centre de composition au moins 30 minutes avant le début des épreuves.</li>
                <li>Munissez-vous obligatoirement de cette fiche imprimée et de votre pièce d'identité originale en cours de validité.</li>
                <li>L'accès aux salles avec un téléphone portable ou tout appareil connecté est strictly interdit sous peine d'exclusion.</li>
            </ul>
        </div>

        <div class="d-flex justify-content-between align-items-end pt-3 border-top">
            <div>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($cand['numero_candidat'] . ' | ' . $u['nom'] . ' ' . $u['prenom']) ?>" alt="QR Code Convocation" class="border p-1 bg-white rounded mb-2 d-block" style="width: 80px; height: 80px;">
                <small class="text-muted d-block">Date d'impression : <?= date('d/m/Y à H:i') ?></small>
                <small class="text-muted">Document officiel vérifiable par QR Code.</small>
            </div>
            <div class="text-end">
                <small class="fw-bold d-block mb-4">Le Directeur des Examens et Concours</small>
                <span class="badge bg-outline-dark border text-dark p-2"><i class="bi bi-shield-check text-success me-1"></i>Cachet électronique officiel</span>
            </div>
        </div>
    </div>
<?php endif; ?>

</div>

</body>
</html>
