<?php
// ============================================
// Système d'authentification sécurisé - CREx
// Gestion des sessions et vérification des permissions
// ============================================

session_start();
require_once 'config.php';

/**
 * Vérifie si l'utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 */
function hasRole($requiredRole) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['user_role'] ?? 'visitor';
    
    // Hiérarchie des rôles: admin > editor > visitor
    $roles = ['visitor' => 1, 'editor' => 2, 'admin' => 3];
    $userLevel = $roles[$userRole] ?? 0;
    $requiredLevel = $roles[$requiredRole] ?? 0;
    
    return $userLevel >= $requiredLevel;
}

/**
 * Requiert une connexion pour accéder à la page
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: admin-login.php');
        exit;
    }
}

/**
 * Requiert un rôle spécifique pour accéder à la page
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: admin.php?error=permission_denied');
        exit;
    }
}

/**
 * Authentifie un utilisateur
 */
function authenticateUser($username, $password) {
    try {
        $pdo = getDBConnection();
        
        // Chercher l'utilisateur dans la table users
        $stmt = $pdo->prepare("SELECT id, username, password, role, email, active FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            // Essayer aussi dans admin_users pour compatibilité
            $stmt = $pdo->prepare("SELECT id, username, password_hash as password, email, actif as active FROM admin_users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $user['role'] = 'admin'; // Par défaut admin pour les anciens comptes
            }
        }
        
        if (!$user) {
            return ['success' => false, 'message' => 'Nom d\'utilisateur ou mot de passe incorrect.'];
        }
        
        // Vérifier si le compte est actif
        $active = $user['active'] ?? $user['actif'] ?? 0;
        if (!$active) {
            return ['success' => false, 'message' => 'Votre compte a été désactivé. Contactez l\'administrateur.'];
        }
        
        // Vérifier le mot de passe
        $passwordHash = $user['password'];
        if (!password_verify($password, $passwordHash)) {
            return ['success' => false, 'message' => 'Nom d\'utilisateur ou mot de passe incorrect.'];
        }
        
        // Connexion réussie - créer la session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'] ?? 'admin';
        $_SESSION['user_email'] = $user['email'] ?? '';
        $_SESSION['user_logged_in'] = true;
        
        // Mettre à jour la dernière connexion
        try {
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            $updateStmt->execute([':id' => $user['id']]);
        } catch (Exception $e) {
            // Ignorer si la table n'existe pas encore
        }
        
        // Enregistrer dans le journal d'activité
        logActivity($user['id'], 'login', 'user', $user['id'], 'Connexion réussie');
        
        return ['success' => true, 'user' => $user];
        
    } catch (PDOException $e) {
        error_log("Erreur d'authentification: " . $e->getMessage());
        return ['success' => false, 'message' => 'Une erreur est survenue. Veuillez réessayer plus tard.'];
    }
}

/**
 * Déconnecte l'utilisateur
 */
function logoutUser() {
    if (isLoggedIn()) {
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            logActivity($userId, 'logout', 'user', $userId, 'Déconnexion');
        }
    }
    
    // Détruire la session
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
}

/**
 * Enregistre une activité dans le journal
 */
function logActivity($userId, $action, $entityType = null, $entityId = null, $description = null) {
    try {
        $pdo = getDBConnection();
        
        // Essayer d'insérer dans activity_log
        try {
            $stmt = $pdo->prepare("
                INSERT INTO activity_log (user_id, action, entity_type, entity_id, description, ip_address, user_agent, date_time)
                VALUES (:user_id, :action, :entity_type, :entity_id, :description, :ip_address, :user_agent, NOW())
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':action' => $action,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':description' => $description,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (PDOException $e) {
            // Si activity_log n'existe pas, essayer security_logs
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO security_logs (admin_id, type, description, ip_address, user_agent, date_creation)
                    VALUES (:admin_id, :type, :description, :ip_address, :user_agent, NOW())
                ");
                
                $stmt->execute([
                    ':admin_id' => $userId,
                    ':type' => $action,
                    ':description' => $description,
                    ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                    ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);
            } catch (PDOException $e2) {
                // Ignorer si aucune table n'existe
                error_log("Impossible d'enregistrer l'activité: " . $e2->getMessage());
            }
        }
    } catch (Exception $e) {
        error_log("Erreur lors de l'enregistrement de l'activité: " . $e->getMessage());
    }
}

/**
 * Vérifie les tentatives de connexion et applique la limitation
 */
function checkLoginAttempts($username) {
    $maxAttempts = 3;
    $lockoutTime = 300; // 5 minutes
    
    $attemptsKey = 'login_attempts_' . md5($username);
    $lastAttemptKey = 'last_login_attempt_' . md5($username);
    
    $attempts = $_SESSION[$attemptsKey] ?? 0;
    $lastAttempt = $_SESSION[$lastAttemptKey] ?? 0;
    
    if ($attempts >= $maxAttempts && (time() - $lastAttempt) < $lockoutTime) {
        $remainingTime = $lockoutTime - (time() - $lastAttempt);
        return [
            'allowed' => false,
            'message' => "Trop de tentatives de connexion. Veuillez patienter " . ceil($remainingTime / 60) . " minutes."
        ];
    }
    
    return ['allowed' => true];
}

/**
 * Enregistre une tentative de connexion échouée
 */
function recordFailedLogin($username) {
    $attemptsKey = 'login_attempts_' . md5($username);
    $lastAttemptKey = 'last_login_attempt_' . md5($username);
    
    $attempts = $_SESSION[$attemptsKey] ?? 0;
    $_SESSION[$attemptsKey] = $attempts + 1;
    $_SESSION[$lastAttemptKey] = time();
    
    // Enregistrer dans le journal
    try {
        $pdo = getDBConnection();
        logActivity(null, 'failed_login', 'user', null, "Tentative de connexion échouée pour: $username");
    } catch (Exception $e) {
        // Ignorer
    }
}

/**
 * Réinitialise les tentatives de connexion après succès
 */
function resetLoginAttempts($username) {
    $attemptsKey = 'login_attempts_' . md5($username);
    $lastAttemptKey = 'last_login_attempt_' . md5($username);
    
    unset($_SESSION[$attemptsKey]);
    unset($_SESSION[$lastAttemptKey]);
}

/**
 * Obtient les informations de l'utilisateur connecté
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['user_username'] ?? null,
        'role' => $_SESSION['user_role'] ?? 'visitor',
        'email' => $_SESSION['user_email'] ?? ''
    ];
}

?>

