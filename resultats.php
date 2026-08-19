<?php
require 'config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$title = 'Publication des Résultats — Concours Administratifs';

// Récupérer les concours avec au moins un résultat publié
$concoursPublies = $pdo->query("SELECT DISTINCT co.* 
                                FROM concours co 
                                JOIN candidatures c ON c.concours_id = co.id 
                                JOIN resultats r ON r.candidature_id = c.id 
                                WHERE r.publie = 1 
                                ORDER BY co.date_debut DESC")->fetchAll();

$selectedConcoursId = (int)($_GET['concours_id'] ?? ($concoursPublies[0]['id'] ?? 0));
$searchNum = trim($_GET['search_num'] ?? '');

$searchResult = null;
if (!empty($searchNum)) {
    $stSearch = $pdo->prepare("SELECT c.*, u.nom, u.prenom, co.titre as concours_titre, co.session, r.moyenne, r.rang, r.decision, r.publie 
                               FROM candidatures c 
                               JOIN users u ON u.id = c.user_id 
                               JOIN concours co ON co.id = c.concours_id 
                               JOIN resultats r ON r.candidature_id = c.id 
                               WHERE c.numero_candidat = ? AND r.publie = 1");
    $stSearch->execute([$searchNum]);
    $searchResult = $stSearch->fetch();
}

$admisList = [];
$attenteList = [];
$currentConcours = null;

if ($selectedConcoursId > 0) {
    $stCo = $pdo->prepare("SELECT * FROM concours WHERE id = ?");
    $stCo->execute([$selectedConcoursId]);
    $currentConcours = $stCo->fetch();

    $stAdmis = $pdo->prepare("SELECT c.*, u.nom, u.prenom, r.moyenne, r.rang, r.decision 
                              FROM candidatures c 
                              JOIN users u ON u.id = c.user_id 
                              JOIN resultats r ON r.candidature_id = c.id 
                              WHERE c.concours_id = ? AND r.publie = 1 AND r.decision = 'admis' 
                              ORDER BY r.rang ASC");
    $stAdmis->execute([$selectedConcoursId]);
    $admisList = $stAdmis->fetchAll();

    $stAttente = $pdo->prepare("SELECT c.*, u.nom, u.prenom, r.moyenne, r.rang, r.decision 
                                FROM candidatures c 
                                JOIN users u ON u.id = c.user_id 
                                JOIN resultats r ON r.candidature_id = c.id 
                                WHERE c.concours_id = ? AND r.publie = 1 AND r.decision = 'liste_attente' 
                                ORDER BY r.rang ASC");
    $stAttente->execute([$selectedConcoursId]);
    $attenteList = $stAttente->fetchAll();
}

require 'includes/header.php';
?>

<div class="text-center mb-5 py-4 bg-gradient bg-primary text-white rounded-4 shadow-sm">
    <h1 class="display-5 fw-bold mb-2"><i class="bi bi-trophy me-2"></i>Résultats Officiels des Concours</h1>
    <p class="lead mb-0">Consultation en ligne des listes des candidats admis et recherche individuelle par numéro.</p>
</div>

<!-- Formulaire de Recherche par Numéro de Candidat -->
<div class="card p-4 shadow-sm border-0 mb-5 max-w-800 mx-auto">
    <h4 class="h5 fw-bold text-dark mb-3"><i class="bi bi-search me-2 text-primary"></i>Recherche individuelle de résultat</h4>
    <form method="get" action="resultats.php" class="row g-2">
        <div class="col-md-9">
            <div class="input-group input-group-lg">
                <span class="input-group-text"><i class="bi bi-person-vcard"></i></span>
                <input type="text" name="search_num" class="form-control" placeholder="Saisir votre N° candidat (ex: CONC-2026-123456)" value="<?= htmlspecialchars($searchNum) ?>" required>
            </div>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary btn-lg w-100 fw-bold" type="submit">Rechercher</button>
        </div>
    </form>

    <?php if (!empty($searchNum)): ?>
        <div class="mt-4 border-top pt-3">
            <?php if ($searchResult): ?>
                <div class="alert <?= $searchResult['decision'] === 'admis' ? 'alert-success' : ($searchResult['decision'] === 'liste_attente' ? 'alert-warning' : 'alert-secondary') ?> border-0 shadow-sm p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($searchResult['nom'] . ' ' . $searchResult['prenom']) ?></h5>
                            <span class="text-muted small">N° Candidat : <strong><?= htmlspecialchars($searchResult['numero_candidat']) ?></strong> | Concours : <strong><?= htmlspecialchars($searchResult['concours_titre']) ?></strong></span>
                        </div>
                        <div class="text-end">
                            <span class="badge <?= $searchResult['decision'] === 'admis' ? 'bg-success' : ($searchResult['decision'] === 'liste_attente' ? 'bg-warning text-dark' : 'bg-danger') ?> fs-5 p-2">
                                <?= strtoupper(str_replace('_', ' ', $searchResult['decision'])) ?>
                            </span>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center mt-2">
                        <div class="col-4">
                            <small class="text-muted d-block">Moyenne</small>
                            <strong class="fs-5 text-primary"><?= number_format($searchResult['moyenne'], 2, ',', ' ') ?> / 20</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Rang</small>
                            <strong class="fs-5 text-dark">#<?= (int)$searchResult['rang'] ?></strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Décision Finale</small>
                            <strong class="fs-5 text-uppercase"><?= str_replace('_', ' ', $searchResult['decision']) ?></strong>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger border-0 shadow-sm text-center py-3">
                    <i class="bi bi-exclamation-octagon fs-4 d-block mb-1"></i>
                    Aucun résultat publié correspondant au numéro <strong><?= htmlspecialchars($searchNum) ?></strong>.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Liste des admis par concours -->
<?php if (!empty($concoursPublies)): ?>
    <div class="mb-4">
        <ul class="nav nav-pills justify-content-center gap-2 mb-4">
            <?php foreach ($concoursPublies as $coItem): ?>
                <li class="nav-item">
                    <a href="resultats.php?concours_id=<?= $coItem['id'] ?>" class="nav-link fw-bold px-4 py-2 <?= $selectedConcoursId === (int)$coItem['id'] ? 'active bg-primary' : 'bg-white text-dark shadow-sm' ?>">
                        <?= htmlspecialchars($coItem['titre']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php if ($currentConcours): ?>
        <div class="card shadow-sm border-0 p-4 mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 border-bottom pb-3">
                <div>
                    <h3 class="h4 fw-bold text-dark m-0"><?= htmlspecialchars($currentConcours['titre']) ?></h3>
                    <span class="text-muted small">Session <?= htmlspecialchars($currentConcours['session']) ?> — <?= (int)$currentConcours['places'] ?> places ouvertes</span>
                </div>
                <span class="badge bg-success p-2"><i class="bi bi-check-circle me-1"></i>Résultats Officiels Publiés</span>
            </div>

            <h4 class="h5 fw-bold text-success mb-3"><i class="bi bi-check-circle-fill me-2"></i>Liste des candidats admis par ordre de mérite</h4>

            <div class="table-responsive mb-5">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 10%;">Rang</th>
                            <th>N° Candidat</th>
                            <th>Nom & Prénom</th>
                            <th class="text-end pe-3">Moyenne</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admisList as $ad): ?>
                            <tr>
                                <td class="ps-3"><span class="badge bg-success text-white fs-6">#<?= (int)$ad['rang'] ?></span></td>
                                <td><span class="badge bg-light text-dark font-monospace border"><?= htmlspecialchars($ad['numero_candidat']) ?></span></td>
                                <td class="fw-bold text-uppercase"><?= htmlspecialchars($ad['nom'] . ' ' . $ad['prenom']) ?></td>
                                <td class="text-end pe-3 fw-bold text-primary"><?= number_format($ad['moyenne'], 2, ',', ' ') ?> / 20</td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($admisList)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Aucun candidat admis pour ce concours.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($attenteList)): ?>
                <h4 class="h5 fw-bold text-warning mb-3"><i class="bi bi-hourglass-split me-2"></i>Liste d'attente</h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 10%;">Rang</th>
                                <th>N° Candidat</th>
                                <th>Nom & Prénom</th>
                                <th class="text-end pe-3">Moyenne</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attenteList as $at): ?>
                                <tr>
                                    <td class="ps-3"><span class="badge bg-warning text-dark fs-6">#<?= (int)$at['rang'] ?></span></td>
                                    <td><span class="badge bg-light text-dark font-monospace border"><?= htmlspecialchars($at['numero_candidat']) ?></span></td>
                                    <td class="fw-bold text-uppercase"><?= htmlspecialchars($at['nom'] . ' ' . $at['prenom']) ?></td>
                                    <td class="text-end pe-3 fw-bold text-secondary"><?= number_format($at['moyenne'], 2, ',', ' ') ?> / 20</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="alert alert-info text-center py-5 shadow-sm">
        <i class="bi bi-clock-history fs-1 text-primary d-block mb-3"></i>
        <h4 class="fw-bold text-dark">Aucun résultat publié pour le moment</h4>
        <p class="text-muted mb-0">Les délibérations sont en cours. Veuillez consulter régulièrement cette page pour prendre connaissance des publications.</p>
    </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>