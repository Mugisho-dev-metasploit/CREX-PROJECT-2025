<?php
/**
 * Gestion AJAX pour l'administration de la base de données
 * CREx - Interface d'administration MySQL
 */

session_start();
require_once 'config.php';
require_once 'includes/database-admin-functions.php';

// Vérifier l'authentification
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// Vérifier les privilèges (optionnel, selon votre système de permissions)
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'super_admin' && $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Privilèges insuffisants']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list_databases':
            $databases = listDatabases();
            echo json_encode(['success' => true, 'data' => array_values($databases)]);
            break;
            
        case 'list_tables':
            $database = $_POST['database'] ?? DB_NAME;
            $tables = listTables($database);
            echo json_encode(['success' => true, 'data' => $tables]);
            break;
            
        case 'get_table_info':
            $database = $_POST['database'] ?? DB_NAME;
            $table = $_POST['table'] ?? '';
            if (empty($table)) {
                throw new Exception('Table non spécifiée');
            }
            $info = getTableInfo($database, $table);
            echo json_encode(['success' => true, 'data' => $info]);
            break;
            
        case 'execute_query':
            $database = $_POST['database'] ?? DB_NAME;
            $query = $_POST['query'] ?? '';
            if (empty($query)) {
                throw new Exception('Requête vide');
            }
            $limit = (int)($_POST['limit'] ?? 1000);
            $result = executeSQLQuery($database, $query, $limit);
            echo json_encode($result);
            break;
            
        case 'list_users':
            $users = listMySQLUsers();
            echo json_encode(['success' => true, 'data' => $users]);
            break;
            
        case 'get_user_privileges':
            $user = $_POST['user'] ?? '';
            $host = $_POST['host'] ?? '%';
            if (empty($user)) {
                throw new Exception('Utilisateur non spécifié');
            }
            $privileges = getUserPrivileges($user, $host);
            echo json_encode(['success' => true, 'data' => $privileges]);
            break;
            
        case 'create_database':
            $name = $_POST['name'] ?? '';
            $charset = $_POST['charset'] ?? 'utf8mb4';
            $collation = $_POST['collation'] ?? 'utf8mb4_unicode_ci';
            if (empty($name)) {
                throw new Exception('Nom de base de données requis');
            }
            $result = createDatabase($name, $charset, $collation);
            echo json_encode($result);
            break;
            
        case 'drop_database':
            $name = $_POST['name'] ?? '';
            if (empty($name)) {
                throw new Exception('Nom de base de données requis');
            }
            // Protection: ne pas permettre la suppression de la base principale
            if ($name === DB_NAME) {
                throw new Exception('Impossible de supprimer la base de données principale');
            }
            $result = dropDatabase($name);
            echo json_encode($result);
            break;
            
        case 'create_user':
            $username = $_POST['username'] ?? '';
            $host = $_POST['host'] ?? '%';
            $password = $_POST['password'] ?? '';
            if (empty($username) || empty($password)) {
                throw new Exception('Nom d\'utilisateur et mot de passe requis');
            }
            $result = createMySQLUser($username, $host, $password);
            echo json_encode($result);
            break;
            
        case 'grant_privileges':
            $username = $_POST['username'] ?? '';
            $host = $_POST['host'] ?? '%';
            $database = $_POST['database'] ?? '';
            $privileges = $_POST['privileges'] ?? [];
            $table = $_POST['table'] ?? '*';
            if (empty($username) || empty($database) || empty($privileges)) {
                throw new Exception('Paramètres incomplets');
            }
            $result = grantPrivileges($username, $host, $database, $privileges, $table);
            echo json_encode($result);
            break;
            
        case 'revoke_privileges':
            $username = $_POST['username'] ?? '';
            $host = $_POST['host'] ?? '%';
            $database = $_POST['database'] ?? '';
            $privileges = $_POST['privileges'] ?? [];
            $table = $_POST['table'] ?? '*';
            if (empty($username) || empty($database) || empty($privileges)) {
                throw new Exception('Paramètres incomplets');
            }
            $result = revokePrivileges($username, $host, $database, $privileges, $table);
            echo json_encode($result);
            break;
            
        case 'drop_user':
            $username = $_POST['username'] ?? '';
            $host = $_POST['host'] ?? '%';
            if (empty($username)) {
                throw new Exception('Nom d\'utilisateur requis');
            }
            // Protection: ne pas permettre la suppression de l'utilisateur principal
            if ($username === DB_USER && $host === '%') {
                throw new Exception('Impossible de supprimer l\'utilisateur principal');
            }
            $result = dropMySQLUser($username, $host);
            echo json_encode($result);
            break;
            
        case 'export_database':
            $database = $_POST['database'] ?? DB_NAME;
            $tables = isset($_POST['tables']) ? json_decode($_POST['tables'], true) : null;
            $result = exportDatabase($database, $tables);
            if ($result['success']) {
                header('Content-Type: application/sql');
                header('Content-Disposition: attachment; filename="' . $database . '_' . date('Y-m-d_His') . '.sql"');
                echo $result['data'];
            } else {
                echo json_encode($result);
            }
            break;
            
        case 'get_processes':
            $processes = getMySQLProcesses();
            echo json_encode(['success' => true, 'data' => $processes]);
            break;
            
        case 'get_variables':
            $variables = getMySQLVariables();
            echo json_encode(['success' => true, 'data' => $variables]);
            break;
            
        case 'get_status':
            $status = getMySQLStatus();
            echo json_encode(['success' => true, 'data' => $status]);
            break;
            
        case 'list_views':
            $database = $_POST['database'] ?? DB_NAME;
            $views = listViews($database);
            echo json_encode(['success' => true, 'data' => $views]);
            break;
            
        case 'list_procedures':
            $database = $_POST['database'] ?? DB_NAME;
            $procedures = listStoredProcedures($database);
            echo json_encode(['success' => true, 'data' => $procedures]);
            break;
            
        case 'list_triggers':
            $database = $_POST['database'] ?? DB_NAME;
            $triggers = listTriggers($database);
            echo json_encode(['success' => true, 'data' => $triggers]);
            break;
            
        case 'explain_query':
            $database = $_POST['database'] ?? DB_NAME;
            $query = $_POST['query'] ?? '';
            if (empty($query)) {
                throw new Exception('Requête vide');
            }
            $explain = explainQuery($database, $query);
            echo json_encode(['success' => true, 'data' => $explain]);
            break;
            
        case 'import_sql':
            $database = $_POST['database'] ?? DB_NAME;
            $sqlContent = $_POST['sql_content'] ?? '';
            if (empty($sqlContent)) {
                throw new Exception('Contenu SQL vide');
            }
            $result = importSQL($database, $sqlContent);
            echo json_encode($result);
            break;
            
        case 'import_csv':
            $database = $_POST['database'] ?? DB_NAME;
            $table = $_POST['table'] ?? '';
            $csvContent = $_POST['csv_content'] ?? '';
            $delimiter = $_POST['delimiter'] ?? ',';
            $enclosure = $_POST['enclosure'] ?? '"';
            if (empty($table) || empty($csvContent)) {
                throw new Exception('Table ou contenu CSV manquant');
            }
            $result = importCSV($database, $table, $csvContent, $delimiter, $enclosure);
            echo json_encode($result);
            break;
            
        case 'create_index':
            $database = $_POST['database'] ?? DB_NAME;
            $table = $_POST['table'] ?? '';
            $indexName = $_POST['index_name'] ?? '';
            $columns = isset($_POST['columns']) ? json_decode($_POST['columns'], true) : [];
            $indexType = $_POST['index_type'] ?? 'INDEX';
            if (empty($table) || empty($indexName) || empty($columns)) {
                throw new Exception('Paramètres incomplets');
            }
            $result = createIndex($database, $table, $indexName, $columns, $indexType);
            echo json_encode($result);
            break;
            
        case 'drop_index':
            $database = $_POST['database'] ?? DB_NAME;
            $table = $_POST['table'] ?? '';
            $indexName = $_POST['index_name'] ?? '';
            if (empty($table) || empty($indexName)) {
                throw new Exception('Table ou nom d\'index manquant');
            }
            $result = dropIndex($database, $table, $indexName);
            echo json_encode($result);
            break;
            
        case 'get_error_logs':
            $logs = getMySQLErrorLogs();
            echo json_encode(['success' => true, 'data' => $logs]);
            break;
            
        case 'get_active_connections':
            $connections = getActiveConnections();
            echo json_encode(['success' => true, 'data' => $connections]);
            break;
            
        case 'kill_process':
            $processId = $_POST['process_id'] ?? '';
            if (empty($processId)) {
                throw new Exception('ID de processus manquant');
            }
            $result = killProcess($processId);
            echo json_encode($result);
            break;
            
        case 'get_view_definition':
            $database = $_POST['database'] ?? DB_NAME;
            $viewName = $_POST['view_name'] ?? '';
            if (empty($viewName)) {
                throw new Exception('Nom de vue manquant');
            }
            $definition = getViewDefinition($database, $viewName);
            echo json_encode(['success' => true, 'data' => $definition]);
            break;
            
        case 'get_procedure_definition':
            $database = $_POST['database'] ?? DB_NAME;
            $procedureName = $_POST['procedure_name'] ?? '';
            if (empty($procedureName)) {
                throw new Exception('Nom de procédure manquant');
            }
            $definition = getProcedureDefinition($database, $procedureName);
            echo json_encode(['success' => true, 'data' => $definition]);
            break;
            
        case 'get_trigger_definition':
            $database = $_POST['database'] ?? DB_NAME;
            $triggerName = $_POST['trigger_name'] ?? '';
            if (empty($triggerName)) {
                throw new Exception('Nom de trigger manquant');
            }
            $definition = getTriggerDefinition($database, $triggerName);
            echo json_encode(['success' => true, 'data' => $definition]);
            break;
            
        case 'export_table_csv':
            $database = $_POST['database'] ?? DB_NAME;
            $table = $_POST['table'] ?? '';
            if (empty($table)) {
                throw new Exception('Table manquante');
            }
            $result = exportTableToCSV($database, $table);
            if ($result['success']) {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $table . '_' . date('Y-m-d_His') . '.csv"');
                echo $result['data'];
            } else {
                echo json_encode($result);
            }
            break;
            
        case 'create_table':
            $database = $_POST['database'] ?? DB_NAME;
            $tableName = $_POST['table_name'] ?? '';
            $columns = isset($_POST['columns']) ? json_decode($_POST['columns'], true) : [];
            if (empty($tableName) || empty($columns)) {
                throw new Exception('Nom de table ou colonnes manquants');
            }
            // Construire la requête CREATE TABLE
            $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (";
            $sqlParts = [];
            foreach ($columns as $col) {
                $sqlParts[] = "`{$col['name']}` {$col['type']}" . 
                             (!empty($col['length']) ? "({$col['length']})" : '') .
                             ($col['null'] ? ' NULL' : ' NOT NULL') .
                             (!empty($col['default']) ? " DEFAULT '{$col['default']}'" : '') .
                             ($col['auto_increment'] ? ' AUTO_INCREMENT' : '') .
                             (!empty($col['primary']) ? ' PRIMARY KEY' : '');
            }
            $sql .= implode(', ', $sqlParts) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $result = executeSQLQuery($database, $sql);
            echo json_encode($result);
            break;
            
        case 'drop_table':
            $database = $_POST['database'] ?? DB_NAME;
            $tableName = $_POST['table_name'] ?? '';
            if (empty($tableName)) {
                throw new Exception('Nom de table manquant');
            }
            // Protection: ne pas permettre la suppression de tables système importantes
            $protectedTables = ['users', 'admin_users'];
            if (in_array($tableName, $protectedTables) && $database === DB_NAME) {
                throw new Exception('Impossible de supprimer cette table système');
            }
            $sql = "DROP TABLE IF EXISTS `$tableName`";
            $result = executeSQLQuery($database, $sql);
            echo json_encode($result);
            break;
            
        case 'alter_table':
            $database = $_POST['database'] ?? DB_NAME;
            $tableName = $_POST['table_name'] ?? '';
            $alterStatement = $_POST['alter_statement'] ?? '';
            if (empty($tableName) || empty($alterStatement)) {
                throw new Exception('Paramètres incomplets');
            }
            $sql = "ALTER TABLE `$tableName` $alterStatement";
            $result = executeSQLQuery($database, $sql);
            echo json_encode($result);
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>

