<?php
/**
 * Script Principal de Préparation pour la Production - CREx
 * 
 * Ce script automatise toutes les étapes de préparation pour la production :
 * 1. Nettoyage des fichiers inutiles
 * 2. Minification CSS/JS
 * 3. Export de la base de données
 * 4. Vérification de la structure
 * 5. Génération de la documentation
 * 
 * UTILISATION :
 * php prepare-production.php [--skip-clean] [--skip-minify] [--skip-export]
 * 
 * OPTIONS :
 * --skip-clean   : Ne pas nettoyer les fichiers
 * --skip-minify  : Ne pas minifier les assets
 * --skip-export  : Ne pas exporter la base de données
 * --all          : Exécuter toutes les étapes (par défaut)
 */

// Empêcher l'exécution accidentelle en production
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') === false) {
    die("❌ Ce script ne peut être exécuté qu'en localhost pour des raisons de sécurité.\n");
}

// Analyser les arguments
$skipClean = in_array('--skip-clean', $argv);
$skipMinify = in_array('--skip-minify', $argv);
$skipExport = in_array('--skip-export', $argv);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Préparation du Projet CREx pour la Production            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$startTime = microtime(true);
$errors = [];
$warnings = [];

// ============================================
// ÉTAPE 1 : Vérification de l'environnement
// ============================================
echo "📋 ÉTAPE 1 : Vérification de l'environnement\n";
echo str_repeat("─", 60) . "\n";

// Vérifier PHP
$phpVersion = PHP_VERSION;
echo "  ✓ PHP Version : $phpVersion\n";
if (version_compare($phpVersion, '7.4.0', '<')) {
    $warnings[] = "PHP 7.4+ recommandé (version actuelle : $phpVersion)";
    echo "  ⚠️  Version PHP ancienne\n";
}

// Vérifier les extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "  ✓ Extension $ext : OK\n";
    } else {
        $errors[] = "Extension $ext manquante";
        echo "  ✗ Extension $ext : MANQUANTE\n";
    }
}

// Vérifier config.php
if (file_exists(__DIR__ . '/config.php')) {
    echo "  ✓ config.php : Présent\n";
} else {
    $errors[] = "config.php manquant";
    echo "  ✗ config.php : MANQUANT\n";
}

// Vérifier config.production.php.example
if (file_exists(__DIR__ . '/config.production.php.example')) {
    echo "  ✓ config.production.php.example : Présent\n";
} else {
    $warnings[] = "config.production.php.example manquant";
    echo "  ⚠️  config.production.php.example : MANQUANT\n";
}

echo "\n";

// ============================================
// ÉTAPE 2 : Nettoyage des fichiers
// ============================================
if (!$skipClean) {
    echo "🧹 ÉTAPE 2 : Nettoyage des fichiers inutiles\n";
    echo str_repeat("─", 60) . "\n";
    
    require_once __DIR__ . '/clean-production.php';
    echo "\n";
} else {
    echo "⏭️  ÉTAPE 2 : Nettoyage ignoré (--skip-clean)\n\n";
}

// ============================================
// ÉTAPE 3 : Minification des assets
// ============================================
if (!$skipMinify) {
    echo "📦 ÉTAPE 3 : Minification des assets (CSS/JS)\n";
    echo str_repeat("─", 60) . "\n";
    
    require_once __DIR__ . '/minify-assets.php';
    echo "\n";
} else {
    echo "⏭️  ÉTAPE 3 : Minification ignorée (--skip-minify)\n\n";
}

// ============================================
// ÉTAPE 4 : Export de la base de données
// ============================================
if (!$skipExport) {
    echo "💾 ÉTAPE 4 : Export de la base de données\n";
    echo str_repeat("─", 60) . "\n";
    
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/export-database.php';
    } else {
        echo "  ⚠️  config.php manquant, export ignoré\n";
        $warnings[] = "Impossible d'exporter la base de données (config.php manquant)";
    }
    echo "\n";
} else {
    echo "⏭️  ÉTAPE 4 : Export ignoré (--skip-export)\n\n";
}

// ============================================
// ÉTAPE 5 : Vérification de la structure
// ============================================
echo "🔍 ÉTAPE 5 : Vérification de la structure\n";
echo str_repeat("─", 60) . "\n";

$requiredFiles = [
    'index.html',
    'config.php',
    '.htaccess',
    'style.css',
    'script.js',
    'assets/css/theme-variables.css',
    'assets/js/dark-mode.js',
];

$missingFiles = [];
foreach ($requiredFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "  ✓ $file\n";
    } else {
        $missingFiles[] = $file;
        echo "  ✗ $file : MANQUANT\n";
    }
}

if (!empty($missingFiles)) {
    $errors[] = "Fichiers manquants : " . implode(', ', $missingFiles);
}

echo "\n";

// ============================================
// ÉTAPE 6 : Vérification de la sécurité
// ============================================
echo "🔒 ÉTAPE 6 : Vérification de la sécurité\n";
echo str_repeat("─", 60) . "\n";

// Vérifier que config.production.php n'existe pas (ne doit pas être commité)
if (file_exists(__DIR__ . '/config.production.php')) {
    echo "  ⚠️  config.production.php existe (ne doit pas être commité)\n";
    $warnings[] = "config.production.php ne doit pas être dans le dépôt Git";
} else {
    echo "  ✓ config.production.php n'existe pas (correct)\n";
}

// Vérifier .gitignore
if (file_exists(__DIR__ . '/.gitignore')) {
    $gitignore = file_get_contents(__DIR__ . '/.gitignore');
    if (strpos($gitignore, 'config.production.php') !== false) {
        echo "  ✓ config.production.php dans .gitignore\n";
    } else {
        $warnings[] = "config.production.php devrait être dans .gitignore";
        echo "  ⚠️  config.production.php pas dans .gitignore\n";
    }
} else {
    $warnings[] = ".gitignore manquant";
    echo "  ⚠️  .gitignore : MANQUANT\n";
}

// Vérifier les fichiers sensibles
$sensitiveFiles = ['test-db-connection.php', 'phpinfo.php', 'generate-password-hash.php'];
foreach ($sensitiveFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $warnings[] = "$file devrait être supprimé en production";
        echo "  ⚠️  $file existe (à supprimer)\n";
    } else {
        echo "  ✓ $file : Absent (correct)\n";
    }
}

echo "\n";

// ============================================
// ÉTAPE 7 : Génération du rapport
// ============================================
echo "📊 ÉTAPE 7 : Génération du rapport\n";
echo str_repeat("─", 60) . "\n";

$reportFile = __DIR__ . '/PRODUCTION_REPORT_' . date('Y-m-d_H-i-s') . '.txt';
$report = fopen($reportFile, 'w');

fwrite($report, "╔════════════════════════════════════════════════════════════╗\n");
fwrite($report, "║  Rapport de Préparation pour la Production - CREx         ║\n");
fwrite($report, "║  Date : " . date('Y-m-d H:i:s') . "                              ║\n");
fwrite($report, "╚════════════════════════════════════════════════════════════╝\n\n");

fwrite($report, "📋 RÉSUMÉ\n");
fwrite($report, str_repeat("─", 60) . "\n");
fwrite($report, "Erreurs : " . count($errors) . "\n");
fwrite($report, "Avertissements : " . count($warnings) . "\n\n");

if (!empty($errors)) {
    fwrite($report, "❌ ERREURS :\n");
    foreach ($errors as $error) {
        fwrite($report, "  - $error\n");
    }
    fwrite($report, "\n");
}

if (!empty($warnings)) {
    fwrite($report, "⚠️  AVERTISSEMENTS :\n");
    foreach ($warnings as $warning) {
        fwrite($report, "  - $warning\n");
    }
    fwrite($report, "\n");
}

fwrite($report, "📁 FICHIERS CRÉÉS :\n");
if (!$skipMinify) {
    fwrite($report, "  - Fichiers .min.css et .min.js\n");
}
if (!$skipExport) {
    fwrite($report, "  - Fichier SQL d'export\n");
}

fwrite($report, "\n📝 PROCHAINES ÉTAPES :\n");
fwrite($report, "  1. Créer config.production.php à partir de config.production.php.example\n");
fwrite($report, "  2. Configurer les identifiants de production\n");
fwrite($report, "  3. Tester localement avec config.production.php\n");
fwrite($report, "  4. Transférer les fichiers sur le serveur\n");
fwrite($report, "  5. Importer la base de données\n");
fwrite($report, "  6. Configurer le domaine et SSL\n");
fwrite($report, "  7. Tester en production\n");

fclose($report);

echo "  ✓ Rapport généré : " . basename($reportFile) . "\n\n";

// ============================================
// RÉSUMÉ FINAL
// ============================================
$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  RÉSUMÉ FINAL                                              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "  ⏱️  Durée : {$duration}s\n";
echo "  ❌ Erreurs : " . count($errors) . "\n";
echo "  ⚠️  Avertissements : " . count($warnings) . "\n\n";

if (empty($errors)) {
    echo "✅ Préparation terminée avec succès !\n\n";
    echo "📋 PROCHAINES ÉTAPES :\n";
    echo "  1. Créer config.production.php\n";
    echo "  2. Tester localement\n";
    echo "  3. Transférer sur le serveur\n";
    echo "  4. Importer la base de données\n";
    echo "  5. Configurer le domaine\n\n";
    exit(0);
} else {
    echo "❌ Des erreurs ont été détectées. Veuillez les corriger avant le déploiement.\n\n";
    exit(1);
}

