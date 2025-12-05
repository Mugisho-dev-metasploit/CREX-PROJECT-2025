<?php
// Header pour le panneau d'administration
// Note: Ce fichier est inclus depuis admin.php, donc les chemins sont relatifs à la racine
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

// Récupérer les informations de l'utilisateur
$currentUser = null;
try {
    $pdo = getDBConnection();
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!$currentUser && isset($_SESSION['admin_username'])) {
        $currentUser = ['username' => $_SESSION['admin_username'], 'role' => $_SESSION['user_role'] ?? 'admin'];
    }
    if (!$currentUser) {
        $currentUser = ['username' => 'Admin', 'role' => 'admin'];
    }
} catch (Exception $e) {
    $currentUser = ['username' => $_SESSION['admin_username'] ?? 'Admin', 'role' => $_SESSION['user_role'] ?? 'admin'];
}

$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitle = isset($pageTitle) ? $pageTitle : 'Administration CREx';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Inter (primary) + Poppins (fallback) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    
    <!-- Dark Mode Manager -->
    <script src="assets/js/dark-mode.js" defer></script>
    
    <!-- CSRF Token for AJAX -->
    <meta name="csrf-token" content="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>">
</head>
<body class="admin-body">
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary admin-top-nav">
        <div class="container-fluid">
            <button class="btn btn-link text-white d-lg-none" type="button" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand" href="admin.php">
                <i class="fas fa-hospital-alt me-2"></i>
                <span>CREx Admin</span>
            </a>
            
            <div class="navbar-nav ms-auto d-flex flex-row align-items-center">
                <!-- Dark Mode Toggle -->
                <button class="btn btn-link text-white me-3 theme-toggle" type="button" id="darkModeToggle" title="Basculer le mode sombre" data-theme-toggle>
                    <i class="fas fa-moon" id="darkModeIcon"></i>
                </button>
                
                <!-- User Info -->
                <div class="dropdown">
                    <button class="btn btn-link text-white dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2"></i>
                        <span><?php echo htmlspecialchars($currentUser['username'] ?? 'Admin'); ?></span>
                        <?php if (isset($currentUser['role'])): ?>
                            <span class="badge bg-light text-dark ms-2"><?php echo htmlspecialchars($currentUser['role']); ?></span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="admin-settings.php"><i class="fas fa-cog me-2"></i>Paramètres</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="admin-logout.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

