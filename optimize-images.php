<?php
/**
 * Script d'Optimisation d'Images - CREx
 * 
 * Optimise les images pour la production (compression, conversion WebP)
 * 
 * UTILISATION :
 * php optimize-images.php [--format webp] [--quality 85]
 * 
 * NOTE : Nécessite ImageMagick ou GD pour la conversion WebP
 */

// Empêcher l'exécution accidentelle en production
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') === false) {
    die("Ce script ne peut être exécuté qu'en localhost pour des raisons de sécurité.\n");
}

// Options
$format = in_array('--format', $argv) ? $argv[array_search('--format', $argv) + 1] ?? 'webp' : 'webp';
$quality = in_array('--quality', $argv) ? (int)($argv[array_search('--quality', $argv) + 1] ?? 85) : 85;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Optimisation des Images - CREx                           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Vérifier les extensions
if (!extension_loaded('gd') && !extension_loaded('imagick')) {
    die("❌ Extension GD ou ImageMagick requise pour l'optimisation d'images.\n");
}

$hasGD = extension_loaded('gd');
$hasImagick = extension_loaded('imagick');

echo "Extensions disponibles :\n";
echo "  " . ($hasGD ? "✓" : "✗") . " GD\n";
echo "  " . ($hasImagick ? "✓" : "✗") . " ImageMagick\n\n";

// Dossiers d'images
$imageDirs = [
    __DIR__ . '/assets/images',
    __DIR__ . '/img',
    __DIR__ . '/assets/images/gallery',
];

$totalImages = 0;
$optimizedImages = 0;
$totalSizeBefore = 0;
$totalSizeAfter = 0;

/**
 * Optimiser une image
 */
function optimizeImage($sourcePath, $targetPath, $format, $quality) {
    global $hasGD, $hasImagick;
    
    $sourceInfo = getimagesize($sourcePath);
    if (!$sourceInfo) {
        return false;
    }
    
    $mime = $sourceInfo['mime'];
    $width = $sourceInfo[0];
    $height = $sourceInfo[1];
    
    // Charger l'image source
    $sourceImage = null;
    switch ($mime) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$sourceImage) {
        return false;
    }
    
    // Créer une nouvelle image optimisée
    $optimizedImage = imagecreatetruecolor($width, $height);
    
    // Préserver la transparence pour PNG
    if ($mime === 'image/png') {
        imagealphablending($optimizedImage, false);
        imagesavealpha($optimizedImage, true);
        $transparent = imagecolorallocatealpha($optimizedImage, 255, 255, 255, 127);
        imagefilledrectangle($optimizedImage, 0, 0, $width, $height, $transparent);
    }
    
    imagecopyresampled($optimizedImage, $sourceImage, 0, 0, 0, 0, $width, $height, $width, $height);
    
    // Sauvegarder selon le format
    $success = false;
    if ($format === 'webp' && function_exists('imagewebp')) {
        $success = imagewebp($optimizedImage, $targetPath, $quality);
    } elseif ($format === 'jpg' || $format === 'jpeg') {
        $success = imagejpeg($optimizedImage, $targetPath, $quality);
    } elseif ($format === 'png') {
        $success = imagepng($optimizedImage, $targetPath, 9);
    }
    
    imagedestroy($sourceImage);
    imagedestroy($optimizedImage);
    
    return $success;
}

// Parcourir les dossiers
foreach ($imageDirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    
    echo "📁 Dossier : $dir\n";
    echo str_repeat("─", 60) . "\n";
    
    $images = glob($dir . '/**/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
    
    foreach ($images as $imagePath) {
        $totalImages++;
        $sizeBefore = filesize($imagePath);
        $totalSizeBefore += $sizeBefore;
        
        $pathInfo = pathinfo($imagePath);
        $extension = strtolower($pathInfo['extension']);
        
        // Ignorer les images déjà optimisées
        if (strpos($pathInfo['filename'], '_optimized') !== false || 
            strpos($pathInfo['filename'], '.min') !== false) {
            echo "  ⏭️  " . basename($imagePath) . " (déjà optimisée)\n";
            continue;
        }
        
        // Créer le nom du fichier optimisé
        $optimizedPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_optimized.' . $format;
        
        if (optimizeImage($imagePath, $optimizedPath, $format, $quality)) {
            $sizeAfter = filesize($optimizedPath);
            $totalSizeAfter += $sizeAfter;
            $reduction = round((1 - $sizeAfter / $sizeBefore) * 100, 2);
            
            echo "  ✓ " . basename($imagePath) . " → " . basename($optimizedPath);
            echo " (" . round($sizeBefore / 1024, 2) . " KB → " . round($sizeAfter / 1024, 2) . " KB, -$reduction%)\n";
            $optimizedImages++;
        } else {
            echo "  ✗ " . basename($imagePath) . " : Erreur\n";
        }
    }
    
    echo "\n";
}

// Résumé
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  RÉSUMÉ                                                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "  Images traitées : $totalImages\n";
echo "  Images optimisées : $optimizedImages\n";
echo "  Taille avant : " . round($totalSizeBefore / 1024 / 1024, 2) . " MB\n";
echo "  Taille après : " . round($totalSizeAfter / 1024 / 1024, 2) . " MB\n";

if ($totalSizeBefore > 0) {
    $totalReduction = round((1 - $totalSizeAfter / $totalSizeBefore) * 100, 2);
    echo "  Réduction totale : -$totalReduction%\n";
}

echo "\n";

if ($optimizedImages > 0) {
    echo "✅ Optimisation terminée !\n";
    echo "⚠️  Les fichiers optimisés ont le suffixe '_optimized'.\n";
    echo "   Remplacez les fichiers originaux après vérification.\n\n";
} else {
    echo "⚠️  Aucune image optimisée.\n\n";
}

