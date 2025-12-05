<?php
// Sidebar pour le panneau d'administration
$currentPage = basename($_SERVER['PHP_SELF']);
$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// Charger les permissions si disponible
if (file_exists(__DIR__ . '/permissions.php')) {
    require_once __DIR__ . '/permissions.php';
} else {
    // Fonctions de fallback si permissions.php n'existe pas
    function canManageAdmins() { return true; }
    function canManageSettings() { return true; }
    function canViewDatabase() { return true; }
}

// Compter les messages non lus
$unreadMessages = 0;
$pendingAppointments = 0;
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contact_messages WHERE lu = 0");
    $unreadMessages = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments WHERE statut = 'en_attente'");
    $pendingAppointments = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
} catch (Exception $e) {
    // Ignore errors
}
?>
<!-- Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar-header">
        <div class="admin-logo">
            <i class="fas fa-hospital-alt"></i>
            <span>CREx Admin</span>
        </div>
    </div>
    
    <nav class="admin-sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($section == 'dashboard' || ($currentPage == 'admin.php' && !isset($_GET['section']))) ? 'active' : ''; ?>" 
                   href="admin.php?section=dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($section == 'messages' || ($currentPage == 'admin.php' && isset($_GET['section']) && $_GET['section'] == 'messages')) ? 'active' : ''; ?>" 
                   href="admin.php?section=messages">
                    <i class="fas fa-inbox"></i>
                    <span>Messages</span>
                    <?php if ($unreadMessages > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-auto"><?php echo $unreadMessages; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($section == 'appointments' || ($currentPage == 'admin.php' && isset($_GET['section']) && $_GET['section'] == 'appointments')) ? 'active' : ''; ?>" 
                   href="admin.php?section=appointments">
                    <i class="fas fa-calendar-check"></i>
                    <span>Rendez-vous</span>
                    <?php if ($pendingAppointments > 0): ?>
                        <span class="badge bg-warning rounded-pill ms-auto"><?php echo $pendingAppointments; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="admin.php?section=gallery">
                    <i class="fas fa-images"></i>
                    <span>Galerie</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="admin.php?section=services">
                    <i class="fas fa-concierge-bell"></i>
                    <span>Services</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="admin.php?section=blog">
                    <i class="fas fa-blog"></i>
                    <span>Blog/News</span>
                </a>
            </li>
            <?php if (canManageAdmins()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($section == 'admins') ? 'active' : ''; ?>" href="admin.php?section=admins">
                    <i class="fas fa-users-cog"></i>
                    <span>Administrateurs</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (canManageSettings()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($section == 'settings') ? 'active' : ''; ?>" href="admin.php?section=settings">
                    <i class="fas fa-cog"></i>
                    <span>Paramètres</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (canViewDatabase()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'admin-database.php') ? 'active' : ''; ?>" href="admin-database.php">
                    <i class="fas fa-database"></i>
                    <span>Base de données</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item mt-auto">
                <a class="nav-link text-danger" href="admin-logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Déconnexion</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="admin-sidebar-footer">
        <a href="index.html" target="_blank" class="btn btn-outline-light btn-sm w-100">
            <i class="fas fa-external-link-alt me-2"></i>
            Voir le site
        </a>
    </div>
</aside>

<!-- Overlay pour mobile -->
<div class="admin-sidebar-overlay" id="sidebarOverlay"></div>

