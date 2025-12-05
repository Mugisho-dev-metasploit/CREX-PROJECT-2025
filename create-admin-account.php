<?php
/**
 * Script utilitaire pour créer un compte administrateur
 * Usage: http://localhost/crex_site/create-admin-account.php
 */

require_once 'config.php';

// Configuration
$defaultUsername = 'admin';
$defaultEmail = 'admin@crex.local';
$defaultPassword = '';
$defaultRole = 'super_admin';
$defaultNomComplet = 'Administrateur Principal';

$error = '';
$success = '';
$showDetails = false;

// Si c'est une requête POST, créer le compte
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $role = $_POST['role'] ?? 'super_admin';
    $nom_complet = trim($_POST['nom_complet'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = "Tous les champs obligatoires doivent être remplis.";
    } elseif (strlen($username) < 3) {
        $error = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email n'est pas valide.";
    } elseif (strlen($password) < 8 || strlen($password) > 15) {
        $error = "Le mot de passe doit contenir entre 8 et 15 caractères.";
    } elseif ($password !== $passwordConfirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Le mot de passe doit contenir au moins une majuscule.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = "Le mot de passe doit contenir au moins une minuscule.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Le mot de passe doit contenir au moins un chiffre.";
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = "Le mot de passe doit contenir au moins un caractère spécial (!@#$%^&*(),.?\":{}|<>).";
    } else {
        try {
            $pdo = getDBConnection();
            
            // Vérifier si le username ou l'email existe déjà
            $checkStmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
            $checkStmt->execute([$username, $email]);
            if ($checkStmt->fetch()) {
                $error = "Ce nom d'utilisateur ou cet email existe déjà.";
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                // Vérifier si la table a les nouvelles colonnes
                $columns = $pdo->query("SHOW COLUMNS FROM admin_users")->fetchAll(PDO::FETCH_COLUMN);
                $hasRole = in_array('role', $columns);
                
                if ($hasRole) {
                    $stmt = $pdo->prepare("INSERT INTO admin_users (username, email, nom_complet, password_hash, role, actif, email_verified) VALUES (?, ?, ?, ?, ?, 1, 1)");
                    $stmt->execute([$username, $email, $nom_complet, $passwordHash, $role]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO admin_users (username, email, nom_complet, password_hash, actif) VALUES (?, ?, ?, ?, 1)");
                    $stmt->execute([$username, $email, $nom_complet, $passwordHash]);
                }
                
                $success = "Compte administrateur créé avec succès !";
                $showDetails = true;
            }
        } catch (PDOException $e) {
            $error = "Erreur: " . $e->getMessage();
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
    <title>Créer un compte Admin - CREx</title>
    
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
    <div class="container-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h1>Créer un compte Admin</h1>
                <p>Configurez votre compte administrateur pour CREx</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>Succès !</strong><br>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                </div>
                
                <?php if ($showDetails): ?>
                    <div class="success-details">
                        <h5>
                            <i class="fas fa-info-circle"></i>
                            Détails du compte créé
                        </h5>
                        <div class="detail-item">
                            <span class="detail-label">Nom d'utilisateur:</span>
                            <span class="detail-value"><strong><?php echo htmlspecialchars($username); ?></strong></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value"><strong><?php echo htmlspecialchars($email); ?></strong></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Nom complet:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($nom_complet); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Rôle:</span>
                            <span class="detail-value">
                                <span class="role-badge <?php echo htmlspecialchars($role); ?>">
                                    <i class="fas fa-<?php 
                                        echo $role === 'super_admin' ? 'crown' : 
                                            ($role === 'admin' ? 'user-shield' : 
                                            ($role === 'editor' ? 'edit' : 'user-check')); 
                                    ?>"></i>
                                    <?php 
                                        $roleLabels = [
                                            'super_admin' => 'Super Administrateur',
                                            'admin' => 'Administrateur',
                                            'editor' => 'Éditeur',
                                            'moderator' => 'Modérateur'
                                        ];
                                        echo htmlspecialchars($roleLabels[$role] ?? $role);
                                    ?>
                                </span>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="back-link">
                    <a href="admin-login.php">
                        <i class="fas fa-arrow-left"></i>
                        Se connecter maintenant
                    </a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>
                            <strong>Erreur !</strong><br>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="adminForm" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-user"></i>
                            Nom d'utilisateur *
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="username" 
                            name="username" 
                            value="<?php echo htmlspecialchars($defaultUsername); ?>" 
                            required
                            minlength="3"
                            autocomplete="username"
                            placeholder="Entrez un nom d'utilisateur (min. 3 caractères)">
                        <div class="invalid-feedback" id="usernameFeedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i>
                            Email *
                        </label>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($defaultEmail); ?>" 
                            required
                            autocomplete="email"
                            placeholder="votre.email@exemple.com">
                        <div class="invalid-feedback" id="emailFeedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nom_complet" class="form-label">
                            <i class="fas fa-id-card"></i>
                            Nom complet
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="nom_complet" 
                            name="nom_complet" 
                            value="<?php echo htmlspecialchars($defaultNomComplet); ?>"
                            autocomplete="name"
                            placeholder="Nom et prénom">
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
                                minlength="8"
                                maxlength="15"
                                autocomplete="new-password"
                                placeholder="Entre 8 et 15 caractères"
                                oninput="checkPasswordStrength(this.value)">
                            <button type="button" class="password-toggle" id="passwordToggle" aria-label="Afficher/masquer le mot de passe">
                                <i class="fas fa-eye" id="passwordToggleIcon"></i>
                            </button>
                        </div>
                        
                        <!-- Password Requirements -->
                        <div class="password-requirements" id="passwordRequirements">
                            <div class="password-requirements-title">
                                <i class="fas fa-shield-alt"></i>
                                Critères de sécurité :
                            </div>
                            <div class="requirement-item" id="reqLength">
                                <i class="fas fa-circle"></i>
                                <span>Entre 8 et 15 caractères</span>
                            </div>
                            <div class="requirement-item" id="reqUppercase">
                                <i class="fas fa-circle"></i>
                                <span>Au moins une majuscule (A-Z)</span>
                            </div>
                            <div class="requirement-item" id="reqLowercase">
                                <i class="fas fa-circle"></i>
                                <span>Au moins une minuscule (a-z)</span>
                            </div>
                            <div class="requirement-item" id="reqNumber">
                                <i class="fas fa-circle"></i>
                                <span>Au moins un chiffre (0-9)</span>
                            </div>
                            <div class="requirement-item" id="reqSpecial">
                                <i class="fas fa-circle"></i>
                                <span>Au moins un caractère spécial (!@#$%^&*)</span>
                            </div>
                        </div>
                        
                        <!-- Password Strength -->
                        <div class="password-strength-container">
                            <div class="password-strength-label">
                                <span>Force du mot de passe :</span>
                                <span class="password-strength-text" id="passwordStrengthText">-</span>
                            </div>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                            </div>
                        </div>
                        
                        <div class="invalid-feedback" id="passwordFeedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label">
                            <i class="fas fa-lock"></i>
                            Confirmer le mot de passe *
                        </label>
                        <div class="password-input-wrapper">
                            <input 
                                type="password" 
                                class="form-control" 
                                id="password_confirm" 
                                name="password_confirm" 
                                required
                                autocomplete="new-password"
                                placeholder="Répétez le mot de passe"
                                oninput="checkPasswordMatch()">
                            <button type="button" class="password-toggle" id="passwordConfirmToggle" aria-label="Afficher/masquer le mot de passe">
                                <i class="fas fa-eye" id="passwordConfirmToggleIcon"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback" id="passwordConfirmFeedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">
                            <i class="fas fa-user-tag"></i>
                            Rôle *
                        </label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="super_admin" <?php echo $defaultRole === 'super_admin' ? 'selected' : ''; ?>>
                                👑 Super Administrateur
                            </option>
                            <option value="admin" <?php echo $defaultRole === 'admin' ? 'selected' : ''; ?>>
                                🛡️ Administrateur
                            </option>
                            <option value="editor" <?php echo $defaultRole === 'editor' ? 'selected' : ''; ?>>
                                ✏️ Éditeur
                            </option>
                            <option value="moderator" <?php echo $defaultRole === 'moderator' ? 'selected' : ''; ?>>
                                ✓ Modérateur
                            </option>
                        </select>
                        <small class="form-text">
                            <i class="fas fa-info-circle"></i>
                            Choisissez le niveau d'accès approprié
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-user-plus me-2"></i>
                        Créer le compte
                    </button>
                </form>
                
                <div class="back-link">
                    <a href="admin-login.php">
                        <i class="fas fa-arrow-left"></i>
                        Déjà un compte ? Se connecter
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Auth JS -->
    <script src="assets/js/create-admin-account.js"></script>
</body>
</html>
