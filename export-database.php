<?php
/**
 * Script d'Export de la Base de Données - CREx
 * Exporte la base de données en fichier SQL pour la production
 * 
 * UTILISATION :
 * php export-database.php
 * 
 * Le fichier SQL sera créé dans le dossier actuel avec un timestamp.
 */

require_once 'config.php';

// Empêcher l'exécution accidentelle en production
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') === false) {
    die("Ce script ne peut être exécuté qu'en localhost pour des raisons de sécurité.\n");
}

echo "========================================\n";
echo "Export de la Base de Données - CREx\n";
echo "========================================\n\n";

try {
    $pdo = getDBConnection();
    
    // Nom du fichier de sortie
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "crex_db_export_{$timestamp}.sql";
    $filepath = __DIR__ . '/' . $filename;
    
    echo "Connexion à la base de données...\n";
    echo "  Base : " . DB_NAME . "\n";
    echo "  Host : " . DB_HOST . "\n\n";
    
    // Ouvrir le fichier en écriture
    $file = fopen($filepath, 'w');
    if (!$file) {
        throw new Exception("Impossible de créer le fichier : $filename");
    }
    
    // En-tête SQL
    fwrite($file, "-- ============================================\n");
    fwrite($file, "-- Export de la base de données CREx\n");
    fwrite($file, "-- Date : " . date('Y-m-d H:i:s') . "\n");
    fwrite($file, "-- Base : " . DB_NAME . "\n");
    fwrite($file, "-- ============================================\n\n");
    
    fwrite($file, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
    fwrite($file, "SET AUTOCOMMIT = 0;\n");
    fwrite($file, "START TRANSACTION;\n");
    fwrite($file, "SET time_zone = \"+00:00\";\n\n");
    
    // Créer la base de données
    fwrite($file, "-- Création de la base de données\n");
    fwrite($file, "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n");
    fwrite($file, "USE `" . DB_NAME . "`;\n\n");
    
    // Récupérer toutes les tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Export des tables...\n";
    $tableCount = 0;
    
    foreach ($tables as $table) {
        echo "  - Export de la table : $table\n";
        $tableCount++;
        
        // Structure de la table
        fwrite($file, "-- --------------------------------------------------------\n");
        fwrite($file, "-- Structure de la table `$table`\n");
        fwrite($file, "-- --------------------------------------------------------\n\n");
        
        $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        fwrite($file, "DROP TABLE IF EXISTS `$table`;\n");
        fwrite($file, $createTable['Create Table'] . ";\n\n");
        
        // Données de la table
        fwrite($file, "-- Données de la table `$table`\n\n");
        
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            // Récupérer les colonnes
            $columns = array_keys($rows[0]);
            $columnsList = '`' . implode('`, `', $columns) . '`';
            
            fwrite($file, "INSERT INTO `$table` ($columnsList) VALUES\n");
            
            $values = [];
            foreach ($rows as $row) {
                $rowValues = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $rowValues[] = 'NULL';
                    } else {
                        // Échapper les valeurs
                        $escaped = $pdo->quote($value);
                        $rowValues[] = $escaped;
                    }
                }
                $values[] = '(' . implode(', ', $rowValues) . ')';
            }
            
            fwrite($file, implode(",\n", $values) . ";\n\n");
        } else {
            fwrite($file, "-- Aucune donnée dans cette table\n\n");
        }
    }
    
    // Fin du fichier
    fwrite($file, "COMMIT;\n");
    
    fclose($file);
    
    $fileSize = filesize($filepath);
    $fileSizeMB = round($fileSize / 1024 / 1024, 2);
    
    echo "\n========================================\n";
    echo "Export terminé avec succès !\n";
    echo "  - Fichier : $filename\n";
    echo "  - Taille : $fileSizeMB MB\n";
    echo "  - Tables exportées : $tableCount\n";
    echo "========================================\n\n";
    
    echo "✅ Le fichier SQL est prêt pour l'import en production.\n";
    echo "⚠️  N'oubliez pas de :\n";
    echo "   1. Vérifier le contenu du fichier\n";
    echo "   2. Sécuriser le fichier (ne pas le partager publiquement)\n";
    echo "   3. Supprimer les mots de passe en clair si présents\n";
    echo "   4. Importer sur le serveur de production\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Erreur lors de l'export :\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);
}

