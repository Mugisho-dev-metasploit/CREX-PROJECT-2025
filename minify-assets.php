<?php
/**
 * Script de Minification CSS/JS pour la Production - CREx
 * Minifie les fichiers CSS et JS pour réduire la taille
 * 
 * UTILISATION :
 * php minify-assets.php
 * 
 * NOTE : Ce script utilise des fonctions PHP simples.
 * Pour une meilleure compression, utilisez des outils comme :
 * - cssnano (Node.js)
 * - terser (Node.js)
 * - ou des services en ligne
 */

// Empêcher l'exécution accidentelle en production
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') === false) {
    die("Ce script ne peut être exécuté qu'en localhost pour des raisons de sécurité.\n");
}

echo "========================================\n";
echo "Minification des Assets - CREx\n";
echo "========================================\n\n";

/**
 * Minifier CSS simple
 */
function minifyCSS($css) {
    // Supprimer les commentaires
    $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
    
    // Supprimer les espaces inutiles
    $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
    $css = str_replace(['  ', '  '], ' ', $css);
    
    // Supprimer les espaces autour de certains caractères
    $css = str_replace([' {', '{ ', ' }', '} ', ' :', ': ', ' ;', '; ', ' ,', ', ', ' >', '> ', ' +', '+ ', ' ~', '~ '], ['{', '{', '}', '}', ':', ':', ';', ';', ',', ',', '>', '>', '+', '+', '~', '~'], $css);
    
    return trim($css);
}

/**
 * Minifier JavaScript simple
 */
function minifyJS($js) {
    // Supprimer les commentaires sur une ligne
    $js = preg_replace('/(?<!:)\/\/.*$/m', '', $js);
    
    // Supprimer les commentaires multi-lignes
    $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);
    
    // Supprimer les espaces inutiles
    $js = preg_replace('/\s+/', ' ', $js);
    $js = str_replace([' {', '{ ', ' }', '} ', ' (', '( ', ' )', ') ', ' ;', '; ', ' ,', ', '], ['{', '{', '}', '}', '(', '(', ')', ')', ';', ';', ',', ','], $js);
    
    return trim($js);
}

// Fichiers CSS à minifier
$cssFiles = [
    'style.css',
    'assets/css/admin.css',
    'assets/css/appointment.css',
    'assets/css/auth.css',
    'assets/css/contact.css',
    'assets/css/theme-variables.css',
    'assets/css/theme-fixes.css',
    'assets/css/theme-white-text-fixes.css',
];

// Fichiers JS à minifier
$jsFiles = [
    'script.js',
    'dark-mode.js',
    'theme-init.js',
    'gallery-lightbox.js',
    'contact-form.js',
    'appointment-form.js',
    'assets/js/admin.js',
    'assets/js/admin-login.js',
    'assets/js/appointment.js',
    'assets/js/contact-map.js',
    'assets/js/create-admin-account.js',
    'assets/js/dark-mode.js',
    'assets/js/theme-manager.js',
    'js/admin-database.js',
];

$minifiedCount = 0;
$errorCount = 0;

// Minifier les CSS
echo "Minification des fichiers CSS...\n";
foreach ($cssFiles as $file) {
    $filePath = __DIR__ . '/' . $file;
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $minified = minifyCSS($content);
        $minFile = str_replace('.css', '.min.css', $filePath);
        
        if (file_put_contents($minFile, $minified)) {
            $originalSize = filesize($filePath);
            $minifiedSize = filesize($minFile);
            $reduction = round((1 - $minifiedSize / $originalSize) * 100, 2);
            echo "  ✓ $file : {$originalSize} → {$minifiedSize} bytes (-{$reduction}%)\n";
            $minifiedCount++;
        } else {
            echo "  ✗ Erreur lors de la minification : $file\n";
            $errorCount++;
        }
    } else {
        echo "  - Non trouvé : $file\n";
    }
}

// Minifier les JS
echo "\nMinification des fichiers JavaScript...\n";
foreach ($jsFiles as $file) {
    $filePath = __DIR__ . '/' . $file;
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $minified = minifyJS($content);
        $minFile = str_replace('.js', '.min.js', $filePath);
        
        if (file_put_contents($minFile, $minified)) {
            $originalSize = filesize($filePath);
            $minifiedSize = filesize($minFile);
            $reduction = round((1 - $minifiedSize / $originalSize) * 100, 2);
            echo "  ✓ $file : {$originalSize} → {$minifiedSize} bytes (-{$reduction}%)\n";
            $minifiedCount++;
        } else {
            echo "  ✗ Erreur lors de la minification : $file\n";
            $errorCount++;
        }
    } else {
        echo "  - Non trouvé : $file\n";
    }
}

echo "\n========================================\n";
echo "Résumé :\n";
echo "  - Fichiers minifiés : $minifiedCount\n";
echo "  - Erreurs : $errorCount\n";
echo "========================================\n\n";

echo "✅ Minification terminée !\n";
echo "⚠️  Les fichiers .min.css et .min.js ont été créés.\n";
echo "   Vous devez maintenant mettre à jour les références dans vos fichiers HTML/PHP.\n\n";

