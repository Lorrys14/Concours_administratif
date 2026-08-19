<?php
require '../config/database.php';
require '../includes/auth.php';
require_role('candidat');

$u = $_SESSION['user'];

// Marquer comme lues si demandé
if (isset($_GET['read_all'])) {
    $stMark = $pdo->prepare("UPDATE notifications SET lu = 1 WHERE user_id = ?");
    $stMark->execute([$u['id']]);
}

$stNotif = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stNotif->execute([$u['id']]);
$notifications = $stNotif->fetchAll();

$title = 'Notifications — Espace Candidat';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-dark m-0"><i class="bi bi-bell-fill text-primary me-2"></i>Mes Notifications</h1>
        <p class="text-muted m-0">Historique des messages et alertes reçus pour vos candidatures.</p>
    </div>
    <?php if (!empty($notifications)): ?>
        <a href="notifications.php?read_all=1" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-check-all me-1"></i>Tout marquer comme lu
        </a>
    <?php endif; ?>
</div>

<div class="card shadow-sm border-0 p-4">
    <?php if (!empty($notifications)): ?>
        <div class="list-group list-group-flush">
            <?php foreach ($notifications as $n): ?>
                <div class="list-group-item p-3 mb-2 rounded-3 border <?= $n['lu'] ? 'bg-light text-muted' : 'bg-white border-primary shadow-sm' ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold text-dark fs-6"><?= htmlspecialchars($n['message']) ?></span>
                        <small class="text-muted ms-3 text-nowrap"><i class="bi bi-clock me-1"></i><?= date('d/m/Y à H:i', strtotime($n['created_at'])) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-bell-slash fs-1 text-secondary d-block mb-2"></i>
            Vous n'avez aucune notification.
        </div>
    <?php endif; ?>
</div>

<?php require '../includes/footer.php'; ?>