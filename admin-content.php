<?php
// ============================================
// Page d'administration - Gestion du contenu CREx
// CRUD complet pour le contenu du site
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

// Traitement des actions (CRUD)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            
            // CRÉER
            if ($action === 'create') {
                $stmt = $pdo->prepare("
                    INSERT INTO site_content (type, slug, titre, sous_titre, contenu, description, image_url, ordre, statut, meta_keywords)
                    VALUES (:type, :slug, :titre, :sous_titre, :contenu, :description, :image_url, :ordre, :statut, :meta_keywords)
                ");
                
                $slug = strtolower(trim(preg_replace('/[^a-z0-9-]+/', '-', $_POST['titre']), '-'));
                $datePub = ($_POST['statut'] === 'publie' && !empty($_POST['date_publication'])) 
                    ? $_POST['date_publication'] : null;
                
                $stmt->execute([
                    ':type' => $_POST['type'],
                    ':slug' => $slug,
                    ':titre' => $_POST['titre'],
                    ':sous_titre' => $_POST['sous_titre'] ?: null,
                    ':contenu' => $_POST['contenu'] ?: null,
                    ':description' => $_POST['description'] ?: null,
                    ':image_url' => $_POST['image_url'] ?: null,
                    ':ordre' => (int)$_POST['ordre'],
                    ':statut' => $_POST['statut'],
                    ':meta_keywords' => $_POST['meta_keywords'] ?: null
                ]);
                
                if ($datePub) {
                    $pdo->prepare("UPDATE site_content SET date_publication = ? WHERE id = ?")
                        ->execute([$datePub, $pdo->lastInsertId()]);
                }
                
                $success = 'Contenu créé avec succès !';
            }
            
            // MODIFIER
            elseif ($action === 'update') {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("
                    UPDATE site_content 
                    SET type = :type, slug = :slug, titre = :titre, sous_titre = :sous_titre, 
                        contenu = :contenu, description = :description, image_url = :image_url, 
                        ordre = :ordre, statut = :statut, meta_keywords = :meta_keywords,
                        date_publication = :date_publication
                    WHERE id = :id
                ");
                
                $slug = strtolower(trim(preg_replace('/[^a-z0-9-]+/', '-', $_POST['titre']), '-'));
                $datePub = ($_POST['statut'] === 'publie' && !empty($_POST['date_publication'])) 
                    ? $_POST['date_publication'] : null;
                
                $stmt->execute([
                    ':id' => $id,
                    ':type' => $_POST['type'],
                    ':slug' => $slug,
                    ':titre' => $_POST['titre'],
                    ':sous_titre' => $_POST['sous_titre'] ?: null,
                    ':contenu' => $_POST['contenu'] ?: null,
                    ':description' => $_POST['description'] ?: null,
                    ':image_url' => $_POST['image_url'] ?: null,
                    ':ordre' => (int)$_POST['ordre'],
                    ':statut' => $_POST['statut'],
                    ':meta_keywords' => $_POST['meta_keywords'] ?: null,
                    ':date_publication' => $datePub
                ]);
                
                $success = 'Contenu modifié avec succès !';
            }
            
            // SUPPRIMER
            elseif ($action === 'delete') {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM site_content WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Contenu supprimé avec succès !';
            }
            
            // DUPLIQUER
            elseif ($action === 'duplicate') {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("SELECT * FROM site_content WHERE id = ?");
                $stmt->execute([$id]);
                $original = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($original) {
                    $newSlug = $original['slug'] . '-copie-' . time();
                    $insertStmt = $pdo->prepare("
                        INSERT INTO site_content (type, slug, titre, sous_titre, contenu, description, image_url, ordre, statut, meta_keywords)
                        VALUES (:type, :slug, :titre, :sous_titre, :contenu, :description, :image_url, :ordre, 'brouillon', :meta_keywords)
                    ");
                    $insertStmt->execute([
                        ':type' => $original['type'],
                        ':slug' => $newSlug,
                        ':titre' => $original['titre'] . ' (Copie)',
                        ':sous_titre' => $original['sous_titre'],
                        ':contenu' => $original['contenu'],
                        ':description' => $original['description'],
                        ':image_url' => $original['image_url'],
                        ':ordre' => $original['ordre'],
                        ':meta_keywords' => $original['meta_keywords']
                    ]);
                    $success = 'Contenu dupliqué avec succès !';
                }
            }
            
            // CHANGER STATUT RAPIDE
            elseif ($action === 'toggle_status') {
                $id = (int)$_POST['id'];
                $newStatus = $_POST['new_status'];
                $stmt = $pdo->prepare("UPDATE site_content SET statut = ? WHERE id = ?");
                $stmt->execute([$newStatus, $id]);
                $success = 'Statut modifié avec succès !';
            }
        }
    } catch (PDOException $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

// Récupérer les contenus
try {
    $pdo = getDBConnection();
    $filterType = isset($_GET['type']) ? $_GET['type'] : '';
    $filterStatut = isset($_GET['statut']) ? $_GET['statut'] : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'date_modification';
    $sortOrder = isset($_GET['order']) ? $_GET['order'] : 'DESC';
    
    $where = [];
    $params = [];
    
    if ($filterType) {
        $where[] = "type = :type";
        $params[':type'] = $filterType;
    }
    if ($filterStatut) {
        $where[] = "statut = :statut";
        $params[':statut'] = $filterStatut;
    }
    if ($search) {
        $where[] = "(titre LIKE :search1 OR sous_titre LIKE :search2 OR contenu LIKE :search3 OR description LIKE :search4)";
        $searchTerm = '%' . $search . '%';
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
        $params[':search4'] = $searchTerm;
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Validation du tri
    $allowedSorts = ['date_modification', 'date_creation', 'titre', 'ordre', 'type'];
    $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'date_modification';
    $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
    
    $stmt = $pdo->prepare("SELECT * FROM site_content $whereClause ORDER BY $sortBy $sortOrder");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $contents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer le contenu à éditer si demandé
    $editContent = null;
    if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
        $editStmt = $pdo->prepare("SELECT * FROM site_content WHERE id = ?");
        $editStmt->execute([(int)$_GET['edit']]);
        $editContent = $editStmt->fetch(PDO::FETCH_ASSOC);
        // Si le contenu n'existe pas, $editContent sera false, on le remet à null
        if ($editContent === false) {
            $editContent = null;
        }
    }
    
} catch (PDOException $e) {
    $error = "Erreur de connexion : " . $e->getMessage();
    $contents = [];
    $editContent = null;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion du Contenu - CREx</title>
    <link rel="stylesheet" href="admin-style.css">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">
    <script src="theme-init.js"></script>
    <script src="dark-mode.js"></script>
</head>
<body>
    <div class="admin-header">
        <div class="admin-header-content">
            <h1>📄 Administration CREx - Gestion du Contenu</h1>
            <div class="admin-nav-links">
                <button class="theme-toggle" aria-label="Basculer le thème" type="button">
                    <span class="theme-icon moon-icon">🌙</span>
                </button>
                <a href="admin.php" class="admin-nav-link">📧 Messages</a>
                <a href="admin-content.php" class="admin-nav-link active">📄 Contenu</a>
                <a href="admin-testimonials.php" class="admin-nav-link">💬 Témoignages</a>
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
            <button onclick="window.location.href='?edit=0'" class="btn-primary-admin">➕ Nouveau contenu</button>
            <form method="GET" style="flex: 1; display: flex; gap: 0.5rem; max-width: 400px;">
                <input type="text" name="search" placeholder="🔍 Rechercher..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; padding: 0.75rem; border: 2px solid #E3F6FD; border-radius: 8px;">
                <?php if ($filterType): ?>
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($filterType); ?>">
                <?php endif; ?>
                <?php if ($filterStatut): ?>
                    <input type="hidden" name="statut" value="<?php echo htmlspecialchars($filterStatut); ?>">
                <?php endif; ?>
                <button type="submit" class="btn-primary-admin" style="padding: 0.75rem 1rem;">Rechercher</button>
            </form>
            <select onchange="window.location.href='?type='+this.value<?php echo $filterStatut ? '&statut='.$filterStatut : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" style="padding: 0.75rem; border-radius: 8px;">
                <option value="">Tous les types</option>
                <option value="page" <?php echo $filterType === 'page' ? 'selected' : ''; ?>>Pages</option>
                <option value="section" <?php echo $filterType === 'section' ? 'selected' : ''; ?>>Sections</option>
                <option value="article" <?php echo $filterType === 'article' ? 'selected' : ''; ?>>Articles</option>
                <option value="service" <?php echo $filterType === 'service' ? 'selected' : ''; ?>>Services</option>
            </select>
            <select onchange="window.location.href='?statut='+this.value<?php echo $filterType ? '&type='.$filterType : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" style="padding: 0.75rem; border-radius: 8px;">
                <option value="">Tous les statuts</option>
                <option value="publie" <?php echo $filterStatut === 'publie' ? 'selected' : ''; ?>>Publié</option>
                <option value="brouillon" <?php echo $filterStatut === 'brouillon' ? 'selected' : ''; ?>>Brouillon</option>
                <option value="archive" <?php echo $filterStatut === 'archive' ? 'selected' : ''; ?>>Archivé</option>
            </select>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Ordre</th>
                        <th>Modifié</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contents)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 2rem;">Aucun contenu trouvé</td></tr>
                    <?php else: ?>
                        <?php foreach ($contents as $content): ?>
                            <tr>
                                <td><?php echo $content['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($content['titre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($content['type']); ?></td>
                                <td><span class="badge <?php echo $content['statut']; ?>"><?php echo ucfirst($content['statut']); ?></span></td>
                                <td><?php echo $content['ordre']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($content['date_modification'])); ?></td>
                                <td style="white-space: nowrap;">
                                    <a href="?edit=<?php echo $content['id']; ?>" class="btn-primary-admin" style="padding: 0.5rem 1rem; font-size: 0.85rem;">✏️ Modifier</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Dupliquer ce contenu ?');">
                                        <input type="hidden" name="action" value="duplicate">
                                        <input type="hidden" name="id" value="<?php echo $content['id']; ?>">
                                        <button type="submit" style="background: #17a2b8; color: white; padding: 0.5rem 1rem; border: none; border-radius: 8px; cursor: pointer; font-size: 0.85rem;">📋 Dupliquer</button>
                                    </form>
                                    <?php if ($content['statut'] === 'publie'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?php echo $content['id']; ?>">
                                            <input type="hidden" name="new_status" value="brouillon">
                                            <button type="submit" style="background: #ffc107; color: #333; padding: 0.5rem 1rem; border: none; border-radius: 8px; cursor: pointer; font-size: 0.85rem;">📝 Brouillon</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?php echo $content['id']; ?>">
                                            <input type="hidden" name="new_status" value="publie">
                                            <button type="submit" style="background: #28a745; color: white; padding: 0.5rem 1rem; border: none; border-radius: 8px; cursor: pointer; font-size: 0.85rem;">✅ Publier</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer ce contenu ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $content['id']; ?>">
                                        <button type="submit" style="background: #dc3545; color: white; padding: 0.5rem 1rem; border: none; border-radius: 8px; cursor: pointer; font-size: 0.85rem;">🗑️ Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de formulaire -->
    <div id="contentModal" class="form-modal <?php echo ($editContent || (isset($_GET['edit']) && $_GET['edit'] == '0')) ? 'active' : ''; ?>">
        <div class="form-modal-content">
            <h2><?php echo isset($editContent['id']) ? 'Modifier le contenu' : 'Nouveau contenu'; ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo isset($editContent['id']) ? 'update' : 'create'; ?>">
                <?php if (isset($editContent['id'])): ?>
                    <input type="hidden" name="id" value="<?php echo (int)$editContent['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Type *</label>
                    <select name="type" required>
                        <option value="page" <?php echo (isset($editContent['type']) && $editContent['type'] === 'page') ? 'selected' : ''; ?>>Page</option>
                        <option value="section" <?php echo (isset($editContent['type']) && $editContent['type'] === 'section') ? 'selected' : ''; ?>>Section</option>
                        <option value="article" <?php echo (isset($editContent['type']) && $editContent['type'] === 'article') ? 'selected' : ''; ?>>Article</option>
                        <option value="service" <?php echo (isset($editContent['type']) && $editContent['type'] === 'service') ? 'selected' : ''; ?>>Service</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Titre *</label>
                    <input type="text" name="titre" value="<?php echo isset($editContent['titre']) ? htmlspecialchars($editContent['titre']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Sous-titre</label>
                    <input type="text" name="sous_titre" value="<?php echo isset($editContent['sous_titre']) ? htmlspecialchars($editContent['sous_titre']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Contenu</label>
                    <textarea name="contenu"><?php echo isset($editContent['contenu']) ? htmlspecialchars($editContent['contenu']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"><?php echo isset($editContent['description']) ? htmlspecialchars($editContent['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>URL de l'image</label>
                    <input type="url" name="image_url" value="<?php echo isset($editContent['image_url']) ? htmlspecialchars($editContent['image_url']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Ordre</label>
                    <input type="number" name="ordre" value="<?php echo isset($editContent['ordre']) ? (int)$editContent['ordre'] : '0'; ?>">
                </div>
                
                <div class="form-group">
                    <label>Statut *</label>
                    <select name="statut" required>
                        <option value="brouillon" <?php echo (isset($editContent['statut']) && $editContent['statut'] === 'brouillon') ? 'selected' : ''; ?>>Brouillon</option>
                        <option value="publie" <?php echo (isset($editContent['statut']) && $editContent['statut'] === 'publie') ? 'selected' : ''; ?>>Publié</option>
                        <option value="archive" <?php echo (isset($editContent['statut']) && $editContent['statut'] === 'archive') ? 'selected' : ''; ?>>Archivé</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Mots-clés SEO</label>
                    <input type="text" name="meta_keywords" value="<?php echo isset($editContent['meta_keywords']) ? htmlspecialchars($editContent['meta_keywords']) : ''; ?>" placeholder="mot1, mot2, mot3">
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn-primary-admin">💾 Enregistrer</button>
                    <a href="admin-content.php" class="btn-primary-admin" style="background: #6c757d;">❌ Annuler</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(action) {
            if (action === 'create') {
                window.location.href = 'admin-content.php?edit=0';
            }
        }
        <?php if (isset($editContent) && $editContent): ?>
        document.getElementById('contentModal').classList.add('active');
        <?php endif; ?>
    </script>
</body>
</html>

