<?php
require 'config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['user'])) {
    $redirect = $_SESSION['user']['role'] === 'admin' ? 'admin/dashboard.php' : 'candidat/dashboard.php';
    header('Location: ' . BASE_URL . $redirect);
    exit;
}

$error = '';
$nom = '';
$prenom = '';
$date_naissance = '';
$lieu_naissance = '';
$sexe = 'M';
$nationalite = 'Ivoirienne';
$email = '';
$telephone = '';
$adresse = '';
$ville = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom            = trim($_POST['nom'] ?? '');
    $prenom         = trim($_POST['prenom'] ?? '');
    $date_naissance = trim($_POST['date_naissance'] ?? '');
    $lieu_naissance = trim($_POST['lieu_naissance'] ?? '');
    $sexe           = trim($_POST['sexe'] ?? 'M');
    $nationalite    = trim($_POST['nationalite'] ?? 'Ivoirienne');
    $email          = trim($_POST['email'] ?? '');
    $telephone      = trim($_POST['telephone'] ?? '');
    $adresse        = trim($_POST['adresse'] ?? '');
    $ville          = trim($_POST['ville'] ?? '');
    $password       = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($nom) || empty($prenom)) {
        $error = 'Le nom et le prénom sont obligatoires.';
    } elseif (empty($date_naissance) || empty($lieu_naissance)) {
        $error = 'La date et le lieu de naissance sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Veuillez saisir une adresse email valide.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($password !== $confirm_password) {
        $error = 'La confirmation du mot de passe ne correspond pas.';
    } else {
        $st = $pdo->prepare("INSERT INTO users (nom, prenom, date_naissance, lieu_naissance, sexe, nationalite, email, telephone, adresse, ville, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'candidat')");
        try {
            $st->execute([$nom, $prenom, $date_naissance, $lieu_naissance, $sexe, $nationalite, $email, $telephone, $adresse, $ville, password_hash($password, PASSWORD_DEFAULT)]);
            header('Location: ' . BASE_URL . 'login.php?ok=1');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Cette adresse email est déjà enregistrée.';
            } else {
                $error = 'Une erreur s\'est produite lors de l\'inscription : ' . $e->getMessage();
            }
        }
    }
}

$title = 'Inscription — Concours Administratifs';
require 'includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-lg-8 col-md-10">
        <div class="card shadow-sm border-0 p-4 p-md-5">
            <h2 class="h4 text-center mb-4 text-primary fw-bold">
                <i class="bi bi-person-plus-fill me-2"></i>Créer un compte candidat
            </h2>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            <?php endif; ?>

            <form method="post" action="register.php" class="needs-validation" novalidate>
                <h5 class="fw-bold text-secondary mb-3 border-bottom pb-2"><i class="bi bi-person me-2"></i>Informations Personnelles</h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Nom <span class="text-danger">*</span></label>
                        <input name="nom" class="form-control" value="<?= htmlspecialchars($nom) ?>" placeholder="Ex: KOUASSI" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Prénom <span class="text-danger">*</span></label>
                        <input name="prenom" class="form-control" value="<?= htmlspecialchars($prenom) ?>" placeholder="Ex: Jean" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Date de naissance <span class="text-danger">*</span></label>
                        <input type="date" name="date_naissance" class="form-control" value="<?= htmlspecialchars($date_naissance) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Lieu de naissance <span class="text-danger">*</span></label>
                        <input name="lieu_naissance" class="form-control" value="<?= htmlspecialchars($lieu_naissance) ?>" placeholder="Ex: Abidjan" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Sexe <span class="text-danger">*</span></label>
                        <select name="sexe" class="form-select" required>
                            <option value="M" <?= $sexe === 'M' ? 'selected' : '' ?>>Masculin</option>
                            <option value="F" <?= $sexe === 'F' ? 'selected' : '' ?>>Féminin</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Nationalité <span class="text-danger">*</span></label>
                        <input name="nationalite" class="form-control" value="<?= htmlspecialchars($nationalite) ?>" placeholder="Ex: Ivoirienne" required>
                    </div>
                </div>

                <h5 class="fw-bold text-secondary mb-3 mt-3 border-bottom pb-2"><i class="bi bi-telephone me-2"></i>Coordonnées</h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Adresse Email <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" placeholder="exemple@domaine.com" required>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Téléphone <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="tel" name="telephone" class="form-control" value="<?= htmlspecialchars($telephone) ?>" placeholder="0700000000" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-bold">Adresse</label>
                        <input name="adresse" class="form-control" value="<?= htmlspecialchars($adresse) ?>" placeholder="Ex: Cocody Angré 8ème Tranche">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Ville</label>
                        <input name="ville" class="form-control" value="<?= htmlspecialchars($ville) ?>" placeholder="Ex: Abidjan">
                    </div>
                </div>

                <h5 class="fw-bold text-secondary mb-3 mt-3 border-bottom pb-2"><i class="bi bi-shield-lock me-2"></i>Sécurité</h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Mot de passe <span class="text-danger">*</span> <small class="text-muted">(min. 6 caractères)</small></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" minlength="6" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Confirmation mot de passe <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="confirm_password" minlength="6" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                </div>

                <button class="btn btn-success w-100 py-2 mt-3 fw-bold fs-5" type="submit">
                    <i class="bi bi-check-circle me-2"></i>Créer mon compte
                </button>
            </form>

            <p class="text-center mt-4 mb-0">
                <small>Vous avez déjà un compte ? <a href="login.php" class="text-decoration-none fw-bold text-primary">Se connecter</a></small>
            </p>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
