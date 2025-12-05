<?php
// ============================================
// Dashboard Admin - CREx
// Vue d'ensemble avec statistiques
// ============================================

require_once 'auth.php';
requireRole('admin'); // Nécessite le rôle admin

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Récupérer les statistiques
$stats = [];

try {
    // Nombre de messages de contact
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contact_messages");
    $stats['messages'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Messages non lus
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contact_messages WHERE lu = 0");
    $stats['messages_unread'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Nombre de rendez-vous
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments");
    $stats['appointments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Rendez-vous en attente
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments WHERE statut = 'en_attente'");
    $stats['appointments_pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Nombre d'images dans la galerie
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM gallery WHERE active = 1");
    $stats['gallery_images'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Nombre de services
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM services WHERE active = 1");
    $stats['services'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Nombre d'articles de blog
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM blog WHERE status = 'published'");
    $stats['blog_posts'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Nombre d'abonnés newsletter
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM newsletter WHERE status = 'active'");
    $stats['newsletter'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Nombre de visiteurs (derniers 30 jours)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT ip_address) as total FROM visitor_stats WHERE visit_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['visitors_30d'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Nombre d'utilisateurs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE active = 1");
    $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
} catch (PDOException $e) {
    // Si certaines tables n'existent pas encore, utiliser des valeurs par défaut
    error_log("Erreur lors de la récupération des statistiques: " . $e->getMessage());
}

// Récupérer les dernières activités
$recentActivities = [];
try {
    $stmt = $pdo->query("
        SELECT al.*, u.username 
        FROM activity_log al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.date_time DESC
        LIMIT 10
    ");
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la table n'existe pas encore
    try {
        $stmt = $pdo->query("
            SELECT sl.*, u.username 
            FROM security_logs sl
            LEFT JOIN users u ON sl.admin_id = u.id
            ORDER BY sl.date_creation DESC
            LIMIT 10
        ");
        $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        // Ignorer
    }
}

// Récupérer les derniers messages
$recentMessages = [];
try {
    $stmt = $pdo->query("
        SELECT id, nom, email, sujet, date_creation, lu
        FROM contact_messages
        ORDER BY date_creation DESC
        LIMIT 5
    ");
    $recentMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignorer
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - CREx</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
    <script src="theme-init.js"></script>
    <script src="dark-mode.js"></script>
</head>
<body class="admin-body">
    <!-- Sidebar -->
    <?php include 'admin-sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="admin-main">
        <!-- Header -->
        <header class="admin-header">
            <div class="admin-header-content">
                <h1><i class="fas fa-chart-line"></i> Dashboard</h1>
                <div class="admin-header-actions">
                    <span class="admin-user-info">
                        <i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars($currentUser['username']); ?>
                        <span class="admin-role-badge"><?php echo htmlspecialchars($currentUser['role']); ?></span>
                    </span>
                    <a href="admin-logout.php" class="admin-btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </div>
            </div>
        </header>
        
        <!-- Dashboard Content -->
        <main class="admin-content">
            <!-- Statistics Cards -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="admin-stat-content">
                        <h3><?php echo number_format($stats['messages'] ?? 0); ?></h3>
                        <p>Messages reçus</p>
                        <?php if (($stats['messages_unread'] ?? 0) > 0): ?>
                            <span class="admin-stat-badge"><?php echo $stats['messages_unread']; ?> non lus</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="admin-stat-content">
                        <h3><?php echo number_format($stats['appointments'] ?? 0); ?></h3>
                        <p>Rendez-vous</p>
                        <?php if (($stats['appointments_pending'] ?? 0) > 0): ?>
                            <span class="admin-stat-badge"><?php echo $stats['appointments_pending']; ?> en attente</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fas fa-images"></i>
                    </div>
                    <div class="admin-stat-content">
                        <h3><?php echo number_format($stats['gallery_images'] ?? 0); ?></h3>
                        <p>Images galerie</p>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <i class="fas fa-blog"></i>
                    </div>
                    <div class="admin-stat-content">
                        <h3><?php echo number_format($stats['blog_posts'] ?? 0); ?></h3>
                        <p>Articles publiés</p>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="admin-stat-content">
                        <h3><?php echo number_format($stats['visitors_30d'] ?? 0); ?></h3>
                        <p>Visiteurs (30j)</p>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="admin-stat-content">
                        <h3><?php echo number_format($stats['newsletter'] ?? 0); ?></h3>
                        <p>Abonnés newsletter</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity & Messages -->
            <div class="admin-dashboard-grid">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2><i class="fas fa-history"></i> Activité récente</h2>
                    </div>
                    <div class="admin-card-body">
                        <?php if (empty($recentActivities)): ?>
                            <p class="admin-empty-state">Aucune activité récente</p>
                        <?php else: ?>
                            <div class="admin-activity-list">
                                <?php foreach ($recentActivities as $activity): ?>
                                    <div class="admin-activity-item">
                                        <div class="admin-activity-icon">
                                            <i class="fas fa-circle"></i>
                                        </div>
                                        <div class="admin-activity-content">
                                            <p><strong><?php echo htmlspecialchars($activity['username'] ?? 'Système'); ?></strong> 
                                            <?php echo htmlspecialchars($activity['action'] ?? $activity['type'] ?? ''); ?></p>
                                            <span class="admin-activity-time">
                                                <?php 
                                                $date = $activity['date_time'] ?? $activity['date_creation'] ?? '';
                                                if ($date) {
                                                    echo date('d/m/Y H:i', strtotime($date));
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2><i class="fas fa-inbox"></i> Derniers messages</h2>
                        <a href="admin.php" class="admin-btn-link">Voir tout</a>
                    </div>
                    <div class="admin-card-body">
                        <?php if (empty($recentMessages)): ?>
                            <p class="admin-empty-state">Aucun message</p>
                        <?php else: ?>
                            <div class="admin-messages-list">
                                <?php foreach ($recentMessages as $message): ?>
                                    <div class="admin-message-item <?php echo ($message['lu'] == 0) ? 'unread' : ''; ?>">
                                        <div class="admin-message-header">
                                            <strong><?php echo htmlspecialchars($message['nom']); ?></strong>
                                            <?php if ($message['lu'] == 0): ?>
                                                <span class="admin-badge-new">Nouveau</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="admin-message-email"><?php echo htmlspecialchars($message['email']); ?></p>
                                        <?php if ($message['sujet']): ?>
                                            <p class="admin-message-subject"><?php echo htmlspecialchars($message['sujet']); ?></p>
                                        <?php endif; ?>
                                        <span class="admin-message-time">
                                            <?php echo date('d/m/Y H:i', strtotime($message['date_creation'])); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2><i class="fas fa-bolt"></i> Actions rapides</h2>
                </div>
                <div class="admin-card-body">
                    <div class="admin-quick-actions">
                        <a href="admin-pages.php" class="admin-quick-action-btn">
                            <i class="fas fa-file-alt"></i>
                            <span>Gérer les pages</span>
                        </a>
                        <a href="admin-gallery.php" class="admin-quick-action-btn">
                            <i class="fas fa-images"></i>
                            <span>Gérer la galerie</span>
                        </a>
                        <a href="admin-blog.php" class="admin-quick-action-btn">
                            <i class="fas fa-blog"></i>
                            <span>Gérer le blog</span>
                        </a>
                        <a href="admin-services.php" class="admin-quick-action-btn">
                            <i class="fas fa-concierge-bell"></i>
                            <span>Gérer les services</span>
                        </a>
                        <a href="admin-settings.php" class="admin-quick-action-btn">
                            <i class="fas fa-cog"></i>
                            <span>Paramètres</span>
                        </a>
                        <a href="admin-backup.php" class="admin-quick-action-btn">
                            <i class="fas fa-database"></i>
                            <span>Sauvegarder</span>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="admin-script.js"></script>
</body>
</html>

