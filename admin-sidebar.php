<?php
// Sidebar pour le panneau d'administration
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <div class="admin-sidebar-header">
        <div class="admin-logo">
            <i class="fas fa-hospital"></i>
            <span>CREx Admin</span>
        </div>
    </div>
    
    <nav class="admin-sidebar-nav">
        <ul>
            <li>
                <a href="admin-dashboard.php" class="<?php echo ($currentPage == 'admin-dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="admin.php" class="<?php echo ($currentPage == 'admin.php') ? 'active' : ''; ?>">
                    <i class="fas fa-inbox"></i>
                    <span>Messages</span>
                    <?php
                    try {
                        $pdo = getDBConnection();
                        $stmt = $pdo->query("SELECT COUNT(*) as total FROM contact_messages WHERE lu = 0");
                        $unread = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                        if ($unread > 0) {
                            echo '<span class="admin-nav-badge">' . $unread . '</span>';
                        }
                    } catch (Exception $e) {}
                    ?>
                </a>
            </li>
            <li>
                <a href="admin-appointments.php" class="<?php echo ($currentPage == 'admin-appointments.php') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Rendez-vous</span>
                    <?php
                    try {
                        $pdo = getDBConnection();
                        $stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments WHERE statut = 'en_attente'");
                        $pending = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                        if ($pending > 0) {
                            echo '<span class="admin-nav-badge">' . $pending . '</span>';
                        }
                    } catch (Exception $e) {}
                    ?>
                </a>
            </li>
            <li>
                <a href="admin-pages.php" class="<?php echo ($currentPage == 'admin-pages.php') ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Pages</span>
                </a>
            </li>
            <li>
                <a href="admin-gallery.php" class="<?php echo ($currentPage == 'admin-gallery.php') ? 'active' : ''; ?>">
                    <i class="fas fa-images"></i>
                    <span>Galerie</span>
                </a>
            </li>
            <li>
                <a href="admin-services.php" class="<?php echo ($currentPage == 'admin-services.php') ? 'active' : ''; ?>">
                    <i class="fas fa-concierge-bell"></i>
                    <span>Services</span>
                </a>
            </li>
            <li>
                <a href="admin-blog.php" class="<?php echo ($currentPage == 'admin-blog.php') ? 'active' : ''; ?>">
                    <i class="fas fa-blog"></i>
                    <span>Blog/News</span>
                </a>
            </li>
            <li>
                <a href="admin-newsletter.php" class="<?php echo ($currentPage == 'admin-newsletter.php') ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper"></i>
                    <span>Newsletter</span>
                </a>
            </li>
            <li>
                <a href="admin-stats.php" class="<?php echo ($currentPage == 'admin-stats.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Statistiques</span>
                </a>
            </li>
            <li>
                <a href="admin-users.php" class="<?php echo ($currentPage == 'admin-users.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Utilisateurs</span>
                </a>
            </li>
            <li>
                <a href="admin-settings.php" class="<?php echo ($currentPage == 'admin-settings.php') ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Paramètres</span>
                </a>
            </li>
            <li>
                <a href="admin-backup.php" class="<?php echo ($currentPage == 'admin-backup.php') ? 'active' : ''; ?>">
                    <i class="fas fa-database"></i>
                    <span>Sauvegarde</span>
                </a>
            </li>
            <li>
                <a href="admin-activity.php" class="<?php echo ($currentPage == 'admin-activity.php') ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i>
                    <span>Journal d'activité</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="admin-sidebar-footer">
        <a href="../index.html" target="_blank" class="admin-view-site">
            <i class="fas fa-external-link-alt"></i>
            <span>Voir le site</span>
        </a>
    </div>
</aside>

