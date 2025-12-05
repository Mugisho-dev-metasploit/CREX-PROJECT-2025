<?php
// ============================================
// Page d'administration - Gestion des témoignages CREx
// ============================================

session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

$error = '';
$success = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            
            if ($action === 'create' || $action === 'update') {
                $stmt = $pdo->prepare("
                    INSERT INTO testimonials (nom, role, message, photo_url, note, actif, ordre)
                    VALUES (:nom, :role, :message, :photo_url, :note, :actif, :ordre)
                    ON DUPLICATE KEY UPDATE 
                        nom = :nom, role = :role, message = :message, 
                        photo_url = :photo_url, note = :note, actif = :actif, ordre = :ordre
                ");
                
                if ($action === 'update') {
                    $stmt = $pdo->prepare("
                        UPDATE testimonials 
                        SET nom = :nom, role = :role, message = :message, 
                            photo_url = :photo_url, note = :note, actif = :actif, ordre = :ordre
                        WHERE id = :id
                    ");
                }
                
                $stmt->execute([
                    ':nom' => $_POST['nom'],
                    ':role' => $_POST['role'] ?: null,
                    ':message' => $_POST['message'],
                    ':photo_url' => $_POST['photo_url'] ?: null,
                    ':note' => isset($_POST['note']) ? (int)$_POST['note'] : 5,
                    ':actif' => isset($_POST['actif']) ? 1 : 0,
                    ':ordre' => (int)$_POST['ordre'],
                    ':id' => $action === 'update' ? (int)$_POST['id'] : null
                ]);
                
                $success = 'Témoignage ' . ($action === 'create' ? 'créé' : 'modifié') . ' avec succès !';
            }
            
            elseif ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
                $stmt->execute([(int)$_POST['id']]);
                $success = 'Témoignage supprimé avec succès !';
            }
        }
    } catch (PDOException $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

// Initialiser les variables
$editTestimonial = null;
$testimonials = [];

// Récupérer les témoignages
try {
    $pdo = getDBConnection();
    
    // Vérifier si la table existe, sinon la créer
    $tableExists = $pdo->query("SHOW TABLES LIKE 'testimonials'")->rowCount() > 0;
    if (!$tableExists) {
        $createTableSQL = "CREATE TABLE IF NOT EXISTS `testimonials` (
            `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
            `nom` VARCHAR(100) NOT NULL COMMENT 'Nom de la personne',
            `role` VARCHAR(100) DEFAULT NULL COMMENT 'Rôle/Fonction (ex: Patient, Sportif)',
            `message` TEXT NOT NULL COMMENT 'Contenu du témoignage',
            `photo_url` VARCHAR(500) DEFAULT NULL COMMENT 'URL de la photo',
            `note` INT DEFAULT 5 COMMENT 'Note de 1 à 5',
            `actif` TINYINT(1) DEFAULT 1 COMMENT 'Témoignage actif (affiché sur le site)',
            `ordre` INT DEFAULT 0 COMMENT 'Ordre d''affichage',
            `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
            `date_modification` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date de modification',
            INDEX `idx_actif` (`actif`) COMMENT 'Index sur le statut actif',
            INDEX `idx_ordre` (`ordre`) COMMENT 'Index sur l''ordre'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table des témoignages clients'";
        $pdo->exec($createTableSQL);
        $success = 'Table testimonials créée automatiquement !';
    }
    
    $stmt = $pdo->query("SELECT * FROM testimonials ORDER BY ordre ASC, date_creation DESC");
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer le témoignage à éditer si demandé
    if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
        $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id = ?");
        $stmt->execute([(int)$_GET['edit']]);
        $editTestimonial = $stmt->fetch(PDO::FETCH_ASSOC);
        // Si le témoignage n'existe pas, $editTestimonial sera false, on le remet à null
        if ($editTestimonial === false) {
            $editTestimonial = null;
        }
    }
} catch (PDOException $e) {
    $error = "Erreur : " . $e->getMessage();
    $testimonials = [];
    $editTestimonial = null;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Témoignages - CREx</title>
    <link rel="stylesheet" href="admin-style.css">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">
    <script src="theme-init.js"></script>
    <script src="dark-mode.js"></script>
    <style>
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .testimonial-card {
            background: var(--admin-white);
            border-radius: var(--admin-radius);
            padding: 1.5rem;
            box-shadow: var(--admin-shadow);
            transition: all 0.3s ease;
        }
        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--admin-shadow-lg);
        }
        .testimonial-card.inactive { opacity: 0.6; }
        .stars { color: #ffc107; font-size: 1.2rem; }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-header-content">
            <h1>💬 Administration CREx - Témoignages</h1>
            <div class="admin-nav-links">
                <button class="theme-toggle" aria-label="Basculer le thème" type="button">
                    <span class="theme-icon moon-icon">🌙</span>
                </button>
                <a href="admin.php" class="admin-nav-link">📧 Messages</a>
                <a href="admin-content.php" class="admin-nav-link">📄 Contenu</a>
                <a href="admin-testimonials.php" class="admin-nav-link active">💬 Témoignages</a>
                <a href="admin-stats.php" class="admin-nav-link">📊 Stats</a>
                <a href="admin-settings.php" class="admin-nav-link">⚙️ Paramètres</a>
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

        <div class="admin-toolbar">
            <button onclick="window.location.href='?edit=0'" class="btn-primary-admin">➕ Nouveau témoignage</button>
        </div>

        <div class="testimonials-grid">
            <?php if (empty($testimonials)): ?>
                <div class="empty-state" style="grid-column: 1/-1;">
                    <h3>Aucun témoignage trouvé</h3>
                    <p>Créez-en un nouveau !</p>
                </div>
            <?php else: ?>
                <?php foreach ($testimonials as $testimonial): ?>
                    <div class="testimonial-card <?php echo !$testimonial['actif'] ? 'inactive' : ''; ?>">
                        <?php if ($testimonial['photo_url']): ?>
                            <img src="<?php echo htmlspecialchars($testimonial['photo_url']); ?>" alt="<?php echo htmlspecialchars($testimonial['nom']); ?>" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                        <?php endif; ?>
                        <div class="stars"><?php echo str_repeat('★', $testimonial['note']); ?><?php echo str_repeat('☆', 5 - $testimonial['note']); ?></div>
                        <p style="margin: 1rem 0; font-style: italic;"><?php echo nl2br(htmlspecialchars($testimonial['message'])); ?></p>
                        <strong><?php echo htmlspecialchars($testimonial['nom']); ?></strong>
                        <?php if ($testimonial['role']): ?>
                            <p style="color: #666; font-size: 0.9rem; margin: 0.5rem 0 0 0;"><?php echo htmlspecialchars($testimonial['role']); ?></p>
                        <?php endif; ?>
                        <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                            <a href="?edit=<?php echo $testimonial['id']; ?>" class="btn-primary-admin" style="padding: 0.5rem 1rem; font-size: 0.85rem;">✏️ Modifier</a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer ce témoignage ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $testimonial['id']; ?>">
                                <button type="submit" style="background: #dc3545; color: white; padding: 0.5rem 1rem; border: none; border-radius: 8px; cursor: pointer; font-size: 0.85rem;">🗑️ Supprimer</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal formulaire -->
    <div class="form-modal <?php echo $editTestimonial || (isset($_GET['edit']) && $_GET['edit'] == '0') ? 'active' : ''; ?>" id="testimonialModal">
        <div class="form-modal-content">
            <h2><?php echo isset($editTestimonial['id']) ? 'Modifier le témoignage' : 'Nouveau témoignage'; ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo isset($editTestimonial['id']) ? 'update' : 'create'; ?>">
                <?php if (isset($editTestimonial['id'])): ?>
                    <input type="hidden" name="id" value="<?php echo (int)$editTestimonial['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Nom complet *</label>
                    <input type="text" name="nom" value="<?php echo isset($editTestimonial['nom']) ? htmlspecialchars($editTestimonial['nom']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Rôle / Fonction</label>
                    <input type="text" name="role" value="<?php echo isset($editTestimonial['role']) ? htmlspecialchars($editTestimonial['role']) : ''; ?>" placeholder="Ex: Patient, Sportif, etc.">
                </div>
                
                <div class="form-group">
                    <label>Message *</label>
                    <textarea name="message" required><?php echo isset($editTestimonial['message']) ? htmlspecialchars($editTestimonial['message']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Note (1-5)</label>
                    <select name="note">
                        <?php 
                        $currentNote = isset($editTestimonial['note']) ? (int)$editTestimonial['note'] : 5;
                        for ($i = 5; $i >= 1; $i--): 
                        ?>
                            <option value="<?php echo $i; ?>" <?php echo ($currentNote == $i) ? 'selected' : ''; ?>>
                                <?php echo $i; ?> <?php echo $i == 1 ? 'étoile' : 'étoiles'; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>URL de la photo</label>
                    <input type="url" name="photo_url" value="<?php echo isset($editTestimonial['photo_url']) ? htmlspecialchars($editTestimonial['photo_url']) : ''; ?>" placeholder="https://...">
                </div>
                
                <div class="form-group">
                    <label>Ordre d'affichage</label>
                    <input type="number" name="ordre" value="<?php echo isset($editTestimonial['ordre']) ? (int)$editTestimonial['ordre'] : '0'; ?>">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="actif" <?php echo (isset($editTestimonial['actif']) && $editTestimonial['actif']) || !$editTestimonial ? 'checked' : ''; ?>>
                        Témoignage actif (affiché sur le site)
                    </label>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn-primary-admin">💾 Enregistrer</button>
                    <a href="admin-testimonials.php" class="btn-primary-admin" style="background: #6c757d;">❌ Annuler</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

