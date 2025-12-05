<?php
/**
 * Admin Panel Unifié - CREx
 * Combine Dashboard + Messages Management
 */

session_start();
require_once 'config.php';
require_once 'includes/permissions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

// Initialiser le token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Récupérer la section active
$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// Paramètres de pagination pour les messages
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Filtres pour les messages
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Actions pour les messages
    if (isset($_POST['message_id'])) {
        // Vérifier CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Erreur de sécurité. Veuillez réessayer.";
        } else {
            try {
                $pdo = getDBConnection();
                $messageId = (int)$_POST['message_id'];
                $action = $_POST['action'];
                
                if ($action === 'mark_read') {
                    $stmt = $pdo->prepare("UPDATE contact_messages SET lu = 1 WHERE id = ?");
                    $stmt->execute([$messageId]);
                    $success = "Message marqué comme lu";
                } elseif ($action === 'mark_unread') {
                    $stmt = $pdo->prepare("UPDATE contact_messages SET lu = 0 WHERE id = ?");
                    $stmt->execute([$messageId]);
                    $success = "Message marqué comme non lu";
                } elseif ($action === 'mark_replied') {
                    $stmt = $pdo->prepare("UPDATE contact_messages SET repondu = 1 WHERE id = ?");
                    $stmt->execute([$messageId]);
                    $success = "Message marqué comme répondu";
                } elseif ($action === 'delete') {
                    $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
                    $stmt->execute([$messageId]);
                    $success = "Message supprimé";
                }
                
                // Rediriger pour éviter la resoumission
                header('Location: admin.php?section=' . $section . '&success=' . urlencode($success ?? ''));
                exit;
            } catch (PDOException $e) {
                $error = "Erreur lors de l'opération : " . $e->getMessage();
            }
        }
    }
    
    // Actions pour les rendez-vous
    if (isset($_POST['appointment_id'])) {
        // Vérifier CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Erreur de sécurité. Veuillez réessayer.";
        } else {
            try {
                $pdo = getDBConnection();
                $appointmentId = (int)$_POST['appointment_id'];
                $action = $_POST['action'];
                
                // Ces actions (confirm, cancel, complete) sont maintenant gérées par send-appointment-response.php via le modal
                // Garder ce code pour la rétrocompatibilité si nécessaire
                if ($action === 'confirm' && !isset($_POST['send_method'])) {
                    $stmt = $pdo->prepare("UPDATE appointments SET statut = 'confirme', date_confirmation = NOW(), confirmed_by = ? WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id'] ?? null, $appointmentId]);
                    $success = "Rendez-vous confirmé";
                } elseif ($action === 'cancel' && !isset($_POST['send_method'])) {
                    $stmt = $pdo->prepare("UPDATE appointments SET statut = 'annule' WHERE id = ?");
                    $stmt->execute([$appointmentId]);
                    $success = "Rendez-vous annulé";
                } elseif ($action === 'complete' && !isset($_POST['send_method'])) {
                    $stmt = $pdo->prepare("UPDATE appointments SET statut = 'termine' WHERE id = ?");
                    $stmt->execute([$appointmentId]);
                    $success = "Rendez-vous marqué comme terminé";
                } elseif ($action === 'delete_appointment') {
                    $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
                    $stmt->execute([$appointmentId]);
                    $success = "Rendez-vous supprimé";
                } elseif ($action === 'update_status') {
                    $newStatus = $_POST['new_status'] ?? '';
                    if (in_array($newStatus, ['en_attente', 'confirme', 'annule', 'termine'])) {
                        $stmt = $pdo->prepare("UPDATE appointments SET statut = ? WHERE id = ?");
                        $stmt->execute([$newStatus, $appointmentId]);
                        if ($newStatus === 'confirme') {
                            $stmt = $pdo->prepare("UPDATE appointments SET date_confirmation = NOW(), confirmed_by = ? WHERE id = ?");
                            $stmt->execute([$_SESSION['user_id'] ?? null, $appointmentId]);
                        }
                        $success = "Statut du rendez-vous mis à jour";
                    }
                }
                
                header('Location: admin.php?section=appointments&success=' . urlencode($success ?? ''));
                exit;
            } catch (PDOException $e) {
                $error = "Erreur lors de l'opération : " . $e->getMessage();
            }
        }
    }
    
    // Actions pour la galerie
    if (isset($_POST['gallery_id']) || (isset($_POST['action']) && $_POST['action'] === 'delete_gallery')) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Erreur de sécurité. Veuillez réessayer.";
        } else {
            try {
                $pdo = getDBConnection();
                if (isset($_POST['gallery_id']) && $_POST['action'] === 'delete_gallery') {
                    $galleryId = (int)$_POST['gallery_id'];
                    // Récupérer le chemin de l'image avant suppression
                    $stmt = $pdo->prepare("SELECT image_path, thumbnail_path FROM gallery WHERE id = ?");
                    $stmt->execute([$galleryId]);
                    $image = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Supprimer de la base de données
                    $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
                    $stmt->execute([$galleryId]);
                    
                    // Supprimer les fichiers
                    if ($image && file_exists($image['image_path'])) {
                        @unlink($image['image_path']);
                    }
                    if ($image && $image['thumbnail_path'] && file_exists($image['thumbnail_path'])) {
                        @unlink($image['thumbnail_path']);
                    }
                    
                    $success = "Image supprimée avec succès";
                }
                header('Location: admin.php?section=gallery&success=' . urlencode($success ?? ''));
                exit;
            } catch (PDOException $e) {
                $error = "Erreur lors de l'opération : " . $e->getMessage();
            }
        }
    }
    
    // Actions pour les services
    if (isset($_POST['service_id']) || (isset($_POST['action']) && in_array($_POST['action'], ['add_service', 'update_service', 'delete_service']))) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Erreur de sécurité. Veuillez réessayer.";
        } else {
            try {
                $pdo = getDBConnection();
                $action = $_POST['action'];
                
                if ($action === 'delete_service' && isset($_POST['service_id'])) {
                    $serviceId = (int)$_POST['service_id'];
                    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
                    $stmt->execute([$serviceId]);
                    $success = "Service supprimé avec succès";
                } elseif (in_array($action, ['add_service', 'update_service'])) {
                    $title = trim($_POST['title'] ?? '');
                    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', $title), '-'));
                    $description = trim($_POST['description'] ?? '');
                    $fullDescription = trim($_POST['full_description'] ?? '');
                    $icon = trim($_POST['icon'] ?? '');
                    $imageUrl = trim($_POST['image_url'] ?? '');
                    $price = !empty($_POST['price']) ? (float)$_POST['price'] : null;
                    $duration = trim($_POST['duration'] ?? '');
                    $orderIndex = (int)($_POST['order_index'] ?? 0);
                    $active = isset($_POST['active']) ? 1 : 0;
                    $featured = isset($_POST['featured']) ? 1 : 0;
                    
                    if (empty($title)) {
                        $error = "Le titre est obligatoire";
                    } else {
                        if ($action === 'add_service') {
                            // Vérifier que le slug est unique
                            $checkStmt = $pdo->prepare("SELECT id FROM services WHERE slug = ?");
                            $checkStmt->execute([$slug]);
                            if ($checkStmt->fetch()) {
                                $slug .= '-' . time();
                            }
                            
                            $stmt = $pdo->prepare("INSERT INTO services (title, slug, description, full_description, icon, image_url, price, duration, order_index, active, featured, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$title, $slug, $description, $fullDescription, $icon, $imageUrl, $price, $duration, $orderIndex, $active, $featured, $_SESSION['user_id'] ?? null]);
                            $success = "Service ajouté avec succès";
                        } else {
                            $serviceId = (int)$_POST['service_id'];
                            $stmt = $pdo->prepare("UPDATE services SET title = ?, slug = ?, description = ?, full_description = ?, icon = ?, image_url = ?, price = ?, duration = ?, order_index = ?, active = ?, featured = ?, updated_by = ? WHERE id = ?");
                            $stmt->execute([$title, $slug, $description, $fullDescription, $icon, $imageUrl, $price, $duration, $orderIndex, $active, $featured, $_SESSION['user_id'] ?? null, $serviceId]);
                            $success = "Service mis à jour avec succès";
                        }
                    }
                }
                
                if (!isset($error)) {
                    header('Location: admin.php?section=services&success=' . urlencode($success ?? ''));
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Erreur lors de l'opération : " . $e->getMessage();
            }
        }
    }
    
    // Actions pour le blog
    if (isset($_POST['blog_id']) || (isset($_POST['action']) && in_array($_POST['action'], ['add_blog', 'update_blog', 'delete_blog']))) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Erreur de sécurité. Veuillez réessayer.";
        } else {
            try {
                $pdo = getDBConnection();
                $action = $_POST['action'];
                
                if ($action === 'delete_blog' && isset($_POST['blog_id'])) {
                    $blogId = (int)$_POST['blog_id'];
                    $stmt = $pdo->prepare("DELETE FROM blog WHERE id = ?");
                    $stmt->execute([$blogId]);
                    $success = "Article supprimé avec succès";
                } elseif (in_array($action, ['add_blog', 'update_blog'])) {
                    $title = trim($_POST['title'] ?? '');
                    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', $title), '-'));
                    $description = trim($_POST['description'] ?? '');
                    $content = trim($_POST['content'] ?? '');
                    $image = trim($_POST['image'] ?? '');
                    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
                    $status = in_array($_POST['status'] ?? 'draft', ['draft', 'published', 'archived']) ? $_POST['status'] : 'draft';
                    $featured = isset($_POST['featured']) ? 1 : 0;
                    $metaTitle = trim($_POST['meta_title'] ?? '');
                    $metaDescription = trim($_POST['meta_description'] ?? '');
                    
                    if (empty($title)) {
                        $error = "Le titre est obligatoire";
                    } else {
                        if ($action === 'add_blog') {
                            // Vérifier que le slug est unique
                            $checkStmt = $pdo->prepare("SELECT id FROM blog WHERE slug = ?");
                            $checkStmt->execute([$slug]);
                            if ($checkStmt->fetch()) {
                                $slug .= '-' . time();
                            }
                            
                            $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;
                            $stmt = $pdo->prepare("INSERT INTO blog (title, slug, description, content, image, category_id, status, featured, author_id, meta_title, meta_description, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$title, $slug, $description, $content, $image, $categoryId, $status, $featured, $_SESSION['user_id'] ?? null, $metaTitle, $metaDescription, $publishedAt]);
                            $success = "Article ajouté avec succès";
                        } else {
                            $blogId = (int)$_POST['blog_id'];
                            $publishedAt = null;
                            if ($status === 'published') {
                                // Vérifier si l'article était déjà publié
                                $checkStmt = $pdo->prepare("SELECT published_at FROM blog WHERE id = ?");
                                $checkStmt->execute([$blogId]);
                                $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
                                $publishedAt = $existing['published_at'] ?? date('Y-m-d H:i:s');
                            }
                            
                            $stmt = $pdo->prepare("UPDATE blog SET title = ?, slug = ?, description = ?, content = ?, image = ?, category_id = ?, status = ?, featured = ?, meta_title = ?, meta_description = ?, published_at = ? WHERE id = ?");
                            $stmt->execute([$title, $slug, $description, $content, $image, $categoryId, $status, $featured, $metaTitle, $metaDescription, $publishedAt, $blogId]);
                            $success = "Article mis à jour avec succès";
                        }
                    }
                }
                
                if (!isset($error)) {
                    header('Location: admin.php?section=blog&success=' . urlencode($success ?? ''));
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Erreur lors de l'opération : " . $e->getMessage();
            }
        }
    }
    
    // Actions pour les paramètres
    if (isset($_POST['action']) && $_POST['action'] === 'update_settings') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Erreur de sécurité. Veuillez réessayer.";
        } else {
            try {
                $pdo = getDBConnection();
                if (isset($_POST['settings']) && is_array($_POST['settings'])) {
                    foreach ($_POST['settings'] as $settingId => $settingValue) {
                        $stmt = $pdo->prepare("UPDATE site_settings SET valeur = ? WHERE id = ?");
                        $stmt->execute([$settingValue, (int)$settingId]);
                    }
                    $success = "Paramètres mis à jour avec succès";
                }
                header('Location: admin.php?section=settings&success=' . urlencode($success ?? ''));
                exit;
            } catch (PDOException $e) {
                $error = "Erreur lors de l'opération : " . $e->getMessage();
            }
        }
    }
    
    // Actions pour la gestion des admins
    if (isset($_POST['action']) && in_array($_POST['action'], ['create_admin', 'update_admin', 'delete_admin', 'toggle_admin_status'])) {
        if (!canManageAdmins()) {
            $error = "Vous n'avez pas la permission de gérer les administrateurs.";
        } elseif (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Erreur de sécurité. Veuillez réessayer.";
        } else {
            try {
                $pdo = getDBConnection();
                $currentUserId = $_SESSION['user_id'];
                
                if ($_POST['action'] === 'create_admin') {
                    $username = trim($_POST['username'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $nom_complet = trim($_POST['nom_complet'] ?? '');
                    $password = $_POST['password'] ?? '';
                    $role = $_POST['role'] ?? 'admin';
                    
                    // Validation
                    if (empty($username) || empty($email) || empty($password)) {
                        $error = "Tous les champs obligatoires doivent être remplis.";
                    } elseif (strlen($password) < 6) {
                        $error = "Le mot de passe doit contenir au moins 6 caractères.";
                    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $error = "L'adresse email n'est pas valide.";
                    } else {
                        // Vérifier si le username ou l'email existe déjà
                        $checkStmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
                        $checkStmt->execute([$username, $email]);
                        if ($checkStmt->fetch()) {
                            $error = "Ce nom d'utilisateur ou cet email existe déjà.";
                        } else {
                            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("INSERT INTO admin_users (username, email, nom_complet, password_hash, role, cree_par) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$username, $email, $nom_complet, $passwordHash, $role, $currentUserId]);
                            $success = "Administrateur créé avec succès.";
                        }
                    }
                } elseif ($_POST['action'] === 'update_admin') {
                    $adminId = (int)$_POST['admin_id'];
                    $email = trim($_POST['email'] ?? '');
                    $nom_complet = trim($_POST['nom_complet'] ?? '');
                    $role = $_POST['role'] ?? 'admin';
                    $password = $_POST['password'] ?? '';
                    
                    // Vérifier que l'admin existe et n'est pas le super_admin actuel (sauf si on est super_admin)
                    $checkStmt = $pdo->prepare("SELECT role FROM admin_users WHERE id = ?");
                    $checkStmt->execute([$adminId]);
                    $existingAdmin = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$existingAdmin) {
                        $error = "Administrateur introuvable.";
                    } elseif ($existingAdmin['role'] === 'super_admin' && $_SESSION['user_role'] !== 'super_admin') {
                        $error = "Vous ne pouvez pas modifier un super administrateur.";
                    } else {
                        // Vérifier si l'email est déjà utilisé par un autre admin
                        $emailCheck = $pdo->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
                        $emailCheck->execute([$email, $adminId]);
                        if ($emailCheck->fetch()) {
                            $error = "Cet email est déjà utilisé par un autre administrateur.";
                        } else {
                            if (!empty($password)) {
                                if (strlen($password) < 6) {
                                    $error = "Le mot de passe doit contenir au moins 6 caractères.";
                                } else {
                                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                                    $stmt = $pdo->prepare("UPDATE admin_users SET email = ?, nom_complet = ?, role = ?, password_hash = ? WHERE id = ?");
                                    $stmt->execute([$email, $nom_complet, $role, $passwordHash, $adminId]);
                                    $success = "Administrateur mis à jour avec succès.";
                                }
                            } else {
                                $stmt = $pdo->prepare("UPDATE admin_users SET email = ?, nom_complet = ?, role = ? WHERE id = ?");
                                $stmt->execute([$email, $nom_complet, $role, $adminId]);
                                $success = "Administrateur mis à jour avec succès.";
                            }
                        }
                    }
                } elseif ($_POST['action'] === 'delete_admin') {
                    $adminId = (int)$_POST['admin_id'];
                    
                    // Ne pas permettre de supprimer son propre compte ou un super_admin
                    if ($adminId == $currentUserId) {
                        $error = "Vous ne pouvez pas supprimer votre propre compte.";
                    } else {
                        $checkStmt = $pdo->prepare("SELECT role FROM admin_users WHERE id = ?");
                        $checkStmt->execute([$adminId]);
                        $admin = $checkStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$admin) {
                            $error = "Administrateur introuvable.";
                        } elseif ($admin['role'] === 'super_admin' && $_SESSION['user_role'] !== 'super_admin') {
                            $error = "Vous ne pouvez pas supprimer un super administrateur.";
                        } else {
                            $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = ?");
                            $stmt->execute([$adminId]);
                            $success = "Administrateur supprimé avec succès.";
                        }
                    }
                } elseif ($_POST['action'] === 'toggle_admin_status') {
                    $adminId = (int)$_POST['admin_id'];
                    
                    // Ne pas permettre de désactiver son propre compte
                    if ($adminId == $currentUserId) {
                        $error = "Vous ne pouvez pas désactiver votre propre compte.";
                    } else {
                        $checkStmt = $pdo->prepare("SELECT actif, role FROM admin_users WHERE id = ?");
                        $checkStmt->execute([$adminId]);
                        $admin = $checkStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$admin) {
                            $error = "Administrateur introuvable.";
                        } elseif ($admin['role'] === 'super_admin' && $_SESSION['user_role'] !== 'super_admin') {
                            $error = "Vous ne pouvez pas modifier un super administrateur.";
                        } else {
                            $newStatus = $admin['actif'] ? 0 : 1;
                            $stmt = $pdo->prepare("UPDATE admin_users SET actif = ? WHERE id = ?");
                            $stmt->execute([$newStatus, $adminId]);
                            $success = $newStatus ? "Administrateur activé avec succès." : "Administrateur désactivé avec succès.";
                        }
                    }
                }
                
                if (isset($success) || isset($error)) {
                    header('Location: admin.php?section=admins&' . (isset($success) ? 'success=' . urlencode($success) : 'error=' . urlencode($error)));
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Erreur lors de l'opération : " . $e->getMessage();
            }
        }
    }
}

// Gérer les actions GET pour les paramètres (création/réinitialisation)
if (isset($_GET['action']) && $_GET['action'] === 'create_defaults') {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: admin-login.php');
        exit;
    }
    
    try {
        $pdo = getDBConnection();
        $defaultSettings = [
            ['site_titre', 'CREx - Centre de Réadaptation et d\'Excellence', 'text', 'Titre principal du site', 'general'],
            ['site_description', 'Centre de réadaptation et d\'excellence offrant des services de kinésithérapie, réadaptation et accompagnement psychologique', 'text', 'Description du site', 'general'],
            ['site_language', 'fr', 'text', 'Langue par défaut du site', 'general'],
            ['contact_email', 'crex.bdi@gmail.com', 'text', 'Email de contact principal', 'contact'],
            ['contact_telephone_1', '+257 77 510 647', 'text', 'Premier numéro de téléphone', 'contact'],
            ['contact_telephone_2', '+257 61 343 682', 'text', 'Deuxième numéro de téléphone', 'contact'],
            ['contact_whatsapp', '+257 77 510 647', 'text', 'Numéro WhatsApp', 'contact'],
            ['contact_adresse', 'Kinindo Ouest, Avenue Beraka N°30 — Bujumbura, Burundi', 'text', 'Adresse complète', 'contact'],
            ['contact_latitude', '-3.404847', 'text', 'Latitude GPS', 'contact'],
            ['contact_longitude', '29.349306', 'text', 'Longitude GPS', 'contact'],
            ['horaires_lundi_vendredi', '08h00 – 19h00', 'text', 'Horaires du lundi au vendredi', 'contact'],
            ['horaires_samedi', '09h00 – 14h00', 'text', 'Horaires du samedi', 'contact'],
            ['horaires_dimanche', 'Fermé', 'text', 'Horaires du dimanche', 'contact'],
            ['site_facebook', 'https://facebook.com', 'text', 'Lien Facebook', 'social'],
            ['site_linkedin', 'https://linkedin.com', 'text', 'Lien LinkedIn', 'social'],
            ['site_whatsapp_link', 'https://wa.me/25777510647', 'text', 'Lien WhatsApp', 'social'],
            ['site_instagram', '', 'text', 'Lien Instagram', 'social'],
            ['site_twitter', '', 'text', 'Lien Twitter', 'social'],
            ['google_maps_api_key', '', 'text', 'Clé API Google Maps (optionnel)', 'seo'],
            ['recaptcha_site_key', '', 'text', 'Clé site reCAPTCHA', 'security'],
            ['recaptcha_secret_key', '', 'text', 'Clé secrète reCAPTCHA', 'security'],
            ['maintenance_mode', '0', 'boolean', 'Mode maintenance (0 = non, 1 = oui)', 'general'],
            ['max_login_attempts', '5', 'number', 'Nombre maximum de tentatives de connexion', 'security'],
            ['login_lockout_time', '300', 'number', 'Temps de verrouillage après échecs (secondes)', 'security'],
            ['session_timeout', '3600', 'number', 'Timeout de session en secondes', 'security'],
            ['items_per_page', '10', 'number', 'Nombre d\'éléments par page', 'general'],
            ['enable_comments', '1', 'boolean', 'Activer les commentaires sur le blog', 'blog'],
            ['enable_ratings', '1', 'boolean', 'Activer le système de notation', 'general'],
            ['enable_newsletter', '1', 'boolean', 'Activer la newsletter', 'newsletter'],
            ['enable_analytics', '1', 'boolean', 'Activer les statistiques', 'analytics'],
            ['email_notification', '1', 'boolean', 'Activer les notifications par email', 'notifications'],
            ['sms_notification', '0', 'boolean', 'Activer les notifications SMS', 'notifications'],
            ['admin_email', 'crex.bdi@gmail.com', 'text', 'Email de l\'administrateur', 'admin'],
            ['backup_enabled', '1', 'boolean', 'Activer les sauvegardes automatiques', 'backup'],
            ['backup_frequency', 'daily', 'text', 'Fréquence des sauvegardes (daily, weekly, monthly)', 'backup']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO site_settings (cle, valeur, type, description, categorie) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE valeur = valeur");
        $count = 0;
        foreach ($defaultSettings as $setting) {
            $stmt->execute($setting);
            $count++;
        }
        $success = "$count paramètres par défaut créés avec succès";
        header('Location: admin.php?section=settings&success=' . urlencode($success));
        exit;
    } catch (PDOException $e) {
        $error = "Erreur lors de la création des paramètres: " . $e->getMessage();
        header('Location: admin.php?section=settings&error=' . urlencode($error));
        exit;
    }
}

// Récupérer les statistiques pour le dashboard
$stats = [];
try {
    $pdo = getDBConnection();
    
    // Messages
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contact_messages");
    $stats['messages'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contact_messages WHERE lu = 0");
    $stats['messages_unread'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Rendez-vous
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments");
        $stats['appointments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments WHERE statut = 'en_attente'");
        $stats['appointments_pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (PDOException $e) {
        $stats['appointments'] = 0;
        $stats['appointments_pending'] = 0;
    }
    
    // Galerie
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM gallery WHERE active = 1");
        $stats['gallery_images'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (PDOException $e) {
        $stats['gallery_images'] = 0;
    }
    
    // Services
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM services WHERE active = 1");
        $stats['services'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (PDOException $e) {
        $stats['services'] = 0;
    }
    
    // Blog
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM blog WHERE status = 'published'");
        $stats['blog_posts'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (PDOException $e) {
        $stats['blog_posts'] = 0;
    }
    
    // Visiteurs (30 derniers jours)
    try {
        $stmt = $pdo->query("SELECT COUNT(DISTINCT ip_address) as total FROM visitor_stats WHERE visit_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['visitors_30d'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (PDOException $e) {
        $stats['visitors_30d'] = 0;
    }
    
} catch (PDOException $e) {
    $error = "Erreur de connexion à la base de données : " . $e->getMessage();
    $stats = array_fill_keys(['messages', 'messages_unread', 'appointments', 'appointments_pending', 'gallery_images', 'services', 'blog_posts', 'visitors_30d'], 0);
}

// Récupérer les messages si on est dans la section messages
$messages = [];
$totalMessages = 0;
$totalPages = 0;

// Récupérer les rendez-vous si on est dans la section appointments
$appointments = [];
$totalAppointments = 0;
$totalAppointmentPages = 0;
$appointmentFilter = isset($_GET['appointment_filter']) ? $_GET['appointment_filter'] : 'all';
$appointmentSearch = isset($_GET['appointment_search']) ? trim($_GET['appointment_search']) : '';

if ($section === 'messages') {
    try {
        $whereConditions = [];
        $params = [];
        
        if ($filter === 'unread') {
            $whereConditions[] = "lu = 0";
        } elseif ($filter === 'unreplied') {
            $whereConditions[] = "repondu = 0";
        }
        
        if (!empty($search)) {
            $whereConditions[] = "(nom LIKE :search1 OR email LIKE :search2 OR sujet LIKE :search3 OR message LIKE :search4)";
            $searchTerm = '%' . $search . '%';
            $params[':search1'] = $searchTerm;
            $params[':search2'] = $searchTerm;
            $params[':search3'] = $searchTerm;
            $params[':search4'] = $searchTerm;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Compter le total
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages $whereClause");
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $totalMessages = $countStmt->fetchColumn();
        $totalPages = ceil($totalMessages / $perPage);
        
        // Récupérer les messages
        $messagesStmt = $pdo->prepare("
            SELECT * FROM contact_messages 
            $whereClause
            ORDER BY date_creation DESC 
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $key => $value) {
            $messagesStmt->bindValue($key, $value);
        }
        $messagesStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $messagesStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $messagesStmt->execute();
        $messages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = "Erreur lors de la récupération des messages : " . $e->getMessage();
    }
} elseif ($section === 'appointments') {
    try {
        $whereConditions = [];
        $params = [];
        
        if ($appointmentFilter === 'pending') {
            $whereConditions[] = "statut = 'en_attente'";
        } elseif ($appointmentFilter === 'confirmed') {
            $whereConditions[] = "statut = 'confirme'";
        } elseif ($appointmentFilter === 'cancelled') {
            $whereConditions[] = "statut = 'annule'";
        } elseif ($appointmentFilter === 'completed') {
            $whereConditions[] = "statut = 'termine'";
        }
        
        if (!empty($appointmentSearch)) {
            $whereConditions[] = "(nom LIKE :search1 OR email LIKE :search2 OR telephone LIKE :search3 OR service_type LIKE :search4)";
            $searchTerm = '%' . $appointmentSearch . '%';
            $params[':search1'] = $searchTerm;
            $params[':search2'] = $searchTerm;
            $params[':search3'] = $searchTerm;
            $params[':search4'] = $searchTerm;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Compter le total
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM appointments $whereClause");
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $totalAppointments = $countStmt->fetchColumn();
        $totalAppointmentPages = ceil($totalAppointments / $perPage);
        
        // Récupérer les rendez-vous
        $appointmentsStmt = $pdo->prepare("
            SELECT * FROM appointments 
            $whereClause
            ORDER BY date_souhaitee DESC, date_creation DESC 
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $key => $value) {
            $appointmentsStmt->bindValue($key, $value);
        }
        $appointmentsStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $appointmentsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $appointmentsStmt->execute();
        $appointments = $appointmentsStmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = "Erreur lors de la récupération des rendez-vous : " . $e->getMessage();
    }
}

// Récupérer les dernières activités pour le dashboard
$recentActivities = [];
if ($section === 'dashboard') {
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
        // Si la table n'existe pas, essayer security_logs
        try {
            $stmt = $pdo->query("
                SELECT sl.*, au.username 
                FROM security_logs sl
                LEFT JOIN admin_users au ON sl.admin_id = au.id
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
}

// Vérifier les permissions pour les sections protégées AVANT d'envoyer du HTML
if ($section === 'database') {
    // Tous les admins peuvent voir la base de données (lecture seule)
    if (!canViewDatabase()) {
        header('Location: admin.php?error=' . urlencode('Vous n\'avez pas la permission d\'accéder à cette page.'));
        exit;
    }
} elseif ($section === 'admins') {
    requirePermission('manage_admins');
}

// Définir le titre de la page
$pageTitles = [
    'dashboard' => 'Dashboard - Administration CREx',
    'messages' => 'Messages - Administration CREx',
    'appointments' => 'Rendez-vous - Administration CREx',
    'database' => 'Base de données - Administration CREx'
];
$pageTitle = $pageTitles[$section] ?? ucfirst($section) . ' - Administration CREx';

// Inclure le header
include 'includes/header.php';
?>

<!-- Sidebar -->
<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<main class="admin-main">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($section === 'dashboard'): ?>
        <!-- ============================================
             DASHBOARD SECTION
             ============================================ -->
        
        <!-- Statistics Cards -->
        <div class="admin-stats-grid" data-aos="fade-up">
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
                    <i class="fas fa-concierge-bell"></i>
                </div>
                <div class="admin-stat-content">
                    <h3><?php echo number_format($stats['services'] ?? 0); ?></h3>
                    <p>Services actifs</p>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity & Messages -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="admin-card" data-aos="fade-right">
                    <div class="admin-card-header">
                        <h2><i class="fas fa-history"></i> Activité récente</h2>
                    </div>
                    <div class="admin-card-body">
                        <?php if (empty($recentActivities)): ?>
                            <p class="empty-state">Aucune activité récente</p>
                        <?php else: ?>
                            <div class="admin-activity-list">
                                <?php foreach ($recentActivities as $activity): ?>
                                    <div class="admin-activity-item">
                                        <div class="admin-activity-icon">
                                            <i class="fas fa-circle"></i>
                                        </div>
                                        <div class="admin-activity-content">
                                            <p>
                                                <strong><?php echo htmlspecialchars($activity['username'] ?? 'Système'); ?></strong> 
                                                <?php echo htmlspecialchars($activity['action'] ?? $activity['type'] ?? ''); ?>
                                            </p>
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
            </div>
            
            <div class="col-lg-6">
                <div class="admin-card" data-aos="fade-left">
                    <div class="admin-card-header">
                        <h2><i class="fas fa-inbox"></i> Derniers messages</h2>
                        <a href="admin.php?section=messages" class="btn btn-sm btn-primary">Voir tout</a>
                    </div>
                    <div class="admin-card-body">
                        <?php if (empty($recentMessages ?? [])): ?>
                            <p class="empty-state">Aucun message</p>
                        <?php else: ?>
                            <div class="admin-messages-list">
                                <?php foreach ($recentMessages as $message): ?>
                                    <div class="admin-message-item <?php echo ($message['lu'] == 0) ? 'unread' : ''; ?>">
                                        <div class="admin-message-header">
                                            <strong><?php echo htmlspecialchars($message['nom']); ?></strong>
                                            <?php if ($message['lu'] == 0): ?>
                                                <span class="badge bg-primary">Nouveau</span>
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
        </div>
        
        <!-- Quick Actions -->
        <div class="admin-card" data-aos="fade-up">
            <div class="admin-card-header">
                <h2><i class="fas fa-bolt"></i> Actions rapides</h2>
            </div>
            <div class="admin-card-body">
                <div class="admin-quick-actions">
                    <a href="admin.php?section=messages" class="admin-quick-action-btn">
                        <i class="fas fa-inbox"></i>
                        <span>Messages</span>
                    </a>
                    <a href="admin.php?section=gallery" class="admin-quick-action-btn">
                        <i class="fas fa-images"></i>
                        <span>Galerie</span>
                    </a>
                    <a href="admin.php?section=services" class="admin-quick-action-btn">
                        <i class="fas fa-concierge-bell"></i>
                        <span>Services</span>
                    </a>
                    <a href="admin.php?section=blog" class="admin-quick-action-btn">
                        <i class="fas fa-blog"></i>
                        <span>Blog</span>
                    </a>
                    <a href="admin.php?section=settings" class="admin-quick-action-btn">
                        <i class="fas fa-cog"></i>
                        <span>Paramètres</span>
                    </a>
                </div>
            </div>
        </div>

    <?php elseif ($section === 'messages'): ?>
        <!-- ============================================
             MESSAGES SECTION
             ============================================ -->
        
        <div class="admin-card" data-aos="fade-up">
            <div class="admin-card-header">
                <h2><i class="fas fa-inbox"></i> Gestion des messages</h2>
            </div>
            <div class="admin-card-body">
                <!-- Search & Filters -->
                <form method="GET" action="" class="admin-search-box mb-3">
                    <input type="hidden" name="section" value="messages">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" 
                               name="search" 
                               id="messageSearch"
                               class="form-control" 
                               placeholder="Rechercher (nom, email, sujet, message)..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">Rechercher</button>
                    </div>
                </form>
                
                <div class="admin-filters mb-3">
                    <a href="?section=messages&filter=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>"
                       data-filter="all">
                        Tous
                    </a>
                    <a href="?section=messages&filter=unread<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="filter-btn <?php echo $filter === 'unread' ? 'active' : ''; ?>"
                       data-filter="unread">
                        Non lus (<?php echo $stats['messages_unread']; ?>)
                    </a>
                    <a href="?section=messages&filter=unreplied<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="filter-btn <?php echo $filter === 'unreplied' ? 'active' : ''; ?>"
                       data-filter="unreplied">
                        Non répondu
                    </a>
                </div>
                
                <!-- Messages List -->
                <div id="messagesContainer">
                    <?php if (empty($messages)): ?>
                        <div class="empty-state">
                            <h3>Aucun message trouvé</h3>
                            <p>
                                <?php if (!empty($search) || $filter !== 'all'): ?>
                                    Aucun message ne correspond à vos critères de recherche.
                                <?php else: ?>
                                    Les messages envoyés depuis le formulaire de contact apparaîtront ici.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message-item <?php echo $message['lu'] == 0 ? 'unread' : ''; ?>" data-message-id="<?php echo $message['id']; ?>">
                                <div class="message-header">
                                    <div class="message-info">
                                        <h3 class="message-name"><?php echo htmlspecialchars($message['nom']); ?></h3>
                                        <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="message-email">
                                            <?php echo htmlspecialchars($message['email']); ?>
                                        </a>
                                        <?php if (!empty($message['telephone'])): ?>
                                            <div class="message-contact mt-2">
                                                <i class="fas fa-phone"></i> 
                                                <a href="tel:<?php echo htmlspecialchars($message['telephone']); ?>">
                                                    <?php echo htmlspecialchars($message['telephone']); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($message['whatsapp'])): ?>
                                            <div class="message-contact mt-2">
                                                <i class="fab fa-whatsapp"></i> 
                                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', htmlspecialchars($message['whatsapp'])); ?>" target="_blank">
                                                    <?php echo htmlspecialchars($message['whatsapp']); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <div class="message-date mt-2">
                                            <i class="fas fa-calendar"></i> 
                                            <?php echo date('d/m/Y à H:i:s', strtotime($message['date_creation'])); ?>
                                            <?php if ($message['ip_address']): ?>
                                                • IP: <?php echo htmlspecialchars($message['ip_address']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="message-badges">
                                        <?php if ($message['lu'] == 0): ?>
                                            <span class="badge unread">Non lu</span>
                                        <?php endif; ?>
                                        <?php if ($message['repondu'] == 1): ?>
                                            <span class="badge replied">Répondu</span>
                                        <?php else: ?>
                                            <span class="badge not-replied">Non répondu</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($message['sujet'])): ?>
                                    <div class="message-subject mt-2">
                                        <strong><i class="fas fa-tag"></i> Objet:</strong> <?php echo htmlspecialchars($message['sujet']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="message-text mt-3">
                                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                </div>
                                
                                <div class="message-actions mt-3">
                                    <?php if ($message['lu'] == 0): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                            <input type="hidden" name="action" value="mark_read">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="fas fa-check"></i> Marquer comme lu
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                            <input type="hidden" name="action" value="mark_unread">
                                            <button type="submit" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-envelope"></i> Marquer comme non lu
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($message['repondu'] == 0): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                            <input type="hidden" name="action" value="mark_replied">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fas fa-check-double"></i> Marquer comme répondu
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>?subject=Re: <?php echo urlencode($message['sujet'] ?: 'Votre message'); ?>" 
                                       class="btn btn-sm btn-primary" target="_blank">
                                        <i class="fas fa-envelope"></i> Répondre
                                    </a>
                                    
                                    <?php if (!empty($message['telephone'])): ?>
                                        <a href="tel:<?php echo htmlspecialchars($message['telephone']); ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-phone"></i> Appeler
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($message['whatsapp'])): ?>
                                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', htmlspecialchars($message['whatsapp'])); ?>" 
                                           class="btn btn-sm btn-success" target="_blank">
                                            <i class="fab fa-whatsapp"></i> WhatsApp
                                        </a>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination mt-4" id="paginationContainer">
                        <?php if ($page > 1): ?>
                            <a href="?section=messages&page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>">
                                « Précédent
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?section=messages&page=<?php echo $i; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?section=messages&page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>">
                                Suivant »
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($section === 'appointments'): ?>
        <!-- ============================================
             APPOINTMENTS SECTION
             ============================================ -->
        
        <div class="admin-card" data-aos="fade-up">
            <div class="admin-card-header">
                <h2><i class="fas fa-calendar-check"></i> Gestion des rendez-vous</h2>
            </div>
            <div class="admin-card-body">
                <!-- Search & Filters -->
                <form method="GET" action="" class="admin-search-box mb-3">
                    <input type="hidden" name="section" value="appointments">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" 
                               name="appointment_search" 
                               class="form-control" 
                               placeholder="Rechercher (nom, email, téléphone, service)..." 
                               value="<?php echo htmlspecialchars($appointmentSearch); ?>">
                        <button class="btn btn-primary" type="submit">Rechercher</button>
                    </div>
                </form>
                
                <div class="admin-filters mb-3">
                    <a href="?section=appointments&appointment_filter=all<?php echo !empty($appointmentSearch) ? '&appointment_search=' . urlencode($appointmentSearch) : ''; ?>" 
                       class="filter-btn <?php echo $appointmentFilter === 'all' ? 'active' : ''; ?>">
                        Tous
                    </a>
                    <a href="?section=appointments&appointment_filter=pending<?php echo !empty($appointmentSearch) ? '&appointment_search=' . urlencode($appointmentSearch) : ''; ?>" 
                       class="filter-btn <?php echo $appointmentFilter === 'pending' ? 'active' : ''; ?>">
                        En attente (<?php echo $stats['appointments_pending'] ?? 0; ?>)
                    </a>
                    <a href="?section=appointments&appointment_filter=confirmed<?php echo !empty($appointmentSearch) ? '&appointment_search=' . urlencode($appointmentSearch) : ''; ?>" 
                       class="filter-btn <?php echo $appointmentFilter === 'confirmed' ? 'active' : ''; ?>">
                        Confirmés
                    </a>
                    <a href="?section=appointments&appointment_filter=cancelled<?php echo !empty($appointmentSearch) ? '&appointment_search=' . urlencode($appointmentSearch) : ''; ?>" 
                       class="filter-btn <?php echo $appointmentFilter === 'cancelled' ? 'active' : ''; ?>">
                        Annulés
                    </a>
                    <a href="?section=appointments&appointment_filter=completed<?php echo !empty($appointmentSearch) ? '&appointment_search=' . urlencode($appointmentSearch) : ''; ?>" 
                       class="filter-btn <?php echo $appointmentFilter === 'completed' ? 'active' : ''; ?>">
                        Terminés
                    </a>
                </div>
                
                <!-- Appointments List -->
                <div id="appointmentsContainer">
                    <?php if (empty($appointments)): ?>
                        <div class="empty-state">
                            <h3>Aucun rendez-vous trouvé</h3>
                            <p>
                                <?php if (!empty($appointmentSearch) || $appointmentFilter !== 'all'): ?>
                                    Aucun rendez-vous ne correspond à vos critères de recherche.
                                <?php else: ?>
                                    Les demandes de rendez-vous apparaîtront ici.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <?php 
                        $serviceLabels = [
                            'consultation_medicale' => 'Consultation médicale',
                            'kinesitherapie' => 'Kinésithérapie',
                            'readaptation' => 'Réadaptation',
                            'psychologie' => 'Psychologie clinique',
                            'orthopedie' => 'Orthopédie et rééducation',
                            'ergotherapie' => 'Ergothérapie',
                            'developpement_enfant' => 'Développement de l\'enfant',
                            'therapies_groupe' => 'Thérapies de groupe',
                            'personnes_agees' => 'Accompagnement des personnes âgées',
                            'autre' => 'Autre'
                        ];
                        
                        $genreLabels = [
                            'homme' => 'Homme',
                            'femme' => 'Femme',
                            'autre' => 'Autre',
                            'non_specifie' => 'Non spécifié'
                        ];
                        
                        $statusLabels = [
                            'en_attente' => ['label' => 'En attente', 'class' => 'warning'],
                            'confirme' => ['label' => 'Confirmé', 'class' => 'success'],
                            'annule' => ['label' => 'Annulé', 'class' => 'danger'],
                            'termine' => ['label' => 'Terminé', 'class' => 'info']
                        ];
                        
                        foreach ($appointments as $appointment): 
                            $statusInfo = $statusLabels[$appointment['statut']] ?? ['label' => $appointment['statut'], 'class' => 'secondary'];
                        ?>
                            <div class="message-item <?php echo $appointment['statut'] === 'en_attente' ? 'unread' : ''; ?>" data-appointment-id="<?php echo $appointment['id']; ?>">
                                <div class="message-header">
                                    <div class="message-info">
                                        <h3 class="message-name">
                                            <?php echo htmlspecialchars($appointment['nom']); ?>
                                            <span class="badge bg-<?php echo $statusInfo['class']; ?> ms-2"><?php echo $statusInfo['label']; ?></span>
                                        </h3>
                                        <div class="message-email">
                                            <i class="fas fa-envelope me-2"></i>
                                            <a href="mailto:<?php echo htmlspecialchars($appointment['email']); ?>">
                                                <?php echo htmlspecialchars($appointment['email']); ?>
                                            </a>
                                        </div>
                                        <div class="message-contact mt-2">
                                            <i class="fas fa-phone me-2"></i>
                                            <a href="tel:<?php echo htmlspecialchars($appointment['telephone']); ?>">
                                                <?php echo htmlspecialchars($appointment['telephone']); ?>
                                            </a>
                                        </div>
                                        <?php if (!empty($appointment['adresse_complete'])): ?>
                                            <div class="message-date mt-2">
                                                <i class="fas fa-map-marker-alt me-2"></i>
                                                <strong>Adresse:</strong> 
                                                <?php echo htmlspecialchars($appointment['adresse_complete']); ?>
                                                <?php if (!empty($appointment['ville'])): ?>
                                                    , <?php echo htmlspecialchars($appointment['ville']); ?>
                                                <?php endif; ?>
                                                <?php if (!empty($appointment['pays'])): ?>
                                                    , <?php echo htmlspecialchars($appointment['pays']); ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($appointment['date_naissance'])): ?>
                                            <div class="message-date mt-2">
                                                <i class="fas fa-birthday-cake me-2"></i>
                                                <strong>Date de naissance:</strong> <?php echo date('d/m/Y', strtotime($appointment['date_naissance'])); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($appointment['nationalite'])): ?>
                                            <div class="message-date mt-2">
                                                <i class="fas fa-flag me-2"></i>
                                                <strong>Nationalité:</strong> <?php echo htmlspecialchars($appointment['nationalite']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($appointment['profession'])): ?>
                                            <div class="message-date mt-2">
                                                <i class="fas fa-briefcase me-2"></i>
                                                <strong>Profession:</strong> <?php echo htmlspecialchars($appointment['profession']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="message-date mt-2">
                                            <i class="fas fa-calendar me-2"></i>
                                            <strong>Date souhaitée:</strong> <?php echo date('d/m/Y', strtotime($appointment['date_souhaitee'])); ?>
                                            à <?php echo date('H:i', strtotime($appointment['heure_souhaitee'])); ?>
                                        </div>
                                        <div class="message-date mt-2">
                                            <i class="fas fa-stethoscope me-2"></i>
                                            <strong>Service:</strong> <?php echo htmlspecialchars($serviceLabels[$appointment['service_type']] ?? $appointment['service_type']); ?>
                                        </div>
                                        <?php if ($appointment['date_confirmation']): ?>
                                            <div class="message-date mt-2 text-success">
                                                <i class="fas fa-check-circle me-2"></i>
                                                Confirmé le <?php echo date('d/m/Y à H:i', strtotime($appointment['date_confirmation'])); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="message-date mt-2">
                                            <i class="fas fa-clock me-2"></i>
                                            Demande créée le <?php echo date('d/m/Y à H:i', strtotime($appointment['date_creation'])); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($appointment['motif_consultation'])): ?>
                                    <div class="message-text mt-3">
                                        <strong><i class="fas fa-clipboard-list me-2"></i>Motif de consultation:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($appointment['motif_consultation'])); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($appointment['message'])): ?>
                                    <div class="message-text mt-3">
                                        <strong><i class="fas fa-comment me-2"></i>Message/Commentaires:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($appointment['message'])); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="message-actions mt-3">
                                    <?php if ($appointment['statut'] === 'en_attente'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#responseModal"
                                                onclick="openResponseModal(<?php echo $appointment['id']; ?>, 'confirm', '<?php echo htmlspecialchars($appointment['nom'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($appointment['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($appointment['telephone'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($serviceLabels[$appointment['service_type']] ?? $appointment['service_type'], ENT_QUOTES); ?>', '<?php echo date('d/m/Y', strtotime($appointment['date_souhaitee'])); ?>', '<?php echo $appointment['heure_souhaitee']; ?>')">
                                            <i class="fas fa-check"></i> Confirmer et répondre
                                        </button>
                                        
                                        <button type="button" 
                                                class="btn btn-sm btn-warning" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#responseModal"
                                                onclick="openResponseModal(<?php echo $appointment['id']; ?>, 'cancel', '<?php echo htmlspecialchars($appointment['nom'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($appointment['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($appointment['telephone'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($serviceLabels[$appointment['service_type']] ?? $appointment['service_type'], ENT_QUOTES); ?>', '<?php echo date('d/m/Y', strtotime($appointment['date_souhaitee'])); ?>', '<?php echo $appointment['heure_souhaitee']; ?>')">
                                            <i class="fas fa-times"></i> Annuler et répondre
                                        </button>
                                    <?php elseif ($appointment['statut'] === 'confirme'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#responseModal"
                                                onclick="openResponseModal(<?php echo $appointment['id']; ?>, 'complete', '<?php echo htmlspecialchars($appointment['nom'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($appointment['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($appointment['telephone'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($serviceLabels[$appointment['service_type']] ?? $appointment['service_type'], ENT_QUOTES); ?>', '<?php echo date('d/m/Y', strtotime($appointment['date_souhaitee'])); ?>', '<?php echo $appointment['heure_souhaitee']; ?>')">
                                            <i class="fas fa-check-double"></i> Marquer terminé et répondre
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button type="button" 
                                            class="btn btn-sm btn-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#responseModal"
                                            onclick="openResponseModal(<?php echo $appointment['id']; ?>, 'reply', '<?php echo htmlspecialchars($appointment['nom'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($appointment['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($appointment['telephone'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($serviceLabels[$appointment['service_type']] ?? $appointment['service_type'], ENT_QUOTES); ?>', '<?php echo date('d/m/Y', strtotime($appointment['date_souhaitee'])); ?>', '<?php echo $appointment['heure_souhaitee']; ?>')">
                                        <i class="fas fa-reply"></i> Répondre
                                    </button>
                                    
                                    <div class="dropdown d-inline">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-edit"></i> Changer le statut
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="new_status" value="en_attente">
                                                    <button type="submit" class="dropdown-item">En attente</button>
                                                </form>
                                            </li>
                                            <li>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="new_status" value="confirme">
                                                    <button type="submit" class="dropdown-item">Confirmé</button>
                                                </form>
                                            </li>
                                            <li>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="new_status" value="annule">
                                                    <button type="submit" class="dropdown-item">Annulé</button>
                                                </form>
                                            </li>
                                            <li>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="new_status" value="termine">
                                                    <button type="submit" class="dropdown-item">Terminé</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                    
                                    <a href="mailto:<?php echo htmlspecialchars($appointment['email']); ?>?subject=Rendez-vous CREx" 
                                       class="btn btn-sm btn-primary" target="_blank">
                                        <i class="fas fa-envelope"></i> Email
                                    </a>
                                    
                                    <a href="tel:<?php echo htmlspecialchars($appointment['telephone']); ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-phone"></i> Appeler
                                    </a>
                                    
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $appointment['telephone']); ?>" 
                                       class="btn btn-sm btn-success" target="_blank">
                                        <i class="fab fa-whatsapp"></i> WhatsApp
                                    </a>
                                    
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce rendez-vous ?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                        <input type="hidden" name="action" value="delete_appointment">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalAppointmentPages > 1): ?>
                    <div class="pagination mt-4">
                        <?php if ($page > 1): ?>
                            <a href="?section=appointments&page=<?php echo $page - 1; ?>&appointment_filter=<?php echo $appointmentFilter; ?>&appointment_search=<?php echo urlencode($appointmentSearch); ?>">
                                « Précédent
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalAppointmentPages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?section=appointments&page=<?php echo $i; ?>&appointment_filter=<?php echo $appointmentFilter; ?>&appointment_search=<?php echo urlencode($appointmentSearch); ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalAppointmentPages): ?>
                            <a href="?section=appointments&page=<?php echo $page + 1; ?>&appointment_filter=<?php echo $appointmentFilter; ?>&appointment_search=<?php echo urlencode($appointmentSearch); ?>">
                                Suivant »
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($section === 'gallery'): ?>
        <!-- ============================================
             GALLERY SECTION
             ============================================ -->
        <?php
        // Récupérer les images de la galerie
        try {
            $pdo = getDBConnection();
            $galleryQuery = "SELECT * FROM gallery ORDER BY order_index ASC, uploaded_at DESC";
            $galleryStmt = $pdo->query($galleryQuery);
            $galleryItems = $galleryStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $galleryItems = [];
            $error = "Erreur lors du chargement de la galerie: " . $e->getMessage();
        }
        ?>
        
        <div class="admin-card" data-aos="fade-up">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-images"></i> Gestion de la galerie</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadImageModal">
                    <i class="fas fa-upload"></i> Ajouter une image
                </button>
            </div>
            <div class="admin-card-body">
                <?php if (empty($galleryItems)): ?>
                    <div class="empty-state">
                        <i class="fas fa-images fa-3x mb-3 text-muted"></i>
                        <h3>Aucune image dans la galerie</h3>
                        <p>Commencez par ajouter votre première image.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($galleryItems as $item): ?>
                            <div class="col-md-4 col-lg-3">
                                <div class="card gallery-item-card">
                                    <div class="position-relative">
                                        <img src="<?php echo htmlspecialchars($item['image_path']); ?>" 
                                             class="card-img-top" 
                                             alt="<?php echo htmlspecialchars($item['alt_text'] ?? $item['title'] ?? 'Image'); ?>"
                                             style="height: 200px; object-fit: cover;">
                                        <div class="position-absolute top-0 end-0 p-2">
                                            <?php if ($item['active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($item['title'] ?? 'Sans titre'); ?></h6>
                                        <?php if ($item['category']): ?>
                                            <span class="badge bg-info mb-2"><?php echo htmlspecialchars($item['category']); ?></span>
                                        <?php endif; ?>
                                        <div class="d-flex gap-2 mt-2">
                                            <button type="button" class="btn btn-sm btn-primary" onclick="editGalleryItem(<?php echo $item['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette image ?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="gallery_id" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="action" value="delete_gallery">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($section === 'services'): ?>
        <!-- ============================================
             SERVICES SECTION
             ============================================ -->
        <?php
        // Récupérer les services
        try {
            $pdo = getDBConnection();
            $servicesQuery = "SELECT * FROM services ORDER BY order_index ASC, last_updated DESC";
            $servicesStmt = $pdo->query($servicesQuery);
            $services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $services = [];
            $error = "Erreur lors du chargement des services: " . $e->getMessage();
        }
        ?>
        
        <div class="admin-card" data-aos="fade-up">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-concierge-bell"></i> Gestion des services</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#serviceModal" onclick="openServiceModal()">
                    <i class="fas fa-plus"></i> Ajouter un service
                </button>
            </div>
            <div class="admin-card-body">
                <?php if (empty($services)): ?>
                    <div class="empty-state">
                        <i class="fas fa-concierge-bell fa-3x mb-3 text-muted"></i>
                        <h3>Aucun service</h3>
                        <p>Commencez par ajouter votre premier service.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Titre</th>
                                    <th>Description</th>
                                    <th>Prix</th>
                                    <th>Ordre</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($service['title']); ?></strong>
                                            <?php if ($service['featured']): ?>
                                                <span class="badge bg-warning ms-2">Mis en avant</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($service['description'] ?? '', 0, 100)) . '...'; ?></td>
                                        <td><?php echo $service['price'] ? number_format($service['price'], 0, ',', ' ') . ' FBU' : '-'; ?></td>
                                        <td><?php echo $service['order_index']; ?></td>
                                        <td>
                                            <?php if ($service['active']): ?>
                                                <span class="badge bg-success">Actif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="openServiceModal(<?php echo $service['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce service ?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                                <input type="hidden" name="action" value="delete_service">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($section === 'blog'): ?>
        <!-- ============================================
             BLOG SECTION
             ============================================ -->
        <?php
        // Récupérer les articles de blog
        try {
            $pdo = getDBConnection();
            $blogQuery = "SELECT b.*, bc.name as category_name 
                         FROM blog b 
                         LEFT JOIN blog_categories bc ON b.category_id = bc.id 
                         ORDER BY b.created_at DESC";
            $blogStmt = $pdo->query($blogQuery);
            $blogPosts = $blogStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupérer les catégories
            $categoriesQuery = "SELECT * FROM blog_categories WHERE active = 1 ORDER BY name";
            $categoriesStmt = $pdo->query($categoriesQuery);
            $blogCategories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $blogPosts = [];
            $blogCategories = [];
            $error = "Erreur lors du chargement du blog: " . $e->getMessage();
        }
        ?>
        
        <div class="admin-card" data-aos="fade-up">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-blog"></i> Gestion du blog</h2>
                <div>
                    <button type="button" class="btn btn-secondary me-2" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="openCategoryModal()">
                        <i class="fas fa-folder"></i> Catégories
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#blogModal" onclick="openBlogModal()">
                        <i class="fas fa-plus"></i> Nouvel article
                    </button>
                </div>
            </div>
            <div class="admin-card-body">
                <?php if (empty($blogPosts)): ?>
                    <div class="empty-state">
                        <i class="fas fa-blog fa-3x mb-3 text-muted"></i>
                        <h3>Aucun article</h3>
                        <p>Commencez par créer votre premier article de blog.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Titre</th>
                                    <th>Catégorie</th>
                                    <th>Statut</th>
                                    <th>Vues</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($blogPosts as $post): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                                            <?php if ($post['featured']): ?>
                                                <span class="badge bg-warning ms-2">Mis en avant</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($post['category_name'] ?? 'Sans catégorie'); ?></td>
                                        <td>
                                            <?php
                                            $statusLabels = ['draft' => 'Brouillon', 'published' => 'Publié', 'archived' => 'Archivé'];
                                            $statusColors = ['draft' => 'secondary', 'published' => 'success', 'archived' => 'warning'];
                                            $status = $post['status'];
                                            ?>
                                            <span class="badge bg-<?php echo $statusColors[$status] ?? 'secondary'; ?>">
                                                <?php echo $statusLabels[$status] ?? $status; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $post['views']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($post['created_at'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="openBlogModal(<?php echo $post['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet article ?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="blog_id" value="<?php echo $post['id']; ?>">
                                                <input type="hidden" name="action" value="delete_blog">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($section === 'settings'): ?>
        <!-- ============================================
             SETTINGS SECTION
             ============================================ -->
        <?php
        // Initialiser les paramètres par défaut s'ils n'existent pas
        try {
            $pdo = getDBConnection();
            
            // Liste complète des paramètres par défaut
            $defaultSettings = [
                ['site_titre', 'CREx - Centre de Réadaptation et d\'Excellence', 'text', 'Titre principal du site', 'general'],
                ['site_description', 'Centre de réadaptation et d\'excellence offrant des services de kinésithérapie, réadaptation et accompagnement psychologique', 'text', 'Description du site', 'general'],
                ['site_language', 'fr', 'text', 'Langue par défaut du site', 'general'],
                ['dark_mode_enabled', '1', 'boolean', 'Activer le mode sombre sur le site public', 'general'],
                ['dark_mode_default', 'auto', 'text', 'Mode sombre par défaut (auto = détection système, light = clair, dark = sombre)', 'general'],
                ['contact_email', 'crex.bdi@gmail.com', 'text', 'Email de contact principal', 'contact'],
                ['contact_telephone_1', '+257 77 510 647', 'text', 'Premier numéro de téléphone', 'contact'],
                ['contact_telephone_2', '+257 61 343 682', 'text', 'Deuxième numéro de téléphone', 'contact'],
                ['contact_whatsapp', '+257 77 510 647', 'text', 'Numéro WhatsApp', 'contact'],
                ['contact_adresse', 'Kinindo Ouest, Avenue Beraka N°30 — Bujumbura, Burundi', 'text', 'Adresse complète', 'contact'],
                ['contact_latitude', '-3.404847', 'text', 'Latitude GPS', 'contact'],
                ['contact_longitude', '29.349306', 'text', 'Longitude GPS', 'contact'],
                ['horaires_lundi_vendredi', '08h00 – 19h00', 'text', 'Horaires du lundi au vendredi', 'contact'],
                ['horaires_samedi', '09h00 – 14h00', 'text', 'Horaires du samedi', 'contact'],
                ['horaires_dimanche', 'Fermé', 'text', 'Horaires du dimanche', 'contact'],
                ['site_facebook', 'https://facebook.com', 'text', 'Lien Facebook', 'social'],
                ['site_linkedin', 'https://linkedin.com', 'text', 'Lien LinkedIn', 'social'],
                ['site_whatsapp_link', 'https://wa.me/25777510647', 'text', 'Lien WhatsApp', 'social'],
                ['site_instagram', '', 'text', 'Lien Instagram', 'social'],
                ['site_twitter', '', 'text', 'Lien Twitter', 'social'],
                ['google_maps_api_key', '', 'text', 'Clé API Google Maps (optionnel)', 'seo'],
                ['recaptcha_site_key', '', 'text', 'Clé site reCAPTCHA', 'security'],
                ['recaptcha_secret_key', '', 'text', 'Clé secrète reCAPTCHA', 'security'],
                ['maintenance_mode', '0', 'boolean', 'Mode maintenance (0 = non, 1 = oui)', 'general'],
                ['max_login_attempts', '5', 'number', 'Nombre maximum de tentatives de connexion', 'security'],
                ['login_lockout_time', '300', 'number', 'Temps de verrouillage après échecs (secondes)', 'security'],
                ['session_timeout', '3600', 'number', 'Timeout de session en secondes', 'security'],
                ['items_per_page', '10', 'number', 'Nombre d\'éléments par page', 'general'],
                ['enable_comments', '1', 'boolean', 'Activer les commentaires sur le blog', 'blog'],
                ['enable_ratings', '1', 'boolean', 'Activer le système de notation', 'general'],
                ['enable_newsletter', '1', 'boolean', 'Activer la newsletter', 'newsletter'],
                ['enable_analytics', '1', 'boolean', 'Activer les statistiques', 'analytics'],
                ['email_notification', '1', 'boolean', 'Activer les notifications par email', 'notifications'],
                ['sms_notification', '0', 'boolean', 'Activer les notifications SMS', 'notifications'],
                ['admin_email', 'crex.bdi@gmail.com', 'text', 'Email de l\'administrateur', 'admin'],
                ['backup_enabled', '1', 'boolean', 'Activer les sauvegardes automatiques', 'backup'],
                ['backup_frequency', 'daily', 'text', 'Fréquence des sauvegardes (daily, weekly, monthly)', 'backup']
            ];
            
            // Récupérer les clés existantes
            $existingKeysStmt = $pdo->query("SELECT cle FROM site_settings");
            $existingKeys = [];
            while ($row = $existingKeysStmt->fetch(PDO::FETCH_ASSOC)) {
                $existingKeys[] = $row['cle'];
            }
            
            // Insérer les paramètres manquants
            $stmt = $pdo->prepare("INSERT INTO site_settings (cle, valeur, type, description, categorie) VALUES (?, ?, ?, ?, ?)");
            $insertedCount = 0;
            foreach ($defaultSettings as $setting) {
                if (!in_array($setting[0], $existingKeys)) {
                    $stmt->execute($setting);
                    $insertedCount++;
                }
            }
            
            if ($insertedCount > 0) {
                $success = "$insertedCount paramètre(s) manquant(s) ajouté(s) avec succès";
            }
            
            // Récupérer les paramètres
            $settingsQuery = "SELECT * FROM site_settings ORDER BY categorie, cle";
            $settingsStmt = $pdo->query($settingsQuery);
            $settings = $settingsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organiser les paramètres par catégorie
            $settingsGrouped = [];
            $categoryLabels = [
                'general' => 'Général',
                'contact' => 'Contact',
                'social' => 'Réseaux sociaux',
                'seo' => 'SEO',
                'security' => 'Sécurité',
                'admin' => 'Administration',
                'blog' => 'Blog',
                'newsletter' => 'Newsletter',
                'analytics' => 'Analytics',
                'notifications' => 'Notifications',
                'backup' => 'Sauvegardes'
            ];
            
            foreach ($settings as $setting) {
                $category = $setting['categorie'] ?? 'general';
                if (!isset($settingsGrouped[$category])) {
                    $settingsGrouped[$category] = [];
                }
                $settingsGrouped[$category][] = $setting;
            }
        } catch (PDOException $e) {
            $settingsGrouped = [];
            $error = "Erreur lors du chargement des paramètres: " . $e->getMessage();
        }
        ?>
        
        <div class="admin-card" data-aos="fade-up">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-cog"></i> Paramètres du site</h2>
                <button type="button" class="btn btn-secondary btn-sm" onclick="if(confirm('Êtes-vous sûr de vouloir réinitialiser tous les paramètres aux valeurs par défaut ?')) location.href='admin.php?section=settings&action=reset_defaults';">
                    <i class="fas fa-undo"></i> Réinitialiser
                </button>
            </div>
            <div class="admin-card-body">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars(urldecode($_GET['success'])); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($success) && !isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="settingsForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <?php if (empty($settingsGrouped)): ?>
                        <div class="empty-state">
                            <i class="fas fa-cog fa-3x mb-3 text-muted"></i>
                            <h3>Aucun paramètre configuré</h3>
                            <p>Les paramètres seront créés automatiquement lors de leur première utilisation.</p>
                            <a href="admin.php?section=settings&action=create_defaults" class="btn btn-primary mt-3">
                                <i class="fas fa-plus"></i> Créer les paramètres par défaut
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($settingsGrouped as $category => $groupSettings): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-<?php 
                                            echo $category === 'contact' ? 'address-book' : 
                                                ($category === 'social' ? 'share-alt' : 
                                                ($category === 'security' ? 'shield-alt' : 
                                                ($category === 'admin' ? 'user-shield' : 
                                                ($category === 'blog' ? 'blog' : 
                                                ($category === 'newsletter' ? 'envelope' : 
                                                ($category === 'analytics' ? 'chart-line' : 
                                                ($category === 'notifications' ? 'bell' : 
                                                ($category === 'backup' ? 'database' : 'cog')))))))); 
                                        ?> me-2"></i>
                                        <?php echo htmlspecialchars($categoryLabels[$category] ?? ucfirst($category)); ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($groupSettings as $setting): ?>
                                        <div class="mb-3">
                                            <label for="setting_<?php echo $setting['id']; ?>" class="form-label">
                                                <strong><?php echo htmlspecialchars($setting['cle']); ?></strong>
                                            </label>
                                            <?php if ($setting['type'] === 'textarea'): ?>
                                                <textarea class="form-control" 
                                                          id="setting_<?php echo $setting['id']; ?>" 
                                                          name="settings[<?php echo $setting['id']; ?>]"
                                                          rows="4"><?php echo htmlspecialchars($setting['valeur'] ?? ''); ?></textarea>
                                            <?php elseif ($setting['type'] === 'boolean'): ?>
                                                <select class="form-select" 
                                                        id="setting_<?php echo $setting['id']; ?>" 
                                                        name="settings[<?php echo $setting['id']; ?>]">
                                                    <option value="1" <?php echo ($setting['valeur'] ?? '0') == '1' ? 'selected' : ''; ?>>Oui</option>
                                                    <option value="0" <?php echo ($setting['valeur'] ?? '0') == '0' ? 'selected' : ''; ?>>Non</option>
                                                </select>
                                            <?php elseif ($setting['type'] === 'number'): ?>
                                                <input type="number" 
                                                       class="form-control" 
                                                       id="setting_<?php echo $setting['id']; ?>" 
                                                       name="settings[<?php echo $setting['id']; ?>]"
                                                       value="<?php echo htmlspecialchars($setting['valeur'] ?? ''); ?>">
                                            <?php elseif ($setting['cle'] === 'dark_mode_default'): ?>
                                                <select class="form-select" 
                                                        id="setting_<?php echo $setting['id']; ?>" 
                                                        name="settings[<?php echo $setting['id']; ?>]">
                                                    <option value="auto" <?php echo ($setting['valeur'] ?? 'auto') == 'auto' ? 'selected' : ''; ?>>Auto (Détection système)</option>
                                                    <option value="light" <?php echo ($setting['valeur'] ?? '') == 'light' ? 'selected' : ''; ?>>Clair</option>
                                                    <option value="dark" <?php echo ($setting['valeur'] ?? '') == 'dark' ? 'selected' : ''; ?>>Sombre</option>
                                                </select>
                                            <?php else: ?>
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="setting_<?php echo $setting['id']; ?>" 
                                                       name="settings[<?php echo $setting['id']; ?>]"
                                                       value="<?php echo htmlspecialchars($setting['valeur'] ?? ''); ?>">
                                            <?php endif; ?>
                                            <?php if (!empty($setting['description'])): ?>
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($setting['description']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Enregistrer tous les paramètres
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

    <?php elseif ($section === 'database'): ?>
        <!-- ============================================
             DATABASE SECTION - Interface graphique de la base de données
             ============================================ -->
        <?php
        // Permission déjà vérifiée avant l'inclusion du header
        
        // Récupérer la table sélectionnée
        $selectedTable = isset($_GET['table']) ? $_GET['table'] : null;
        $tablePage = isset($_GET['table_page']) ? (int)$_GET['table_page'] : 1;
        $tablePerPage = isset($_GET['table_per_page']) ? (int)$_GET['table_per_page'] : 20;
        $tableSearch = isset($_GET['table_search']) ? trim($_GET['table_search']) : '';
        
        try {
            $pdo = getDBConnection();
            
            // Récupérer toutes les tables
            $tablesStmt = $pdo->query("SHOW TABLES");
            $allTables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Statistiques pour chaque table
            $tableStats = [];
            foreach ($allTables as $table) {
                try {
                    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM `$table`");
                    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                    
                    // Récupérer la structure de la table
                    $structureStmt = $pdo->query("DESCRIBE `$table`");
                    $structure = $structureStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $tableStats[$table] = [
                        'count' => $count,
                        'columns' => count($structure),
                        'structure' => $structure
                    ];
                } catch (PDOException $e) {
                    $tableStats[$table] = [
                        'count' => 0,
                        'columns' => 0,
                        'structure' => [],
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // Si une table est sélectionnée, récupérer ses données
            $tableData = [];
            $tableColumns = [];
            $totalTableRows = 0;
            $totalTablePages = 0;
            
            if ($selectedTable && in_array($selectedTable, $allTables)) {
                // Récupérer les colonnes
                $columnsStmt = $pdo->query("DESCRIBE `$selectedTable`");
                $tableColumns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
                $columnNames = array_column($tableColumns, 'Field');
                
                // Construire la requête avec recherche
                $whereClause = '';
                $params = [];
                if (!empty($tableSearch)) {
                    $searchConditions = [];
                    foreach ($columnNames as $col) {
                        $searchConditions[] = "`$col` LIKE :search_$col";
                        $params[":search_$col"] = '%' . $tableSearch . '%';
                    }
                    $whereClause = 'WHERE ' . implode(' OR ', $searchConditions);
                }
                
                // Compter le total
                $countQuery = "SELECT COUNT(*) FROM `$selectedTable` $whereClause";
                $countStmt = $pdo->prepare($countQuery);
                foreach ($params as $key => $value) {
                    $countStmt->bindValue($key, $value);
                }
                $countStmt->execute();
                $totalTableRows = $countStmt->fetchColumn();
                $totalTablePages = ceil($totalTableRows / $tablePerPage);
                
                // Récupérer les données avec pagination
                $offset = ($tablePage - 1) * $tablePerPage;
                $dataQuery = "SELECT * FROM `$selectedTable` $whereClause ORDER BY 1 DESC LIMIT :limit OFFSET :offset";
                $dataStmt = $pdo->prepare($dataQuery);
                foreach ($params as $key => $value) {
                    $dataStmt->bindValue($key, $value);
                }
                $dataStmt->bindValue(':limit', $tablePerPage, PDO::PARAM_INT);
                $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $dataStmt->execute();
                $tableData = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
        } catch (PDOException $e) {
            $allTables = [];
            $tableStats = [];
            $error = "Erreur de connexion à la base de données : " . $e->getMessage();
        }
        ?>
        
        <div class="row g-4">
            <!-- Liste des tables -->
            <div class="col-lg-4">
                <div class="admin-card" data-aos="fade-right">
                    <div class="admin-card-header">
                        <h2><i class="fas fa-database"></i> Tables de la base de données</h2>
                        <span class="badge bg-primary"><?php echo count($allTables); ?> table(s)</span>
                    </div>
                    <div class="admin-card-body">
                        <div class="list-group">
                            <?php foreach ($allTables as $table): ?>
                                <a href="?section=database&table=<?php echo urlencode($table); ?>" 
                                   class="list-group-item list-group-item-action <?php echo $selectedTable === $table ? 'active' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">
                                                <i class="fas fa-table me-2"></i>
                                                <?php echo htmlspecialchars($table); ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo $tableStats[$table]['columns'] ?? 0; ?> colonne(s)
                                            </small>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">
                                            <?php echo number_format($tableStats[$table]['count'] ?? 0); ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Données de la table sélectionnée -->
            <div class="col-lg-8">
                <?php if ($selectedTable): ?>
                    <div class="admin-card" data-aos="fade-left">
                        <div class="admin-card-header d-flex justify-content-between align-items-center">
                            <h2>
                                <i class="fas fa-table"></i> 
                                Table: <code><?php echo htmlspecialchars($selectedTable); ?></code>
                            </h2>
                            <div>
                                <span class="badge bg-info me-2">
                                    <?php echo number_format($totalTableRows); ?> ligne(s)
                                </span>
                                <span class="badge bg-secondary">
                                    <?php echo count($tableColumns); ?> colonne(s)
                                </span>
                            </div>
                        </div>
                        <div class="admin-card-body">
                            <!-- Recherche dans la table -->
                            <form method="GET" class="mb-3">
                                <input type="hidden" name="section" value="database">
                                <input type="hidden" name="table" value="<?php echo htmlspecialchars($selectedTable); ?>">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" 
                                           name="table_search" 
                                           class="form-control" 
                                           placeholder="Rechercher dans cette table..." 
                                           value="<?php echo htmlspecialchars($tableSearch); ?>">
                                    <button class="btn btn-primary" type="submit">Rechercher</button>
                                    <?php if (!empty($tableSearch)): ?>
                                        <a href="?section=database&table=<?php echo urlencode($selectedTable); ?>" 
                                           class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Effacer
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                            
                            <!-- Structure de la table -->
                            <div class="mb-3">
                                <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#tableStructure">
                                    <i class="fas fa-info-circle"></i> Voir la structure
                                </button>
                                <div class="collapse mt-2" id="tableStructure">
                                    <div class="card card-body">
                                        <h6>Structure de la table</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Colonne</th>
                                                        <th>Type</th>
                                                        <th>Null</th>
                                                        <th>Clé</th>
                                                        <th>Défaut</th>
                                                        <th>Extra</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($tableColumns as $col): ?>
                                                        <tr>
                                                            <td><strong><?php echo htmlspecialchars($col['Field']); ?></strong></td>
                                                            <td><?php echo htmlspecialchars($col['Type']); ?></td>
                                                            <td><?php echo $col['Null'] === 'YES' ? '<span class="badge bg-warning">Oui</span>' : '<span class="badge bg-success">Non</span>'; ?></td>
                                                            <td>
                                                                <?php 
                                                                if ($col['Key'] === 'PRI') echo '<span class="badge bg-danger">PRIMARY</span>';
                                                                elseif ($col['Key'] === 'UNI') echo '<span class="badge bg-info">UNIQUE</span>';
                                                                elseif ($col['Key'] === 'MUL') echo '<span class="badge bg-secondary">INDEX</span>';
                                                                ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($col['Default'] ?? 'NULL'); ?></td>
                                                            <td><?php echo htmlspecialchars($col['Extra']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Données de la table -->
                            <?php if (empty($tableData)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-database fa-3x mb-3 text-muted"></i>
                                    <h3>Aucune donnée trouvée</h3>
                                    <p>
                                        <?php if (!empty($tableSearch)): ?>
                                            Aucun résultat pour "<?php echo htmlspecialchars($tableSearch); ?>"
                                        <?php else: ?>
                                            Cette table est vide.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover table-sm">
                                        <thead class="table-dark">
                                            <tr>
                                                <?php foreach ($tableColumns as $col): ?>
                                                    <th><?php echo htmlspecialchars($col['Field']); ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($tableData as $row): ?>
                                                <tr>
                                                    <?php foreach ($tableColumns as $col): ?>
                                                        <td>
                                                            <?php 
                                                            $value = $row[$col['Field']] ?? null;
                                                            if ($value === null) {
                                                                echo '<span class="text-muted fst-italic">NULL</span>';
                                                            } elseif (strlen($value) > 100) {
                                                                echo '<span title="' . htmlspecialchars($value) . '">' . htmlspecialchars(substr($value, 0, 100)) . '...</span>';
                                                            } else {
                                                                echo htmlspecialchars($value);
                                                            }
                                                            ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($totalTablePages > 1): ?>
                                    <nav aria-label="Pagination">
                                        <ul class="pagination justify-content-center mt-3">
                                            <li class="page-item <?php echo $tablePage <= 1 ? 'disabled' : ''; ?>">
                                                <a class="page-link" 
                                                   href="?section=database&table=<?php echo urlencode($selectedTable); ?>&table_page=<?php echo $tablePage - 1; ?><?php echo !empty($tableSearch) ? '&table_search=' . urlencode($tableSearch) : ''; ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                            <?php for ($i = max(1, $tablePage - 2); $i <= min($totalTablePages, $tablePage + 2); $i++): ?>
                                                <li class="page-item <?php echo $i === $tablePage ? 'active' : ''; ?>">
                                                    <a class="page-link" 
                                                       href="?section=database&table=<?php echo urlencode($selectedTable); ?>&table_page=<?php echo $i; ?><?php echo !empty($tableSearch) ? '&table_search=' . urlencode($tableSearch) : ''; ?>">
                                                        <?php echo $i; ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>
                                            <li class="page-item <?php echo $tablePage >= $totalTablePages ? 'disabled' : ''; ?>">
                                                <a class="page-link" 
                                                   href="?section=database&table=<?php echo urlencode($selectedTable); ?>&table_page=<?php echo $tablePage + 1; ?><?php echo !empty($tableSearch) ? '&table_search=' . urlencode($tableSearch) : ''; ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                    <div class="text-center text-muted">
                                        Page <?php echo $tablePage; ?> sur <?php echo $totalTablePages; ?> 
                                        (<?php echo number_format($totalTableRows); ?> ligne(s) au total)
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="admin-card" data-aos="fade-left">
                        <div class="admin-card-body text-center">
                            <i class="fas fa-database fa-4x text-muted mb-3"></i>
                            <h3>Sélectionnez une table</h3>
                            <p class="text-muted">Choisissez une table dans la liste à gauche pour voir ses données.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($section === 'admins'): ?>
        <!-- ============================================
             ADMINS MANAGEMENT SECTION
             ============================================ -->
        <?php
        // Permission déjà vérifiée avant l'inclusion du header
        
        try {
            $pdo = getDBConnection();
            $adminsQuery = "SELECT a.*, creator.username as created_by_username 
                           FROM admin_users a 
                           LEFT JOIN admin_users creator ON a.cree_par = creator.id 
                           ORDER BY a.date_creation DESC";
            $adminsStmt = $pdo->query($adminsQuery);
            $admins = $adminsStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $admins = [];
            $error = "Erreur lors du chargement des administrateurs: " . $e->getMessage();
        }
        
        $roleLabels = [
            'super_admin' => 'Super Administrateur',
            'admin' => 'Administrateur',
            'editor' => 'Éditeur',
            'moderator' => 'Modérateur'
        ];
        ?>
        
        <div class="admin-card" data-aos="fade-up">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-users-cog"></i> Gestion des Administrateurs</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adminModal" onclick="openAdminModal(null)">
                    <i class="fas fa-plus"></i> Nouveau Admin
                </button>
            </div>
            <div class="admin-card-body">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars(urldecode($_GET['success'])); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                    </div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom d'utilisateur</th>
                                <th>Email</th>
                                <th>Nom complet</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th>Dernière connexion</th>
                                <th>Créé par</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($admins)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Aucun administrateur trouvé.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($admin['id']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($admin['username']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['nom_complet'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $admin['role'] === 'super_admin' ? 'danger' : 
                                                    ($admin['role'] === 'admin' ? 'primary' : 
                                                    ($admin['role'] === 'editor' ? 'info' : 'secondary')); 
                                            ?>">
                                                <?php echo htmlspecialchars($roleLabels[$admin['role']] ?? $admin['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $admin['actif'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $admin['actif'] ? 'Actif' : 'Inactif'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $admin['derniere_connexion'] ? date('d/m/Y H:i', strtotime($admin['derniere_connexion'])) : 'Jamais'; ?></td>
                                        <td><?php echo htmlspecialchars($admin['created_by_username'] ?? '-'); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-primary" onclick="openAdminModal(<?php echo htmlspecialchars(json_encode($admin)); ?>)" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir <?php echo $admin['actif'] ? 'désactiver' : 'activer'; ?> cet administrateur ?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="action" value="toggle_admin_status">
                                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                    <button type="submit" class="btn btn-<?php echo $admin['actif'] ? 'warning' : 'success'; ?>" title="<?php echo $admin['actif'] ? 'Désactiver' : 'Activer'; ?>">
                                                        <i class="fas fa-<?php echo $admin['actif'] ? 'ban' : 'check'; ?>"></i>
                                                    </button>
                                                </form>
                                                <?php if ($admin['id'] != $_SESSION['user_id'] && ($admin['role'] !== 'super_admin' || $_SESSION['user_role'] === 'super_admin')): ?>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet administrateur ? Cette action est irréversible.');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <input type="hidden" name="action" value="delete_admin">
                                                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                        <button type="submit" class="btn btn-danger" title="Supprimer">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Modal pour créer/éditer un admin -->
        <div class="modal fade" id="adminModal" tabindex="-1" aria-labelledby="adminModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="adminModalLabel">Nouvel Administrateur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <form method="POST" id="adminForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" id="adminAction" value="create_admin">
                        <input type="hidden" name="admin_id" id="adminId" value="">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="adminUsername" class="form-label">Nom d'utilisateur *</label>
                                <input type="text" class="form-control" id="adminUsername" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="adminEmail" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="adminEmail" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="adminNomComplet" class="form-label">Nom complet</label>
                                <input type="text" class="form-control" id="adminNomComplet" name="nom_complet">
                            </div>
                            <div class="mb-3">
                                <label for="adminPassword" class="form-label">
                                    Mot de passe <span id="passwordRequired">*</span>
                                    <small class="text-muted" id="passwordHint">(Laissez vide pour ne pas changer)</small>
                                </label>
                                <input type="password" class="form-control" id="adminPassword" name="password" minlength="6">
                            </div>
                            <div class="mb-3">
                                <label for="adminRole" class="form-label">Rôle *</label>
                                <select class="form-select" id="adminRole" name="role" required>
                                    <?php foreach ($roleLabels as $roleValue => $roleLabel): ?>
                                        <option value="<?php echo htmlspecialchars($roleValue); ?>"><?php echo htmlspecialchars($roleLabel); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        function openAdminModal(admin) {
            const modal = new bootstrap.Modal(document.getElementById('adminModal'));
            const form = document.getElementById('adminForm');
            const actionInput = document.getElementById('adminAction');
            const adminIdInput = document.getElementById('adminId');
            const usernameInput = document.getElementById('adminUsername');
            const emailInput = document.getElementById('adminEmail');
            const nomCompletInput = document.getElementById('adminNomComplet');
            const passwordInput = document.getElementById('adminPassword');
            const roleInput = document.getElementById('adminRole');
            const passwordRequired = document.getElementById('passwordRequired');
            const passwordHint = document.getElementById('passwordHint');
            const modalTitle = document.getElementById('adminModalLabel');
            
            if (admin) {
                // Mode édition
                modalTitle.textContent = 'Modifier l\'Administrateur';
                actionInput.value = 'update_admin';
                adminIdInput.value = admin.id;
                usernameInput.value = admin.username;
                usernameInput.disabled = true;
                emailInput.value = admin.email;
                nomCompletInput.value = admin.nom_complet || '';
                passwordInput.value = '';
                passwordInput.required = false;
                passwordRequired.style.display = 'none';
                passwordHint.style.display = 'inline';
                roleInput.value = admin.role;
            } else {
                // Mode création
                modalTitle.textContent = 'Nouvel Administrateur';
                actionInput.value = 'create_admin';
                adminIdInput.value = '';
                form.reset();
                usernameInput.disabled = false;
                passwordInput.required = true;
                passwordRequired.style.display = 'inline';
                passwordHint.style.display = 'none';
            }
            
            modal.show();
        }
        </script>

    <?php else: ?>
        <!-- ============================================
             SECTION NOT FOUND
             ============================================ -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>Section introuvable</h2>
            </div>
            <div class="admin-card-body">
                <p>La section "<?php echo htmlspecialchars($section); ?>" n'existe pas.</p>
                <a href="admin.php?section=dashboard" class="btn btn-primary">Retour au tableau de bord</a>
            </div>
        </div>
    <?php endif; ?>
</main>

<!-- Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="messageModalTitle">Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="messageModalBody">
                <!-- Message content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de réponse aux rendez-vous -->
<div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="responseModalLabel">
                    <i class="fas fa-envelope me-2"></i>Répondre au rendez-vous
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="responseModalInfo" class="alert alert-info mb-3">
                    <strong>Client:</strong> <span id="responseClientName"></span><br>
                    <strong>Email:</strong> <span id="responseClientEmail"></span><br>
                    <strong>Téléphone:</strong> <span id="responseClientPhone"></span><br>
                    <strong>Service:</strong> <span id="responseService"></span><br>
                    <strong>Date:</strong> <span id="responseDate"></span> à <span id="responseTime"></span>
                </div>
                
                <form id="responseForm">
                    <input type="hidden" id="responseAppointmentId" name="appointment_id">
                    <input type="hidden" id="responseAction" name="action">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="mb-3">
                        <label for="responseMessage" class="form-label">Message personnalisé</label>
                        <textarea class="form-control" 
                                  id="responseMessage" 
                                  name="message" 
                                  rows="8" 
                                  placeholder="Écrivez votre message... (Laissez vide pour utiliser le message par défaut)"></textarea>
                        <small class="form-text text-muted">Si vous laissez ce champ vide, un message par défaut sera envoyé.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Méthode d'envoi</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="send_method" id="sendEmail" value="email" checked>
                            <label class="form-check-label" for="sendEmail">
                                <i class="fas fa-envelope text-primary me-2"></i>Email uniquement
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="send_method" id="sendWhatsApp" value="whatsapp">
                            <label class="form-check-label" for="sendWhatsApp">
                                <i class="fab fa-whatsapp text-success me-2"></i>WhatsApp uniquement
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="send_method" id="sendBoth" value="both">
                            <label class="form-check-label" for="sendBoth">
                                <i class="fas fa-paper-plane text-info me-2"></i>Email et WhatsApp
                            </label>
                        </div>
                    </div>
                    
                    <div id="responseAlert" class="alert d-none" role="alert"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="sendResponseBtn" onclick="sendResponse()">
                    <i class="fas fa-paper-plane me-2"></i>Envoyer et mettre à jour
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Upload Image Gallery -->
<div class="modal fade" id="uploadImageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-upload"></i> Ajouter une image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="gallery-upload.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="mb-3">
                        <label for="gallery_image" class="form-label">Image</label>
                        <input type="file" class="form-control" id="gallery_image" name="image" accept="image/*" required>
                    </div>
                    <div class="mb-3">
                        <label for="gallery_title" class="form-label">Titre</label>
                        <input type="text" class="form-control" id="gallery_title" name="title">
                    </div>
                    <div class="mb-3">
                        <label for="gallery_description" class="form-label">Description</label>
                        <textarea class="form-control" id="gallery_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="gallery_category" class="form-label">Catégorie</label>
                        <input type="text" class="form-control" id="gallery_category" name="category" value="general">
                    </div>
                    <div class="mb-3">
                        <label for="gallery_alt" class="form-label">Texte alternatif (SEO)</label>
                        <input type="text" class="form-control" id="gallery_alt" name="alt_text">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Uploader</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Service -->
<div class="modal fade" id="serviceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-concierge-bell"></i> <span id="serviceModalTitle">Ajouter un service</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="serviceForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" id="serviceAction" value="add_service">
                    <input type="hidden" name="service_id" id="serviceId">
                    
                    <div class="mb-3">
                        <label for="service_title" class="form-label">Titre *</label>
                        <input type="text" class="form-control" id="service_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="service_description" class="form-label">Description courte</label>
                        <textarea class="form-control" id="service_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="service_full_description" class="form-label">Description complète</label>
                        <textarea class="form-control" id="service_full_description" name="full_description" rows="5"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="service_icon" class="form-label">Icône (Font Awesome)</label>
                                <input type="text" class="form-control" id="service_icon" name="icon" placeholder="fas fa-heart">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="service_image_url" class="form-label">URL de l'image</label>
                                <input type="url" class="form-control" id="service_image_url" name="image_url">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="service_price" class="form-label">Prix (FBU)</label>
                                <input type="number" class="form-control" id="service_price" name="price" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="service_duration" class="form-label">Durée</label>
                                <input type="text" class="form-control" id="service_duration" name="duration" placeholder="30 min">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="service_order" class="form-label">Ordre d'affichage</label>
                                <input type="number" class="form-control" id="service_order" name="order_index" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Options</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="service_active" name="active" checked>
                                    <label class="form-check-label" for="service_active">Actif</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="service_featured" name="featured">
                                    <label class="form-check-label" for="service_featured">Mis en avant</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Blog -->
<div class="modal fade" id="blogModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-blog"></i> <span id="blogModalTitle">Nouvel article</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="blogForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" id="blogAction" value="add_blog">
                    <input type="hidden" name="blog_id" id="blogId">
                    
                    <div class="mb-3">
                        <label for="blog_title" class="form-label">Titre *</label>
                        <input type="text" class="form-control" id="blog_title" name="title" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="blog_category" class="form-label">Catégorie</label>
                                <select class="form-select" id="blog_category" name="category_id">
                                    <option value="">Sans catégorie</option>
                                    <?php if (isset($blogCategories)): ?>
                                        <?php foreach ($blogCategories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="blog_status" class="form-label">Statut</label>
                                <select class="form-select" id="blog_status" name="status">
                                    <option value="draft">Brouillon</option>
                                    <option value="published">Publié</option>
                                    <option value="archived">Archivé</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="blog_description" class="form-label">Description/Résumé</label>
                        <textarea class="form-control" id="blog_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="blog_content" class="form-label">Contenu</label>
                        <textarea class="form-control" id="blog_content" name="content" rows="10"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="blog_image" class="form-label">URL de l'image</label>
                        <input type="url" class="form-control" id="blog_image" name="image">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="blog_meta_title" class="form-label">Meta Title (SEO)</label>
                                <input type="text" class="form-control" id="blog_meta_title" name="meta_title">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Options</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="blog_featured" name="featured">
                                    <label class="form-check-label" for="blog_featured">Mis en avant</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="blog_meta_description" class="form-label">Meta Description (SEO)</label>
                        <textarea class="form-control" id="blog_meta_description" name="meta_description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Category -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-folder"></i> Gestion des catégories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">La gestion complète des catégories sera disponible prochainement.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
