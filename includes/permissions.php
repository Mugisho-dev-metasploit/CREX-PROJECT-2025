<?php
/**
 * Système de permissions pour les administrateurs
 */

/**
 * Vérifie si l'utilisateur connecté a une permission spécifique
 */
function hasPermission($permission) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    
    $userRole = $_SESSION['user_role'] ?? 'admin';
    $userPermissions = $_SESSION['user_permissions'] ?? [];
    
    // Les super_admin ont tous les droits
    if ($userRole === 'super_admin') {
        return true;
    }
    
    // Vérifier les permissions par rôle
    $rolePermissions = getRolePermissions($userRole);
    if (in_array($permission, $rolePermissions)) {
        return true;
    }
    
    // Vérifier les permissions personnalisées
    if (in_array($permission, $userPermissions)) {
        return true;
    }
    
    return false;
}

/**
 * Retourne les permissions d'un rôle
 */
function getRolePermissions($role) {
    $permissions = [
        'super_admin' => [
            'manage_admins', 'manage_settings', 'manage_content', 'manage_gallery',
            'manage_services', 'manage_blog', 'manage_messages', 'manage_appointments',
            'view_statistics', 'manage_backups', 'manage_users', 'view_database'
        ],
        'admin' => [
            'manage_content', 'manage_gallery', 'manage_services', 'manage_blog',
            'manage_messages', 'manage_appointments', 'view_statistics', 'view_database'
        ],
        'editor' => [
            'manage_content', 'manage_gallery', 'manage_services', 'manage_blog',
            'manage_messages', 'view_statistics', 'view_database'
        ],
        'moderator' => [
            'manage_messages', 'manage_appointments', 'view_statistics', 'view_database'
        ]
    ];
    
    return $permissions[$role] ?? [];
}

/**
 * Vérifie si l'utilisateur peut gérer les admins
 */
function canManageAdmins() {
    return hasPermission('manage_admins');
}

/**
 * Vérifie si l'utilisateur peut gérer les paramètres
 */
function canManageSettings() {
    return hasPermission('manage_settings');
}

/**
 * Vérifie si l'utilisateur peut voir la base de données
 * Tous les admins peuvent voir la base de données (lecture seule)
 */
function canViewDatabase() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    
    $userRole = $_SESSION['user_role'] ?? 'admin';
    
    // Tous les rôles admin peuvent voir la base de données
    return in_array($userRole, ['super_admin', 'admin', 'editor', 'moderator']);
}

/**
 * Redirige si l'utilisateur n'a pas la permission
 */
function requirePermission($permission, $redirectUrl = 'admin.php') {
    if (!hasPermission($permission)) {
        header('Location: ' . $redirectUrl . '?error=' . urlencode('Vous n\'avez pas la permission d\'accéder à cette page.'));
        exit;
    }
}
?>

