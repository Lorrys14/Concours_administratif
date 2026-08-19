<?php
require '../config/database.php';
require '../includes/auth.php';
require_role('admin');

$search = trim($_GET['search'] ?? '');

$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM candidatures WHERE user_id = u.id) as nb_candidatures 
        FROM users u 
        WHERE u.role = 'candidat'";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ? OR u.telephone LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
}

$sql .= " ORDER BY u.created_at DESC";

$st = $pdo->prepare($sql);
$st->execute($params);
$candidats = $st->fetchAll();

$title = 'Liste des Candidats — Admin';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-dark m-0"><i class="bi bi-people-fill text-primary me-2"></i>Gestion des Candidats</h1>
        <p class="text-muted m-0">Consultez la liste des comptes candidats créés et leurs informations.</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Retour au tableau de bord
    </a>
</div>

<!-- Filtre & Recherche -->
<div class="card p-3 shadow-sm border-0 mb-4 bg-light">
    <form method="get" action="candidats.php" class="row g-2 align-items-center">
        <div class="col-md-9">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Rechercher un candidat par nom, prénom, email, téléphone..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary w-100 fw-semibold" type="submit">Rechercher</button>
            <?php if (!empty($search)): ?>
                <a href="candidats.php" class="btn btn-outline-secondary">Effacer</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Nom & Prénom</th>
                        <th>Date & Lieu de Naissance</th>
                        <th>Contact</th>
                        <th>Niveau / Diplôme</th>
                        <th>Candidatures</th>
                        <th>Inscrit le</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidats as $c): ?>
                        <tr>
                            <td class="ps-3">
                                <strong class="text-dark d-block"><?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?></strong>
                                <small class="text-muted"><i class="bi bi-gender-ambiguous me-1"></i><?= $c['sexe'] === 'F' ? 'Féminin' : 'Masculin' ?> | <?= htmlspecialchars($c['nationalite'] ?? 'Ivoirienne') ?></small>
                            </td>
                            <td>
                                <?= !empty($c['date_naissance']) ? date('d/m/Y', strtotime($c['date_naissance'])) : 'N/A' ?>
                                <small class="text-muted d-block"><?= htmlspecialchars($c['lieu_naissance'] ?? '') ?></small>
                            </td>
                            <td>
                                <i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($c['email']) ?><br>
                                <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($c['telephone'] ?? 'Non renseigné') ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($c['niveau_etude'] ?? 'Non précisé') ?></span>
                                <small class="text-muted d-block"><?= htmlspecialchars($c['diplome_obtenu'] ?? '') ?></small>
                            </td>
                            <td>
                                <span class="badge bg-primary px-3 py-1 fs-6"><?= (int)$c['nb_candidatures'] ?></span>
                            </td>
                            <td class="small text-muted">
                                <?= date('d/m/Y', strtotime($c['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($candidats)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Aucun candidat trouvé.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>