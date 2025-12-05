<?php
/**
 * Page d'authentification Admin - CREx
 * Version améliorée avec design moderne et sécurisé
 */

session_start();
require_once 'config.php';

// Si l'utilisateur est déjà connecté, rediriger vers admin.php
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

$error = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Vérifier d'abord dans admin_users (username ou email)
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE (username = ? OR email = ?) AND actif = 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Connexion réussie avec admin_users
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_email'] = $user['email'];
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'] ?? 'admin';
                $_SESSION['user_permissions'] = !empty($user['permissions']) ? json_decode($user['permissions'], true) : [];
                
                // Mettre à jour la dernière connexion
                $updateStmt = $pdo->prepare("UPDATE admin_users SET derniere_connexion = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                header('Location: admin.php');
                exit;
            }
            
            // Si pas trouvé dans admin_users, vérifier dans la table users (ancien système)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND active = 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Connexion réussie
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_email'] = $user['email'] ?? '';
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'] ?? 'admin';
                $_SESSION['user_permissions'] = [];
                
                // Mettre à jour la dernière connexion
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                header('Location: admin.php');
                exit;
            }
            
            // Si on arrive ici, les identifiants sont incorrects
            $error = 'Nom d\'utilisateur/Email ou mot de passe incorrect.';
            
        } catch (PDOException $e) {
            $error = 'Erreur de connexion à la base de données.';
            error_log("Admin login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Connexion Admin - CREx</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Inter (primary) + Poppins (fallback) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Auth CSS -->
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <button class="theme-toggle" id="darkModeToggle" type="button" title="Basculer le mode sombre" aria-label="Basculer le mode sombre">
        <i class="fas fa-moon" id="darkModeIcon"></i>
    </button>
    
    <div class="container-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-hospital-alt"></i>
                </div>
                <h1>Administration CREx</h1>
                <p>Connectez-vous pour gérer le site</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Erreur !</strong><br>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i>
                        Nom d'utilisateur ou Email *
                    </label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="username" 
                        name="username" 
                        required 
                        autofocus
                        autocomplete="username"
                        placeholder="Entrez votre nom d'utilisateur ou email">
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Mot de passe *
                    </label>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            required
                            autocomplete="current-password"
                            placeholder="Entrez votre mot de passe">
                        <button 
                            type="button" 
                            class="password-toggle" 
                            id="passwordToggle" 
                            aria-label="Afficher/masquer le mot de passe"
                            tabindex="-1">
                            <i class="fas fa-eye" id="passwordToggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" id="loginBtn">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Se connecter
                </button>
            </form>
            
            <div class="create-account-link">
                <a href="create-admin-account.php">
                    <i class="fas fa-user-plus"></i>
                    Créer un nouveau compte admin
                </a>
            </div>
            
            <div class="back-link">
                <a href="index.html">
                    <i class="fas fa-arrow-left"></i>
                    Retour au site
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Auth JS -->
    <script src="assets/js/admin-login.js"></script>
</body>
</html>
