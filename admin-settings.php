<?php
// ============================================
// Page d'administration - Paramètres CREx
// Gestion des paramètres du site et personnels
// ============================================

session_start();
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

$error = '';
$success = '';

// Traitement des modifications de paramètres
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            
            // Mettre à jour les paramètres du site
            if ($action === 'update_site_settings' && isset($_POST['settings']) && is_array($_POST['settings'])) {
                foreach ($_POST['settings'] as $cle => $valeur) {
                    $stmt = $pdo->prepare("
                        INSERT INTO site_settings (cle, valeur) 
                        VALUES (:cle, :valeur)
                        ON DUPLICATE KEY UPDATE valeur = :valeur, date_modification = NOW()
                    ");
                    $stmt->execute([':cle' => $cle, ':valeur' => $valeur]);
                }
                $success = 'Paramètres du site mis à jour avec succès !';
            }
            
            // Mettre à jour les paramètres personnels
            elseif ($action === 'update_admin_settings' && isset($_POST['admin_settings']) && is_array($_POST['admin_settings'])) {
                $adminId = $_SESSION['admin_id'] ?? 1; // À adapter selon votre système
                foreach ($_POST['admin_settings'] as $cle => $valeur) {
                    $stmt = $pdo->prepare("
                        INSERT INTO admin_settings (admin_id, cle, valeur) 
                        VALUES (:admin_id, :cle, :valeur)
                        ON DUPLICATE KEY UPDATE valeur = :valeur, date_modification = NOW()
                    ");
                    $stmt->execute([':admin_id' => $adminId, ':cle' => $cle, ':valeur' => $valeur]);
                }
                $success = 'Paramètres personnels mis à jour avec succès !';
            }
            
            // Changer le mot de passe
            elseif ($action === 'change_password') {
                $oldPassword = $_POST['old_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                if ($newPassword !== $confirmPassword) {
                    $error = 'Les mots de passe ne correspondent pas.';
                } elseif (strlen($newPassword) < 6) {
                    $error = 'Le mot de passe doit contenir au moins 6 caractères.';
                } else {
                    // Vérifier l'ancien mot de passe (si admin_users est utilisé)
                    // Sinon, mettre à jour directement
                    $adminId = $_SESSION['admin_id'] ?? 1;
                    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$newHash, $adminId]);
                    
                    $success = 'Mot de passe changé avec succès !';
                }
            }
        }
    } catch (PDOException $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

// Initialiser les variables
$settings = [];
$adminSettings = [];

// Récupérer les paramètres du site
try {
    $pdo = getDBConnection();
    
    $settingsStmt = $pdo->query("SELECT * FROM site_settings ORDER BY categorie, cle");
    while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
        if (!isset($settings[$row['categorie']])) {
            $settings[$row['categorie']] = [];
        }
        $settings[$row['categorie']][$row['cle']] = $row;
    }
    
    // Récupérer les paramètres personnels de l'admin
    $adminId = $_SESSION['admin_id'] ?? 1;
    $adminSettingsStmt = $pdo->prepare("SELECT * FROM admin_settings WHERE admin_id = ?");
    $adminSettingsStmt->execute([$adminId]);
    while ($row = $adminSettingsStmt->fetch(PDO::FETCH_ASSOC)) {
        $adminSettings[$row['cle']] = $row['valeur'];
    }
    
} catch (PDOException $e) {
    $error = "Erreur de connexion : " . $e->getMessage();
    $settings = [];
    $adminSettings = [];
}

// Catégories de paramètres
$categories = [
    'general' => 'Général',
    'contact' => 'Contact',
    'social' => 'Réseaux sociaux',
    'security' => 'Sécurité'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - CREx</title>
    <link rel="stylesheet" href="admin-style.css">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">
    <script src="theme-init.js"></script>
    <script src="dark-mode.js"></script>
    <style>
        .settings-section {
            background: var(--admin-white);
            border-radius: var(--admin-radius);
            box-shadow: var(--admin-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .settings-section h2 {
            color: var(--admin-primary);
            margin-top: 0;
            border-bottom: 2px solid var(--admin-border);
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .settings-section h3 {
            color: var(--admin-primary);
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-header-content">
            <h1>⚙️ Administration CREx - Paramètres</h1>
            <div class="admin-nav-links">
                <button class="theme-toggle" aria-label="Basculer le thème" type="button">
                    <span class="theme-icon moon-icon">🌙</span>
                </button>
                <a href="admin.php" class="admin-nav-link">📧 Messages</a>
                <a href="admin-content.php" class="admin-nav-link">📄 Contenu</a>
                <a href="admin-testimonials.php" class="admin-nav-link">💬 Témoignages</a>
                <a href="admin-stats.php" class="admin-nav-link">📊 Stats</a>
                <a href="admin-settings.php" class="admin-nav-link active">⚙️ Paramètres</a>
                <a href="admin-logout.php" class="admin-logout-btn">🚪 Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="admin-container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Paramètres du site -->
        <div class="settings-section">
            <h2>🌐 Paramètres du site</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_site_settings">
                
                <?php foreach ($categories as $catKey => $catName): ?>
                    <?php if (isset($settings[$catKey])): ?>
                        <h3 style="color: #4AB0D9; margin-top: 2rem; margin-bottom: 1rem;"><?php echo $catName; ?></h3>
                        <div class="settings-grid">
                            <?php foreach ($settings[$catKey] as $cle => $setting): ?>
                                <div class="form-group">
                                    <label for="setting_<?php echo htmlspecialchars($cle); ?>">
                                        <?php echo htmlspecialchars(isset($setting['description']) ? $setting['description'] : $cle); ?>
                                    </label>
                                    <?php 
                                    $settingType = isset($setting['type']) ? $setting['type'] : 'text';
                                    $settingValeur = isset($setting['valeur']) ? $setting['valeur'] : '';
                                    ?>
                                    <?php if ($settingType === 'boolean'): ?>
                                        <select name="settings[<?php echo htmlspecialchars($cle); ?>]" id="setting_<?php echo htmlspecialchars($cle); ?>">
                                            <option value="0" <?php echo $settingValeur == '0' ? 'selected' : ''; ?>>Non</option>
                                            <option value="1" <?php echo $settingValeur == '1' ? 'selected' : ''; ?>>Oui</option>
                                        </select>
                                    <?php elseif ($settingType === 'html'): ?>
                                        <textarea name="settings[<?php echo htmlspecialchars($cle); ?>]" id="setting_<?php echo htmlspecialchars($cle); ?>"><?php echo htmlspecialchars($settingValeur); ?></textarea>
                                    <?php else: ?>
                                        <input type="<?php echo $settingType === 'number' ? 'number' : 'text'; ?>" 
                                               name="settings[<?php echo htmlspecialchars($cle); ?>]" 
                                               id="setting_<?php echo htmlspecialchars($cle); ?>"
                                               value="<?php echo htmlspecialchars($settingValeur); ?>">
                                    <?php endif; ?>
                                    <small>Clé: <?php echo htmlspecialchars($cle); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <button type="submit" class="btn-primary-admin">💾 Enregistrer les paramètres du site</button>
            </form>
        </div>

        <!-- Paramètres personnels -->
        <div class="settings-section">
            <h2>👤 Paramètres personnels</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_admin_settings">
                
                <div class="form-group">
                    <label>Langue préférée</label>
                    <select name="admin_settings[langue]">
                        <option value="fr" <?php echo ($adminSettings['langue'] ?? 'fr') === 'fr' ? 'selected' : ''; ?>>Français</option>
                        <option value="en" <?php echo ($adminSettings['langue'] ?? '') === 'en' ? 'selected' : ''; ?>>English</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Notifications par email</label>
                    <select name="admin_settings[notifications_email]">
                        <option value="1" <?php echo ($adminSettings['notifications_email'] ?? '1') == '1' ? 'selected' : ''; ?>>Activées</option>
                        <option value="0" <?php echo ($adminSettings['notifications_email'] ?? '') == '0' ? 'selected' : ''; ?>>Désactivées</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Thème de l'interface</label>
                    <select name="admin_settings[theme]">
                        <option value="light" <?php echo ($adminSettings['theme'] ?? 'light') === 'light' ? 'selected' : ''; ?>>Clair</option>
                        <option value="dark" <?php echo ($adminSettings['theme'] ?? '') === 'dark' ? 'selected' : ''; ?>>Sombre</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-primary-admin">💾 Enregistrer les paramètres personnels</button>
            </form>
        </div>

        <!-- Changer le mot de passe -->
        <div class="settings-section">
            <h2>🔐 Changer le mot de passe</h2>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label>Nouveau mot de passe</label>
                    <input type="password" name="new_password" required minlength="6">
                    <small>Minimum 6 caractères</small>
                </div>
                
                <div class="form-group">
                    <label>Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
                
                <button type="submit" class="btn-primary-admin">🔒 Changer le mot de passe</button>
            </form>
        </div>
    </div>
</body>
</html>

