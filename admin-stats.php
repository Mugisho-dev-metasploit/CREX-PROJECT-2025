<?php
// ============================================
// Page d'administration - Statistiques CREx
// ============================================

session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

// Initialiser les variables
$messagesStats = ['total' => 0, 'non_lus' => 0, 'non_repondu' => 0, 'aujourd_hui' => 0, 'hier' => 0];
$contentStats = ['total' => 0, 'publies' => 0, 'brouillons' => 0, 'pages' => 0, 'articles' => 0, 'services' => 0];
$testimonialsStats = ['total' => 0, 'actifs' => 0];
$messagesByMonth = [];
$recentMessages = [];
$error = '';

try {
    $pdo = getDBConnection();
    
    // Vérifier et créer la table testimonials si elle n'existe pas
    $tableExists = $pdo->query("SHOW TABLES LIKE 'testimonials'")->rowCount() > 0;
    if (!$tableExists) {
        $createTableSQL = "CREATE TABLE IF NOT EXISTS `testimonials` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nom` VARCHAR(100) NOT NULL,
            `role` VARCHAR(100) DEFAULT NULL,
            `message` TEXT NOT NULL,
            `photo_url` VARCHAR(500) DEFAULT NULL,
            `note` INT DEFAULT 5,
            `actif` TINYINT(1) DEFAULT 1,
            `ordre` INT DEFAULT 0,
            `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `date_modification` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_actif` (`actif`),
            INDEX `idx_ordre` (`ordre`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $pdo->exec($createTableSQL);
    }
    
    // Statistiques des messages
    $messagesStats = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN lu = 0 THEN 1 ELSE 0 END) as non_lus,
            SUM(CASE WHEN repondu = 0 THEN 1 ELSE 0 END) as non_repondu,
            COUNT(CASE WHEN DATE(date_creation) = CURDATE() THEN 1 END) as aujourd_hui,
            COUNT(CASE WHEN DATE(date_creation) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 END) as hier
        FROM contact_messages
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Statistiques du contenu
    $contentStats = $pdo->query("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN statut = 'publie' THEN 1 END) as publies,
            COUNT(CASE WHEN statut = 'brouillon' THEN 1 END) as brouillons,
            COUNT(CASE WHEN type = 'page' THEN 1 END) as pages,
            COUNT(CASE WHEN type = 'article' THEN 1 END) as articles,
            COUNT(CASE WHEN type = 'service' THEN 1 END) as services
        FROM site_content
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Statistiques des témoignages
    $testimonialsStats = $pdo->query("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN actif = 1 THEN 1 END) as actifs
        FROM testimonials
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Messages par mois (derniers 6 mois)
    $messagesByMonth = $pdo->query("
        SELECT 
            DATE_FORMAT(date_creation, '%Y-%m') as mois,
            DATE_FORMAT(date_creation, '%M %Y') as mois_label,
            COUNT(*) as nombre
        FROM contact_messages
        WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(date_creation, '%Y-%m')
        ORDER BY mois ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Messages récents (derniers 5)
    $recentMessages = $pdo->query("
        SELECT nom, email, date_creation 
        FROM contact_messages 
        ORDER BY date_creation DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Erreur : " . $e->getMessage();
    $messagesStats = ['total' => 0, 'non_lus' => 0, 'non_repondu' => 0, 'aujourd_hui' => 0, 'hier' => 0];
    $contentStats = ['total' => 0, 'publies' => 0, 'brouillons' => 0];
    $testimonialsStats = ['total' => 0, 'actifs' => 0];
    $messagesByMonth = [];
    $recentMessages = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques - CREx</title>
    <link rel="stylesheet" href="admin-style.css">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">
    <script src="theme-init.js"></script>
    <script src="dark-mode.js"></script>
    <style>
        .chart-section {
            background: var(--admin-white);
            border-radius: var(--admin-radius);
            padding: 2rem;
            box-shadow: var(--admin-shadow);
            margin-bottom: 2rem;
        }
        .chart-section h2 {
            color: var(--admin-primary);
            margin-top: 0;
            margin-bottom: 1.5rem;
        }
        .bar-chart {
            display: flex;
            align-items: flex-end;
            gap: 1rem;
            height: 200px;
            padding: 1rem 0;
        }
        .bar {
            flex: 1;
            background: linear-gradient(to top, var(--admin-primary), var(--admin-primary-dark));
            border-radius: 4px 4px 0 0;
            position: relative;
            min-height: 10px;
        }
        .bar-label {
            text-align: center;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #666;
        }
        .bar-value {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-weight: 600;
            color: #4AB0D9;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-header-content">
            <h1>📊 Administration CREx - Statistiques</h1>
            <div class="admin-nav-links">
                <button class="theme-toggle" aria-label="Basculer le thème" type="button">
                    <span class="theme-icon moon-icon">🌙</span>
                </button>
                <a href="admin.php" class="admin-nav-link">📧 Messages</a>
                <a href="admin-content.php" class="admin-nav-link">📄 Contenu</a>
                <a href="admin-testimonials.php" class="admin-nav-link">💬 Témoignages</a>
                <a href="admin-stats.php" class="admin-nav-link active">📊 Stats</a>
                <a href="admin-settings.php" class="admin-nav-link">⚙️ Paramètres</a>
                <a href="admin-logout.php" class="admin-logout-btn">🚪 Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="admin-container">
        <!-- Statistiques Messages -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Messages de contact</h3>
                <p class="stat-value"><?php echo $messagesStats['total']; ?></p>
                <p class="stat-label">Total des messages</p>
            </div>
            <div class="stat-card">
                <h3>Messages non lus</h3>
                <p class="stat-value" style="color: #ffc107;"><?php echo $messagesStats['non_lus']; ?></p>
                <p class="stat-label">Nécessitent votre attention</p>
            </div>
            <div class="stat-card">
                <h3>Non répondu</h3>
                <p class="stat-value" style="color: #dc3545;"><?php echo $messagesStats['non_repondu']; ?></p>
                <p class="stat-label">En attente de réponse</p>
            </div>
            <div class="stat-card">
                <h3>Aujourd'hui</h3>
                <p class="stat-value" style="color: #28a745;"><?php echo $messagesStats['aujourd_hui']; ?></p>
                <p class="stat-label">Messages reçus aujourd'hui</p>
            </div>
            <div class="stat-card">
                <h3>Hier</h3>
                <p class="stat-value"><?php echo $messagesStats['hier']; ?></p>
                <p class="stat-label">Messages reçus hier</p>
            </div>
        </div>

        <!-- Statistiques Contenu -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Contenu total</h3>
                <p class="stat-value"><?php echo $contentStats['total']; ?></p>
                <p class="stat-label">Tous les contenus</p>
            </div>
            <div class="stat-card">
                <h3>Publiés</h3>
                <p class="stat-value" style="color: #28a745;"><?php echo $contentStats['publies']; ?></p>
                <p class="stat-label">Contenus publiés</p>
            </div>
            <div class="stat-card">
                <h3>Brouillons</h3>
                <p class="stat-value" style="color: #ffc107;"><?php echo $contentStats['brouillons']; ?></p>
                <p class="stat-label">En brouillon</p>
            </div>
            <div class="stat-card">
                <h3>Pages</h3>
                <p class="stat-value"><?php echo $contentStats['pages']; ?></p>
                <p class="stat-label">Pages du site</p>
            </div>
            <div class="stat-card">
                <h3>Articles</h3>
                <p class="stat-value"><?php echo $contentStats['articles']; ?></p>
                <p class="stat-label">Articles</p>
            </div>
            <div class="stat-card">
                <h3>Services</h3>
                <p class="stat-value"><?php echo $contentStats['services']; ?></p>
                <p class="stat-label">Services</p>
            </div>
        </div>

        <!-- Statistiques Témoignages -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Témoignages</h3>
                <p class="stat-value"><?php echo $testimonialsStats['total']; ?></p>
                <p class="stat-label">Total des témoignages</p>
            </div>
            <div class="stat-card">
                <h3>Témoignages actifs</h3>
                <p class="stat-value" style="color: #28a745;"><?php echo $testimonialsStats['actifs']; ?></p>
                <p class="stat-label">Affichés sur le site</p>
            </div>
        </div>

        <!-- Graphique Messages par mois -->
        <div class="chart-section">
            <h2>📈 Messages par mois (6 derniers mois)</h2>
            <?php if (!empty($messagesByMonth)): ?>
                <?php
                $maxMessages = max(array_column($messagesByMonth, 'nombre'));
                ?>
                <div class="bar-chart">
                    <?php foreach ($messagesByMonth as $month): ?>
                        <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                            <div class="bar" style="height: <?php echo $maxMessages > 0 ? ($month['nombre'] / $maxMessages * 100) : 0; ?>%;">
                                <div class="bar-value"><?php echo $month['nombre']; ?></div>
                            </div>
                            <div class="bar-label"><?php echo date('M', strtotime($month['mois'] . '-01')); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #999; text-align: center; padding: 2rem;">Aucune donnée disponible</p>
            <?php endif; ?>
        </div>

        <!-- Messages récents -->
        <div class="chart-section">
            <h2>📧 Messages récents</h2>
            <?php if (!empty($recentMessages)): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #E3F6FD;">
                            <th style="padding: 0.75rem; text-align: left;">Nom</th>
                            <th style="padding: 0.75rem; text-align: left;">Email</th>
                            <th style="padding: 0.75rem; text-align: left;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentMessages as $msg): ?>
                            <tr style="border-bottom: 1px solid #E3F6FD;">
                                <td style="padding: 0.75rem;"><?php echo htmlspecialchars($msg['nom']); ?></td>
                                <td style="padding: 0.75rem;"><?php echo htmlspecialchars($msg['email']); ?></td>
                                <td style="padding: 0.75rem;"><?php echo date('d/m/Y H:i', strtotime($msg['date_creation'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #999; text-align: center; padding: 2rem;">Aucun message récent</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

