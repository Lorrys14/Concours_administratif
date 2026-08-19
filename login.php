<?php
require 'config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';

if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    $redirect = $role === 'admin' ? 'admin/dashboard.php' : ($role === 'agent' ? 'agent/dashboard.php' : 'candidat/dashboard.php');
    header('Location: ' . BASE_URL . $redirect);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($identifier) && !empty($password)) {
        // Chercher par email OU par numero_candidat dans candidatures
        $st = $pdo->prepare("SELECT u.* FROM users u
                             LEFT JOIN candidatures c ON c.user_id = u.id
                             WHERE u.email = ? OR c.numero_candidat = ?
                             LIMIT 1");
        $st->execute([$identifier, $identifier]);
        $u = $st->fetch();

        if ($u && password_verify($password, $u['password'])) {
            unset($u['password']);
            $_SESSION['user'] = $u;
            $role = $u['role'];
            $redirect = $role === 'admin' ? 'admin/dashboard.php' : ($role === 'agent' ? 'agent/dashboard.php' : 'candidat/dashboard.php');
            header('Location: ' . BASE_URL . $redirect);
            exit;
        }
    }
    $error = 'Email / Numéro candidat ou mot de passe incorrect.';
}

$title = 'Connexion — Concours Administratifs';
require 'includes/header.php';
?>

<div class="row justify-content-center my-5">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0 p-4">
            <h2 class="h4 text-center mb-4 text-primary font-weight-bold">
                <i class="bi bi-box-arrow-in-right me-2"></i>Espace Connexion
            </h2>

            <?php if (isset($_GET['ok'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>Compte créé avec succès ! Connectez-vous.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            <?php endif; ?>

            <form method="post" action="login.php" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Adresse Email ou N° Candidat</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                        <input type="text" name="email" class="form-control" placeholder="nom@exemple.com ou CONC-2026-000123" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label font-weight-bold">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>

                <button class="btn btn-primary w-100 py-2 mt-2 font-weight-bold" type="submit">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                </button>
            </form>

            <hr class="my-4">

            <div class="bg-light p-3 rounded">
                <small class="text-muted d-block fw-bold mb-1">Comptes de démonstration (Mot de passe: <code>admin123</code>) :</small>
                <small class="text-secondary d-block"><strong>Admin :</strong> admin@gmail.com</small>
                <small class="text-secondary d-block"><strong>Agent :</strong> agent@gmail.com</small>
                <small class="text-secondary d-block"><strong>Candidat :</strong> candidat@gmail.com</small>
            </div>

            <p class="text-center mt-3 mb-0">
                <small>Pas encore de compte ? <a href="register.php" class="text-decoration-none font-weight-bold">Créer un compte</a></small>
            </p>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
