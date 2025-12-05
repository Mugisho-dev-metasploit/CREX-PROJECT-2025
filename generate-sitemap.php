<?php
/**
 * Générateur de Sitemap XML - CREx
 * 
 * Génère automatiquement un sitemap.xml pour le référencement
 * 
 * UTILISATION :
 * php generate-sitemap.php
 * 
 * Le fichier sitemap.xml sera créé à la racine du projet.
 */

// Configuration
$baseUrl = 'https://www.crex.com'; // Domaine réel du site
$priority = [
    'index.html' => '1.0',
    'about.html' => '0.9',
    'services.php' => '0.9',
    'contact.html' => '0.8',
    'appointment.html' => '0.8',
    'gallery.php' => '0.7',
    'blog.php' => '0.7',
];

$changefreq = [
    'index.html' => 'weekly',
    'about.html' => 'monthly',
    'services.php' => 'weekly',
    'contact.html' => 'monthly',
    'appointment.html' => 'monthly',
    'gallery.php' => 'weekly',
    'blog.php' => 'daily',
];

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Génération du Sitemap XML - CREx                        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Pages statiques
$pages = [
    'index.html',
    'about.html',
    'contact.html',
    'appointment.html',
    'services.php',
    'gallery.php',
    'blog.php',
];

// Générer le XML
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$urlCount = 0;

foreach ($pages as $page) {
    $filePath = __DIR__ . '/' . $page;
    if (file_exists($filePath)) {
        $url = rtrim($baseUrl, '/') . '/' . $page;
        $lastmod = date('Y-m-d', filemtime($filePath));
        $prio = $priority[$page] ?? '0.5';
        $freq = $changefreq[$page] ?? 'monthly';
        
        $xml .= "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
        $xml .= "    <lastmod>$lastmod</lastmod>\n";
        $xml .= "    <changefreq>$freq</changefreq>\n";
        $xml .= "    <priority>$prio</priority>\n";
        $xml .= "  </url>\n";
        
        echo "  ✓ $page\n";
        $urlCount++;
    } else {
        echo "  - $page : Non trouvé\n";
    }
}

// Ajouter les pages dynamiques depuis la base de données (si disponible)
if (file_exists(__DIR__ . '/config.php')) {
    try {
        require_once __DIR__ . '/config.php';
        $pdo = getDBConnection();
        
        // Pages depuis la base de données
        // Note: La table pages utilise 'name' au lieu de 'slug' et 'last_updated' au lieu de 'updated_at'
        $stmt = $pdo->query("SELECT name, last_updated FROM pages");
        $dbPages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($dbPages as $page) {
            $url = rtrim($baseUrl, '/') . '/page.php?name=' . urlencode($page['name']);
            $lastmod = date('Y-m-d', strtotime($page['last_updated']));
            
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
            $xml .= "    <lastmod>$lastmod</lastmod>\n";
            $xml .= "    <changefreq>monthly</changefreq>\n";
            $xml .= "    <priority>0.6</priority>\n";
            $xml .= "  </url>\n";
            
            echo "  ✓ Page DB : " . $page['name'] . "\n";
            $urlCount++;
        }
        
        // Articles de blog
        // Note: La table s'appelle 'blog' (pas 'blog_posts') et utilise 'status' avec 'published' (pas 'statut' avec 'publie')
        $stmt = $pdo->query("SELECT slug, updated_at FROM blog WHERE status = 'published'");
        $blogPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($blogPosts as $post) {
            $url = rtrim($baseUrl, '/') . '/blog.php?slug=' . urlencode($post['slug']);
            $lastmod = date('Y-m-d', strtotime($post['updated_at']));
            
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
            $xml .= "    <lastmod>$lastmod</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.7</priority>\n";
            $xml .= "  </url>\n";
            
            echo "  ✓ Article : " . $post['slug'] . "\n";
            $urlCount++;
        }
        
    } catch (Exception $e) {
        echo "  ⚠️  Impossible de récupérer les pages depuis la DB : " . $e->getMessage() . "\n";
    }
}

$xml .= '</urlset>';

// Sauvegarder le fichier
$sitemapFile = __DIR__ . '/sitemap.xml';
if (file_put_contents($sitemapFile, $xml)) {
    echo "\n✅ Sitemap généré avec succès !\n";
    echo "   Fichier : sitemap.xml\n";
    echo "   URLs : $urlCount\n";
    echo "   Taille : " . round(filesize($sitemapFile) / 1024, 2) . " KB\n";
    echo "   Domaine : $baseUrl\n\n";
} else {
    echo "\n❌ Erreur lors de la création du sitemap.xml\n\n";
    exit(1);
}

