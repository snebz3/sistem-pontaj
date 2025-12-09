<?php
if (!isset($_SESSION['angajat_id'])) {
    header('Location: ../index.php');
    exit();
}
?>
<!-- Header simplu -->
<div style="background: #343a40; color: white; padding: 15px; margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto;">
        <h1 style="margin: 0;">
            🏢 Sistem Pontaj - Orar Angajați
        </h1>
        <div>
            <span>👤 <?php echo $_SESSION['nume']; ?></span>
            <?php if ($_SESSION['este_admin']): ?>
                <span style="margin-left: 10px; background: #dc3545; padding: 2px 8px; border-radius: 10px; font-size: 12px;">ADMIN</span>
            <?php endif; ?>
            <a href="../../dashboard.php" style="color: white; margin-left: 15px; text-decoration: none;">🏠 Dashboard</a>
            <a href="../../logout.php" style="color: white; margin-left: 15px; text-decoration: none;">🚪 Logout</a>
        </div>
    </div>
</div>