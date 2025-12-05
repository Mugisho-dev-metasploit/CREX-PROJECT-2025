<?php
// ============================================
// Configuration de la base de données - CREx
// ============================================

// Détecter l'environnement (production ou développement)
$isProduction = (
    !empty($_SERVER['HTTP_HOST']) && 
    strpos($_SERVER['HTTP_HOST'], 'localhost') === false &&
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === false &&
    strpos($_SERVER['HTTP_HOST'], '.local') === false
);

// Charger la configuration de production si elle existe
if ($isProduction && file_exists(__DIR__ . '/config.production.php')) {
    require_once __DIR__ . '/config.production.php';
    return; // Arrêter l'exécution, config.production.php contient tout
}

// ============================================
// Configuration de DÉVELOPPEMENT (par défaut)
// ============================================

// Paramètres de connexion MySQL
define('DB_HOST', 'localhost');      // Adresse du serveur MySQL
define('DB_NAME', 'crex_db');        // Nom de la base de données
define('DB_USER', 'root');           // Nom d'utilisateur MySQL
define('DB_PASS', '');               // Mot de passe MySQL (vide par défaut sur XAMPP/WAMP)
define('DB_CHARSET', 'utf8mb4');     // Encodage UTF-8

// Mode de développement
define('DEBUG_MODE', true);
define('DISPLAY_ERRORS', true);
define('LOG_ERRORS', true);

/**
 * Fonction de connexion PDO sécurisée à la base de données
 *
 * @return PDO Instance de connexion PDO
 * @throws PDOException En cas d'erreur de connexion
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,    // Mode erreur : lever des exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // Mode de récupération par défaut : tableau associatif
            PDO::ATTR_EMULATE_PREPARES   => false,                     // Désactiver l'émulation des requêtes préparées pour plus de sécurité
            PDO::ATTR_PERSISTENT         => false,                     // Pas de connexion persistante
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Enregistrer l'erreur dans les logs
        error_log("Erreur de connexion DB: " . $e->getMessage());

        // En mode développement, afficher l'erreur détaillée
        // En production, remplacer par un message générique
        $isDevelopment = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false);
        
        if ($isDevelopment) {
            die("Erreur de connexion à la base de données.<br><br>" .
                "<strong>Erreur:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>" .
                "<strong>Solution:</strong> Vérifiez que:<br>" .
                "1. MySQL est démarré dans XAMPP<br>" .
                "2. La base de données 'crex_db' existe (exécutez database.sql dans phpMyAdmin)<br>" .
                "3. Les identifiants MySQL sont corrects dans config.php<br><br>" .
                "<a href='test-db-connection.php'>Tester la connexion</a>");
        } else {
            // En production, message générique
            die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
        }
    }
}

/**
 * Fonction pour tester la connexion à la base de données
 * Utile pour vérifier la configuration
 *
 * @return bool True si la connexion réussit, False sinon
 */
function testDBConnection() {
    try {
        $pdo = getDBConnection();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
