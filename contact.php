<?php
require 'config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$msgSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msgSuccess = 'Votre message a bien été envoyé. Notre équipe vous répondra dans les plus brefs délais.';
}

$title = 'Contact — Concours Administratifs';
require 'includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 p-4 p-md-5">
            <h2 class="h3 fw-bold text-primary mb-3 text-center"><i class="bi bi-envelope-paper-fill me-2"></i>Contactez-nous</h2>
            <p class="text-muted text-center mb-4">Une question ou une assistance concernant votre candidature ? Remplissez ce formulaire.</p>

            <?php if (!empty($msgSuccess)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($msgSuccess) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            <?php endif; ?>

            <form method="post" action="contact.php" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Nom & Prénom <span class="text-danger">*</span></label>
                        <input type="text" name="nom" class="form-control" required placeholder="Votre nom complet">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Adresse Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="nom@domaine.com">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Sujet <span class="text-danger">*</span></label>
                    <input type="text" name="sujet" class="form-control" required placeholder="Objet de votre demande">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Message <span class="text-danger">*</span></label>
                    <textarea name="message" class="form-control" rows="5" required placeholder="Expliquez en détail votre préoccupation..."></textarea>
                </div>

                <button class="btn btn-primary w-100 py-2 fw-bold fs-5" type="submit">
                    <i class="bi bi-send-fill me-2"></i>Envoyer le message
                </button>
            </form>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>