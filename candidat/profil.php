<?php
require '../config/database.php';
require '../includes/auth.php';
require_role('candidat');

$userId = $_SESSION['user']['id'];
$msgSuccess = '';
$msgError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom            = trim($_POST['nom'] ?? '');
    $prenom         = trim($_POST['prenom'] ?? '');
    $date_naissance = trim($_POST['date_naissance'] ?? '');
    $lieu_naissance = trim($_POST['lieu_naissance'] ?? '');
    $sexe           = trim($_POST['sexe'] ?? 'M');
    $nationalite    = trim($_POST['nationalite'] ?? 'Ivoirienne');
    $telephone      = trim($_POST['telephone'] ?? '');
    $adresse        = trim($_POST['adresse'] ?? '');
    $ville          = trim($_POST['ville'] ?? '');
    $niveau_etude   = trim($_POST['niveau_etude'] ?? '');
    $diplome_obtenu = trim($_POST['diplome_obtenu'] ?? '');
    $etablissement  = trim($_POST['etablissement'] ?? '');
    $annee_obtention= !empty($_POST['annee_obtention']) ? (int)$_POST['annee_obtention'] : null;

    if (empty($nom) || empty($prenom) || empty($date_naissance)) {
        $msgError = "Le nom, prénom et date de naissance sont obligatoires.";
    } else {
        $st = $pdo->prepare("UPDATE users SET nom=?, prenom=?, date_naissance=?, lieu_naissance=?, sexe=?, nationalite=?, telephone=?, adresse=?, ville=?, niveau_etude=?, diplome_obtenu=?, etablissement=?, annee_obtention=? WHERE id=?");
        $st->execute([$nom, $prenom, $date_naissance, $lieu_naissance, $sexe, $nationalite, $telephone, $adresse, $ville, $niveau_etude, $diplome_obtenu, $etablissement, $annee_obtention, $userId]);
        
        // Refresh session
        $stU = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stU->execute([$userId]);
        $u = $stU->fetch();
        unset($u['password']);
        $_SESSION['user'] = $u;

        $msgSuccess = "Votre profil a été mis à jour avec succès.";
    }
}

$u = $_SESSION['user'];
$title = "Mon Profil — Espace Candidat";
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 fw-bold text-dark m-0"><i class="bi bi-person-badge-fill text-primary me-2"></i>Mon Profil Candidat</h1>
        <p class="text-muted m-0">Gérez vos informations personnelles, coordonnées et diplômes.</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Retour au tableau de bord
    </a>
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

<div class="card p-4 p-md-5 shadow-sm border-0 mb-4">
    <form method="post" action="profil.php" class="needs-validation" novalidate>
        <h4 class="h5 fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-person me-2"></i>1. Informations personnelles</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Nom <span class="text-danger">*</span></label>
                <input name="nom" class="form-control" value="<?= htmlspecialchars($u['nom'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Prénom <span class="text-danger">*</span></label>
                <input name="prenom" class="form-control" value="<?= htmlspecialchars($u['prenom'] ?? '') ?>" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Date de naissance <span class="text-danger">*</span></label>
                <input type="date" name="date_naissance" class="form-control" value="<?= htmlspecialchars($u['date_naissance'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Lieu de naissance</label>
                <input name="lieu_naissance" class="form-control" value="<?= htmlspecialchars($u['lieu_naissance'] ?? '') ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Sexe</label>
                <select name="sexe" class="form-select">
                    <option value="M" <?= ($u['sexe'] ?? 'M') === 'M' ? 'selected' : '' ?>>Masculin</option>
                    <option value="F" <?= ($u['sexe'] ?? '') === 'F' ? 'selected' : '' ?>>Féminin</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Nationalité</label>
                <input name="nationalite" class="form-control" value="<?= htmlspecialchars($u['nationalite'] ?? 'Ivoirienne') ?>">
            </div>
        </div>

        <h4 class="h5 fw-bold text-primary mb-3 mt-4 border-bottom pb-2"><i class="bi bi-telephone me-2"></i>2. Coordonnées</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Adresse Email (non modifiable)</label>
                <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($u['email'] ?? '') ?>" readonly>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Téléphone</label>
                <input type="tel" name="telephone" class="form-control" value="<?= htmlspecialchars($u['telephone'] ?? '') ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label fw-bold">Adresse résidence</label>
                <input name="adresse" class="form-control" value="<?= htmlspecialchars($u['adresse'] ?? '') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Ville</label>
                <input name="ville" class="form-control" value="<?= htmlspecialchars($u['ville'] ?? '') ?>">
            </div>
        </div>

        <h4 class="h5 fw-bold text-primary mb-3 mt-4 border-bottom pb-2"><i class="bi bi-mortarboard me-2"></i>3. Formation & Diplômes</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Niveau d'étude le plus élevé</label>
                <select name="niveau_etude" class="form-select">
                    <option value="">-- Choisir --</option>
                    <option value="BAC" <?= ($u['niveau_etude'] ?? '') === 'BAC' ? 'selected' : '' ?>>BAC / Baccalauréat</option>
                    <option value="BTS" <?= ($u['niveau_etude'] ?? '') === 'BTS' ? 'selected' : '' ?>>BTS / DUT / DEUG</option>
                    <option value="Licence" <?= ($u['niveau_etude'] ?? '') === 'Licence' ? 'selected' : '' ?>>Licence / Bachelor</option>
                    <option value="Master" <?= ($u['niveau_etude'] ?? '') === 'Master' ? 'selected' : '' ?>>Master / DEA / DESS</option>
                    <option value="Doctorat" <?= ($u['niveau_etude'] ?? '') === 'Doctorat' ? 'selected' : '' ?>>Doctorat / Ph.D</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Intitulé du diplôme</label>
                <input name="diplome_obtenu" class="form-control" value="<?= htmlspecialchars($u['diplome_obtenu'] ?? '') ?>" placeholder="Ex: Licence en Informatique">
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label fw-bold">Établissement / Université</label>
                <input name="etablissement" class="form-control" value="<?= htmlspecialchars($u['etablissement'] ?? '') ?>" placeholder="Ex: Université Felix Houphouët-Boigny">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Année d'obtention</label>
                <input type="number" name="annee_obtention" min="1970" max="2026" class="form-control" value="<?= htmlspecialchars($u['annee_obtention'] ?? '') ?>" placeholder="Ex: 2022">
            </div>
        </div>

        <button class="btn btn-primary btn-lg fw-bold w-100 mt-3" type="submit">
            <i class="bi bi-save me-2"></i>Enregistrer mon profil
        </button>
    </form>
</div>

<?php require '../includes/footer.php'; ?>
