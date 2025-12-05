<?php
/**
 * Fonctions utilitaires pour l'administration de la base de données
 * CREx - Interface d'administration MySQL complète
 */

/**
 * Connexion MySQL avec privilèges administrateur (sans base spécifique)
 */
function getMySQLAdminConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        throw new Exception("Erreur de connexion MySQL: " . $e->getMessage());
    }
}

/**
 * Vérifier si l'utilisateur a les privilèges nécessaires
 */
function hasDatabaseAdminPrivileges() {
    try {
        $pdo = getMySQLAdminConnection();
        $stmt = $pdo->query("SHOW GRANTS FOR CURRENT_USER()");
        $grants = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $hasAllPrivileges = false;
        foreach ($grants as $grant) {
            if (stripos($grant, 'ALL PRIVILEGES') !== false || 
                (stripos($grant, 'CREATE') !== false && 
                 stripos($grant, 'DROP') !== false && 
                 stripos($grant, 'ALTER') !== false)) {
                $hasAllPrivileges = true;
                break;
            }
        }
        return $hasAllPrivileges;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Lister toutes les bases de données
 */
function listDatabases() {
    try {
        $pdo = getMySQLAdminConnection();
        $stmt = $pdo->query("SHOW DATABASES");
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Filtrer les bases système si nécessaire
        $systemDbs = ['information_schema', 'performance_schema', 'mysql', 'sys'];
        return array_filter($databases, function($db) use ($systemDbs) {
            return !in_array($db, $systemDbs);
        });
    } catch (Exception $e) {
        throw new Exception("Erreur lors de la récupération des bases: " . $e->getMessage());
    }
}

/**
 * Lister les tables d'une base de données
 */
function listTables($database) {
    try {
        $pdo = getMySQLAdminConnection();
        $pdo->exec("USE `" . str_replace('`', '``', $database) . "`");
        $stmt = $pdo->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        throw new Exception("Erreur lors de la récupération des tables: " . $e->getMessage());
    }
}

/**
 * Obtenir les informations sur une table
 */
function getTableInfo($database, $table) {
    try {
        $pdo = getMySQLAdminConnection();
        $pdo->exec("USE `" . str_replace('`', '``', $database) . "`");
        
        // Informations de la table
        $stmt = $pdo->query("SHOW TABLE STATUS LIKE '" . str_replace("'", "''", $table) . "'");
        $tableInfo = $stmt->fetch();
        
        // Colonnes
        $stmt = $pdo->query("SHOW FULL COLUMNS FROM `" . str_replace('`', '``', $table) . "`");
        $columns = $stmt->fetchAll();
        
        // Index
        $stmt = $pdo->query("SHOW INDEX FROM `" . str_replace('`', '``', $table) . "`");
        $indexes = $stmt->fetchAll();
        
        // Clés étrangères
        $stmt = $pdo->query("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = ?
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $stmt->execute([$database, $table]);
        $foreignKeys = $stmt->fetchAll();
        
        return [
            'info' => $tableInfo,
            'columns' => $columns,
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys
        ];
    } catch (Exception $e) {
        throw new Exception("Erreur lors de la récupération des informations: " . $e->getMessage());
    }
}

/**
 * Lister les utilisateurs MySQL
 */
function listMySQLUsers() {
    try {
        $pdo = getMySQLAdminConnection();
        $stmt = $pdo->query("SELECT User, Host FROM mysql.user ORDER BY User, Host");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        throw new Exception("Erreur lors de la récupération des utilisateurs: " . $e->getMessage());
    }
}

/**
 * Obtenir les privilèges d'un utilisateur
 */
function getUserPrivileges($user, $host = '%') {
    try {
        $pdo = getMySQLAdminConnection();
        // Utiliser une requête directe car SHOW GRANTS ne supporte pas les paramètres préparés
        $userEscaped = $pdo->quote($user);
        $hostEscaped = $pdo->quote($host);
        $stmt = $pdo->query("SHOW GRANTS FOR $userEscaped@$hostEscaped");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        throw new Exception("Erreur lors de la récupération des privilèges: " . $e->getMessage());
    }
}

/**
 * Exécuter une requête SQL (sécurisée)
 */
function executeSQLQuery($database, $query, $limit = 1000) {
    try {
        $pdo = getMySQLAdminConnection();
        $pdo->exec("USE `" . str_replace('`', '``', $database) . "`");
        
        // Détecter le type de requête
        $queryType = strtoupper(trim(explode(' ', trim($query))[0]));
        
        // Pour SELECT, ajouter une limite de sécurité
        if ($queryType === 'SELECT') {
            if (stripos($query, 'LIMIT') === false) {
                $query .= " LIMIT " . (int)$limit;
            }
        }
        
        $stmt = $pdo->query($query);
        
        if ($queryType === 'SELECT' || $queryType === 'SHOW' || $queryType === 'DESCRIBE' || $queryType === 'EXPLAIN') {
            $results = $stmt->fetchAll();
            $rowCount = count($results);
            return [
                'success' => true,
                'type' => $queryType,
                'data' => $results,
                'row_count' => $rowCount,
                'affected_rows' => $rowCount
            ];
        } else {
            $affectedRows = $stmt->rowCount();
            return [
                'success' => true,
                'type' => $queryType,
                'affected_rows' => $affectedRows,
                'message' => "Requête exécutée avec succès. $affectedRows ligne(s) affectée(s)."
            ];
        }
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'error_code' => $e->getCode()
        ];
    }
}

/**
 * Créer une base de données
 */
function createDatabase($databaseName, $charset = 'utf8mb4', $collation = 'utf8mb4_unicode_ci') {
    try {
        $pdo = getMySQLAdminConnection();
        $databaseName = preg_replace('/[^a-zA-Z0-9_]/', '', $databaseName); // Sécurité
        
        $sql = "CREATE DATABASE `$databaseName` CHARACTER SET $charset COLLATE $collation";
        $pdo->exec($sql);
        return ['success' => true, 'message' => "Base de données '$databaseName' créée avec succès."];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Supprimer une base de données
 */
function dropDatabase($databaseName) {
    try {
        $pdo = getMySQLAdminConnection();
        $databaseName = preg_replace('/[^a-zA-Z0-9_]/', '', $databaseName); // Sécurité
        
        $sql = "DROP DATABASE IF EXISTS `$databaseName`";
        $pdo->exec($sql);
        return ['success' => true, 'message' => "Base de données '$databaseName' supprimée avec succès."];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Créer un utilisateur MySQL
 */
function createMySQLUser($username, $host, $password) {
    try {
        $pdo = getMySQLAdminConnection();
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
        $host = preg_replace('/[^a-zA-Z0-9._%]/', '', $host);
        
        $sql = "CREATE USER ?@? IDENTIFIED BY ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $host, $password]);
        
        return ['success' => true, 'message' => "Utilisateur '$username'@'$host' créé avec succès."];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Accorder des privilèges à un utilisateur
 */
function grantPrivileges($username, $host, $database, $privileges, $table = '*') {
    try {
        $pdo = getMySQLAdminConnection();
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
        $host = preg_replace('/[^a-zA-Z0-9._%]/', '', $host);
        $database = preg_replace('/[^a-zA-Z0-9_]/', '', $database);
        $table = preg_replace('/[^a-zA-Z0-9_*]/', '', $table);
        
        $privilegesStr = is_array($privileges) ? implode(', ', $privileges) : $privileges;
        
        $sql = "GRANT $privilegesStr ON `$database`.`$table` TO ?@?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $host]);
        
        $pdo->exec("FLUSH PRIVILEGES");
        
        return ['success' => true, 'message' => "Privilèges accordés avec succès."];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Révoquer des privilèges d'un utilisateur
 */
function revokePrivileges($username, $host, $database, $privileges, $table = '*') {
    try {
        $pdo = getMySQLAdminConnection();
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
        $host = preg_replace('/[^a-zA-Z0-9._%]/', '', $host);
        $database = preg_replace('/[^a-zA-Z0-9_]/', '', $database);
        $table = preg_replace('/[^a-zA-Z0-9_*]/', '', $table);
        
        $privilegesStr = is_array($privileges) ? implode(', ', $privileges) : $privileges;
        
        $sql = "REVOKE $privilegesStr ON `$database`.`$table` FROM ?@?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $host]);
        
        $pdo->exec("FLUSH PRIVILEGES");
        
        return ['success' => true, 'message' => "Privilèges révoqués avec succès."];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Supprimer un utilisateur MySQL
 */
function dropMySQLUser($username, $host) {
    try {
        $pdo = getMySQLAdminConnection();
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
        $host = preg_replace('/[^a-zA-Z0-9._%]/', '', $host);
        
        $sql = "DROP USER IF EXISTS ?@?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $host]);
        
        return ['success' => true, 'message' => "Utilisateur '$username'@'$host' supprimé avec succès."];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Exporter une base de données en SQL
 */
function exportDatabase($database, $tables = null) {
    try {
        $pdo = getMySQLAdminConnection();
        $pdo->exec("USE `" . str_replace('`', '``', $database) . "`");
        
        $output = "-- Export de la base de données: $database\n";
        $output .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        $output .= "CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
        $output .= "USE `$database`;\n\n";
        
        $tablesToExport = $tables ?: listTables($database);
        
        foreach ($tablesToExport as $table) {
            // Structure de la table
            $stmt = $pdo->query("SHOW CREATE TABLE `" . str_replace('`', '``', $table) . "`");
            $createTable = $stmt->fetch();
            $output .= $createTable['Create Table'] . ";\n\n";
            
            // Données de la table
            $stmt = $pdo->query("SELECT * FROM `" . str_replace('`', '``', $table) . "`");
            $rows = $stmt->fetchAll();
            
            if (count($rows) > 0) {
                $output .= "-- Données de la table `$table`\n";
                foreach ($rows as $row) {
                    $columns = array_keys($row);
                    $values = array_map(function($val) use ($pdo) {
                        return $val === null ? 'NULL' : $pdo->quote($val);
                    }, array_values($row));
                    
                    $output .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                }
                $output .= "\n";
            }
        }
        
        return ['success' => true, 'data' => $output];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Obtenir les processus MySQL actifs
 */
function getMySQLProcesses() {
    try {
        $pdo = getMySQLAdminConnection();
        $stmt = $pdo->query("SHOW PROCESSLIST");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        throw new Exception("Erreur lors de la récupération des processus: " . $e->getMessage());
    }
}

/**
 * Obtenir les variables système MySQL
 */
function getMySQLVariables() {
    try {
        $pdo = getMySQLAdminConnection();
        $stmt = $pdo->query("SHOW VARIABLES");
        $variables = $stmt->fetchAll();
        
        $result = [];
        foreach ($variables as $var) {
            $result[$var['Variable_name']] = $var['Value'];
        }
        return $result;
    } catch (Exception $e) {
        throw new Exception("Erreur lors de la récupération des variables: " . $e->getMessage());
    }
}

/**
 * Obtenir le statut MySQL
 */
function getMySQLStatus() {
    try {
        $pdo = getMySQLAdminConnection();
        $stmt = $pdo->query("SHOW STATUS");
        $status = $stmt->fetchAll();
        
        $result = [];
        foreach ($status as $stat) {
            $result[$stat['Variable_name']] = $stat['Value'];
        }
        return $result;
    } catch (Exception $e) {
        throw new Exception("Erreur lors de la récupération du statut: " . $e->getMessage());
    }
}

/**
 * Lister les vues
 */
function listViews($database) {
    try {
        $pdo = getMySQLAdminConnection();
        $stmt = $pdo->prepare("
            SELECT TABLE_NAME 
            FROM information_schema.VIEWS 
            WHERE TABLE_SCHEMA = ?
        ");
        $stmt->execute([$database]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        throw new Exception("Erreur lors de la récupération des vues: " . $e->getMessage());
    }
}

/**
 * Lister les procédures stockées
 */
function listStoredProcedures($database) {
    try {
        $pdo = getMySQLAdminConnection();
        $stmt = $pdo->prepare("
            SELECT ROUTINE_NAME, ROUTINE_TYPE
            FROM information_schema.ROUTINES
            WHERE ROUTINE_SCHEMA = ?
        ");
        $stmt->execute([$database]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        throw new Exception("Erreur lors de la récupération des procédures: " . $e->getMessage());
    }
}

/**
 * Lister les triggers
 */
function listTriggers($database) {
    try {
        $pdo = getMySQLAdminConnection();
        $stmt = $pdo->prepare("
            SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_STATEMENT
            FROM information_schema.TRIGGERS
            WHERE TRIGGER_SCHEMA = ?
        ");
        $stmt->execute([$database]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        throw new Exception("Erreur lors de la récupération des triggers: " . $e->getMessage());
    }
}

/**
 * Obtenir le plan d'exécution d'une requête
 */
function explainQuery($database, $query) {
    try {
        $pdo = getMySQLAdminConnection();
        $pdo->exec("USE `" . str_replace('`', '``', $database) . "`");
        
        $explainQuery = "EXPLAIN " . $query;
        $stmt = $pdo->query($explainQuery);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        throw new Exception("Erreur lors de l'analyse: " . $e->getMessage());
    }
}

/**
 * Importer un fichier SQL
 */
function importSQL($database, $sqlContent) {
    try {
        $pdo = getMySQLAdminConnection();
        $pdo->exec("USE `" . str_replace('`', '``', $database) . "`");
        
        // Désactiver les vérifications de clés étrangères temporairement
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Diviser le contenu SQL en requêtes individuelles
        $queries = array_filter(
            array_map('trim', explode(';', $sqlContent)),
            function($q) { return !empty($q) && !preg_match('/^--/', $q); }
        );
        
        $executed = 0;
        $errors = [];
        
        foreach ($queries as $query) {
            try {
                $pdo->exec($query);
                $executed++;
            } catch (PDOException $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        // Réactiver les vérifications
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        return [
            'success' => count($errors) === 0,
            'executed' => $executed,
            'errors' => $errors
        ];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Importer un fichier CSV dans une table
 */
function importCSV($database, $table, $csvContent, $delimiter = ',', $enclosure = '"') {
    try {
        $pdo = getMySQLAdminConnection();
        $pdo->exec("USE `" . str_replace('`', '``', $database) . "`");
        
        // Utiliser fgetcsv avec un fichier temporaire pour une meilleure gestion
        $tempFile = tmpfile();
        fwrite($tempFile, $csvContent);
        rewind($tempFile);
        
        // Première ligne = en-têtes
        $headers = fgetcsv($tempFile, 0, $delimiter, $enclosure);
        if (!$headers || empty($headers)) {
            fclose($tempFile);
            throw new Exception('Fichier CSV vide ou invalide');
        }
        
        $headers = array_map('trim', $headers);
        
        // Obtenir les colonnes de la table
        $stmt = $pdo->query("SHOW COLUMNS FROM `" . str_replace('`', '``', $table) . "`");
        $tableColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Vérifier que les colonnes existent
        $validHeaders = array_intersect($headers, $tableColumns);
        if (empty($validHeaders)) {
            fclose($tempFile);
            throw new Exception('Aucune colonne valide trouvée. Colonnes disponibles: ' . implode(', ', $tableColumns));
        }
        
        $inserted = 0;
        $errors = [];
        $lineNum = 1; // On commence à 2 car la ligne 1 est les en-têtes
        
        $pdo->beginTransaction();
        
        while (($values = fgetcsv($tempFile, 0, $delimiter, $enclosure)) !== FALSE) {
            $lineNum++;
            
            // Ignorer les lignes vides
            if (empty(array_filter($values, function($v) { return trim($v) !== ''; }))) {
                continue;
            }
            
            if (count($values) !== count($headers)) {
                $errors[] = "Ligne $lineNum: Nombre de colonnes incorrect (attendu: " . count($headers) . ", trouvé: " . count($values) . ")";
                continue;
            }
            
            $data = array_combine($headers, $values);
            $data = array_intersect_key($data, array_flip($validHeaders));
            
            // Nettoyer les valeurs
            foreach ($data as $key => $value) {
                $data[$key] = trim($value);
                if ($data[$key] === '') {
                    $data[$key] = null;
                }
            }
            
            $columns = array_keys($data);
            $placeholders = array_fill(0, count($columns), '?');
            
            $sql = "INSERT INTO `" . str_replace('`', '``', $table) . "` (`" . 
                   implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
            
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array_values($data));
                $inserted++;
            } catch (PDOException $e) {
                $errors[] = "Ligne $lineNum: " . $e->getMessage();
            }
        }
        
        fclose($tempFile);
        $pdo->commit();
        
        return [
            'success' => count($errors) === 0,
            'inserted' => $inserted,
            'errors' => $errors
        ];
    } catch (Exception $e) {
        if (isset($tempFile)) {
            fclose($tempFile);
        }
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Créer un index sur une table
 */
function createIndex($database, $table, $indexName, $columns, $indexType = 'INDEX') {
    try {
        $pdo = getMySQLAdminConnection();
        $pdo->exec("USE `" . str_replace('`', '``', $database) . "`");
        
        $columnsStr = is_array($columns) ? implode(', ', array_map(function($col) use ($pdo) {
            return '`' . str_replace('`', '``', $col) . '`';
        }, $columns)) : '`' . str_replace('`', '``', $columns) . '`';
        
        $indexNameEscaped = str_replace('`', '``', $indexName);
        $tableEscaped = str_replace('`', '``', $table);
        
        $sql = "ALTER TABLE `$tableEscaped` ADD $indexType `$indexNameEscaped` ($columnsStr)";
        $pdo->exec($sql);
        
        return ['success' => true, 'message' => "Index créé avec succès"];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Supprimer un index
 */
function dropIndex($database, $table, $indexName) {
    try {
        $pdo = getMySQLAdminConnection();
        $pdo->exec("USE `" . str_replace('`', '``', $database) . "`");
        
        $indexNameEscaped = str_replace('`', '``', $indexName);
        $tableEscaped = str_replace('`', '``', $table);
        
        $sql = "ALTER TABLE `$tableEscaped` DROP INDEX `$indexNameEscaped`";
        $pdo->exec($sql);
        
        return ['success' => true, 'message' => "Index supprimé avec succès"];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Obtenir les logs d'erreur MySQL
 */
function getMySQLErrorLogs() {
    try {
        $pdo = getMySQLAdminConnection();
        $stmt = $pdo->query("SHOW VARIABLES LIKE 'log_error'");
        $logError = $stmt->fetch();
        
        $logs = [];
        if ($logError && !empty($logError['Value'])) {
            $logFile = $logError['Value'];
            if (file_exists($logFile) && is_readable($logFile)) {
                $logs = file($logFile);
                $logs = array_slice($logs, -100); // Dernières 100 lignes
            }
        }
        
        return $logs;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Obtenir les connexions actives
 */
function getActiveConnections() {
    try {
        $pdo = getMySQLAdminConnection();
        $stmt = $pdo->query("
            SELECT 
                ID,
                USER,
                HOST,
                DB,
                COMMAND,
                TIME,
                STATE,
                INFO
            FROM information_schema.PROCESSLIST
            ORDER BY TIME DESC
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        throw new Exception("Erreur lors de la récupération des connexions: " . $e->getMessage());
    }
}

/**
 * Tuer un processus MySQL
 */
function killProcess($processId) {
    try {
        $pdo = getMySQLAdminConnection();
        $processId = (int)$processId;
        $pdo->exec("KILL $processId");
        return ['success' => true, 'message' => "Processus $processId terminé"];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Obtenir les informations détaillées d'une vue
 */
function getViewDefinition($database, $viewName) {
    try {
        $pdo = getMySQLAdminConnection();
        $stmt = $pdo->prepare("
            SELECT VIEW_DEFINITION
            FROM information_schema.VIEWS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        ");
        $stmt->execute([$database, $viewName]);
        $view = $stmt->fetch();
        return $view ? $view['VIEW_DEFINITION'] : null;
    } catch (Exception $e) {
        throw new Exception("Erreur lors de la récupération de la vue: " . $e->getMessage());
    }
}

/**
 * Obtenir la définition d'une procédure stockée
 */
function getProcedureDefinition($database, $procedureName) {
    try {
        $pdo = getMySQLAdminConnection();
        $stmt = $pdo->prepare("
            SELECT ROUTINE_DEFINITION
            FROM information_schema.ROUTINES
            WHERE ROUTINE_SCHEMA = ? AND ROUTINE_NAME = ?
        ");
        $stmt->execute([$database, $procedureName]);
        $proc = $stmt->fetch();
        return $proc ? $proc['ROUTINE_DEFINITION'] : null;
    } catch (Exception $e) {
        throw new Exception("Erreur lors de la récupération de la procédure: " . $e->getMessage());
    }
}

/**
 * Obtenir la définition d'un trigger
 */
function getTriggerDefinition($database, $triggerName) {
    try {
        $pdo = getMySQLAdminConnection();
        $stmt = $pdo->prepare("
            SELECT 
                TRIGGER_NAME,
                EVENT_MANIPULATION,
                EVENT_OBJECT_TABLE,
                ACTION_STATEMENT,
                ACTION_TIMING,
                ACTION_ORIENTATION
            FROM information_schema.TRIGGERS
            WHERE TRIGGER_SCHEMA = ? AND TRIGGER_NAME = ?
        ");
        $stmt->execute([$database, $triggerName]);
        return $stmt->fetch();
    } catch (Exception $e) {
        throw new Exception("Erreur lors de la récupération du trigger: " . $e->getMessage());
    }
}

/**
 * Exporter une table en CSV
 */
function exportTableToCSV($database, $table) {
    try {
        $pdo = getMySQLAdminConnection();
        $pdo->exec("USE `" . str_replace('`', '``', $database) . "`");
        
        $stmt = $pdo->query("SELECT * FROM `" . str_replace('`', '``', $table) . "`");
        $rows = $stmt->fetchAll();
        
        if (empty($rows)) {
            return ['success' => false, 'error' => 'Table vide'];
        }
        
        $output = fopen('php://temp', 'r+');
        
        // En-têtes
        fputcsv($output, array_keys($rows[0]));
        
        // Données
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return ['success' => true, 'data' => $csv];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

?>

