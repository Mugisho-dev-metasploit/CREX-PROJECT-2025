<?php
/**
 * Script de Vérification Pré-Déploiement - CREx
 * 
 * Vérifie que le projet est prêt pour la production avant le déploiement.
 * 
 * UTILISATION :
 * php pre-deployment-check.php
 */

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Vérification Pré-Déploiement - CREx                     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$errors = [];
$warnings = [];
$checks = [];

// ============================================
// 1. Vérification des fichiers essentiels
// ============================================
echo "📁 1. Vérification des fichiers essentiels\n";
echo str_repeat("─", 60) . "\n";

$essentialFiles = [
    'index.html' => 'Page d\'accueil',
    'config.php' => 'Configuration',
    '.htaccess' => 'Configuration Apache',
    'style.css' => 'Styles principaux',
    'script.js' => 'Scripts principaux',
    'assets/css/theme-variables.css' => 'Variables de thème',
    'assets/js/dark-mode.js' => 'Gestion du thème',
    'database.sql' => 'Base de données',
];

foreach ($essentialFiles as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "  ✓ $file ($description)\n";
        $checks[] = ['type' => 'file', 'name' => $file, 'status' => 'ok'];
    } else {
        echo "  ✗ $file ($description) : MANQUANT\n";
        $errors[] = "Fichier manquant : $file";
        $checks[] = ['type' => 'file', 'name' => $file, 'status' => 'error'];
    }
}

echo "\n";

// ============================================
// 2. Vérification de la configuration
// ============================================
echo "⚙️  2. Vérification de la configuration\n";
echo str_repeat("─", 60) . "\n";

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    
    // Vérifier que les constantes sont définies
    $requiredConstants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_CHARSET'];
    foreach ($requiredConstants as $constant) {
        if (defined($constant)) {
            echo "  ✓ $constant défini\n";
        } else {
            echo "  ✗ $constant : NON DÉFINI\n";
            $errors[] = "Constante manquante : $constant";
        }
    }
    
    // Vérifier le mode de développement
    if (defined('DEBUG_MODE')) {
        if (DEBUG_MODE === true) {
            echo "  ⚠️  DEBUG_MODE activé (désactiver en production)\n";
            $warnings[] = "DEBUG_MODE devrait être false en production";
        } else {
            echo "  ✓ DEBUG_MODE désactivé\n";
        }
    }
    
    if (defined('DISPLAY_ERRORS')) {
        if (DISPLAY_ERRORS === true) {
            echo "  ⚠️  DISPLAY_ERRORS activé (désactiver en production)\n";
            $warnings[] = "DISPLAY_ERRORS devrait être false en production";
        } else {
            echo "  ✓ DISPLAY_ERRORS désactivé\n";
        }
    }
} else {
    echo "  ✗ config.php : MANQUANT\n";
    $errors[] = "config.php manquant";
}

echo "\n";

// ============================================
// 3. Vérification de la sécurité
// ============================================
echo "🔒 3. Vérification de la sécurité\n";
echo str_repeat("─", 60) . "\n";

// Fichiers sensibles qui ne doivent pas exister
$sensitiveFiles = [
    'test-db-connection.php',
    'phpinfo.php',
    'generate-password-hash.php',
    'fix-testimonials-table.php',
    'migrate-admin-users.php',
    'install-database.php',
    'init-settings.php',
    'create-logs-dir.php',
    'verify-paths.php',
];

foreach ($sensitiveFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "  ⚠️  $file existe (à supprimer en production)\n";
        $warnings[] = "$file devrait être supprimé";
    } else {
        echo "  ✓ $file : Absent\n";
    }
}

// Vérifier config.production.php
if (file_exists(__DIR__ . '/config.production.php')) {
    echo "  ⚠️  config.production.php existe (ne pas commiter)\n";
    $warnings[] = "config.production.php ne doit pas être dans Git";
} else {
    echo "  ✓ config.production.php : Absent (correct pour Git)\n";
}

// Vérifier .gitignore
if (file_exists(__DIR__ . '/.gitignore')) {
    $gitignore = file_get_contents(__DIR__ . '/.gitignore');
    if (strpos($gitignore, 'config.production.php') !== false) {
        echo "  ✓ config.production.php dans .gitignore\n";
    } else {
        echo "  ⚠️  config.production.php pas dans .gitignore\n";
        $warnings[] = "Ajouter config.production.php à .gitignore";
    }
} else {
    echo "  ⚠️  .gitignore : MANQUANT\n";
    $warnings[] = ".gitignore manquant";
}

echo "\n";

// ============================================
// 4. Vérification des chemins
// ============================================
echo "🔗 4. Vérification des chemins\n";
echo str_repeat("─", 60) . "\n";

// Vérifier les fichiers HTML/PHP pour les chemins absolus
$htmlFiles = glob(__DIR__ . '/*.{html,php}', GLOB_BRACE);
$absolutePathCount = 0;

foreach ($htmlFiles as $file) {
    $content = file_get_contents($file);
    
    // Chercher les chemins absolus (commençant par / ou http://)
    if (preg_match_all('/(href|src|action)=["\'](https?:\/\/|localhost|\/\/)/i', $content, $matches)) {
        $absolutePathCount += count($matches[0]);
    }
}

if ($absolutePathCount > 0) {
    echo "  ⚠️  $absolutePathCount chemins absolus détectés\n";
    $warnings[] = "Des chemins absolus ont été détectés (préférer les chemins relatifs)";
} else {
    echo "  ✓ Aucun chemin absolu détecté\n";
}

echo "\n";

// ============================================
// 5. Vérification de la base de données
// ============================================
echo "💾 5. Vérification de la base de données\n";
echo str_repeat("─", 60) . "\n";

if (file_exists(__DIR__ . '/config.php')) {
    try {
        require_once __DIR__ . '/config.php';
        $pdo = getDBConnection();
        echo "  ✓ Connexion à la base de données : OK\n";
        
        // Vérifier les tables essentielles
        $essentialTables = ['admin_users', 'contact_messages', 'pages', 'site_settings'];
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($essentialTables as $table) {
            if (in_array($table, $tables)) {
                echo "  ✓ Table $table : Existe\n";
            } else {
                echo "  ✗ Table $table : MANQUANTE\n";
                $errors[] = "Table manquante : $table";
            }
        }
        
        // Vérifier database.sql
        if (file_exists(__DIR__ . '/database.sql')) {
            $sqlSize = filesize(__DIR__ . '/database.sql');
            echo "  ✓ database.sql : Présent (" . round($sqlSize / 1024, 2) . " KB)\n";
        } else {
            echo "  ⚠️  database.sql : MANQUANT\n";
            $warnings[] = "database.sql manquant";
        }
        
    } catch (Exception $e) {
        echo "  ✗ Erreur de connexion : " . $e->getMessage() . "\n";
        $errors[] = "Impossible de se connecter à la base de données";
    }
} else {
    echo "  ⚠️  config.php manquant, vérification DB ignorée\n";
}

echo "\n";

// ============================================
// 6. Vérification des assets
// ============================================
echo "🎨 6. Vérification des assets\n";
echo str_repeat("─", 60) . "\n";

// Vérifier les CSS
$cssFiles = glob(__DIR__ . '/assets/css/*.css');
echo "  ✓ Fichiers CSS trouvés : " . count($cssFiles) . "\n";

// Vérifier les JS
$jsFiles = glob(__DIR__ . '/assets/js/*.js');
echo "  ✓ Fichiers JS trouvés : " . count($jsFiles) . "\n";

// Vérifier les images
$imageDirs = ['assets/images', 'img'];
foreach ($imageDirs as $dir) {
    if (is_dir(__DIR__ . '/' . $dir)) {
        $images = glob(__DIR__ . '/' . $dir . '/**/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE);
        echo "  ✓ Images dans $dir : " . count($images) . "\n";
    }
}

echo "\n";

// ============================================
// 7. Vérification .htaccess
// ============================================
echo "📄 7. Vérification .htaccess\n";
echo str_repeat("─", 60) . "\n";

if (file_exists(__DIR__ . '/.htaccess')) {
    $htaccess = file_get_contents(__DIR__ . '/.htaccess');
    
    // Vérifier les règles de sécurité
    $securityChecks = [
        'Options -Indexes' => 'Liste des répertoires désactivée',
        'X-XSS-Protection' => 'Protection XSS',
        'X-Content-Type-Options' => 'Protection MIME sniffing',
        'X-Frame-Options' => 'Protection clickjacking',
    ];
    
    foreach ($securityChecks as $check => $description) {
        if (strpos($htaccess, $check) !== false) {
            echo "  ✓ $description\n";
        } else {
            echo "  ⚠️  $description : Non configuré\n";
            $warnings[] = "$description non configuré dans .htaccess";
        }
    }
    
    // Vérifier la compression GZIP
    if (strpos($htaccess, 'mod_deflate') !== false || strpos($htaccess, 'DEFLATE') !== false) {
        echo "  ✓ Compression GZIP configurée\n";
    } else {
        echo "  ⚠️  Compression GZIP non configurée\n";
        $warnings[] = "Compression GZIP non configurée";
    }
    
    // Vérifier le cache
    if (strpos($htaccess, 'mod_expires') !== false || strpos($htaccess, 'ExpiresActive') !== false) {
        echo "  ✓ Cache navigateur configuré\n";
    } else {
        echo "  ⚠️  Cache navigateur non configuré\n";
        $warnings[] = "Cache navigateur non configuré";
    }
    
} else {
    echo "  ⚠️  .htaccess : MANQUANT\n";
    $warnings[] = ".htaccess manquant (recommandé pour la production)";
}

echo "\n";

// ============================================
// RÉSUMÉ FINAL
// ============================================
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  RÉSUMÉ                                                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "  ❌ Erreurs : " . count($errors) . "\n";
echo "  ⚠️  Avertissements : " . count($warnings) . "\n\n";

if (!empty($errors)) {
    echo "❌ ERREURS DÉTECTÉES :\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️  AVERTISSEMENTS :\n";
    foreach ($warnings as $warning) {
        echo "  - $warning\n";
    }
    echo "\n";
}

if (empty($errors)) {
    echo "✅ Le projet semble prêt pour la production !\n";
    echo "⚠️  Vérifiez les avertissements avant le déploiement.\n\n";
    exit(0);
} else {
    echo "❌ Des erreurs doivent être corrigées avant le déploiement.\n\n";
    exit(1);
}

