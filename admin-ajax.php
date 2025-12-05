<?php
/**
 * Admin AJAX Handler - CREx
 * Gère les requêtes AJAX pour le panneau admin
 */

session_start();
require_once 'config.php';

// Vérifier l'authentification
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

// Initialiser le token CSRF si nécessaire
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'get_messages':
            // Récupérer les messages avec pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = 10;
            $offset = ($page - 1) * $perPage;
            $filter = $_GET['filter'] ?? 'all';
            $search = trim($_GET['search'] ?? '');

            $whereConditions = [];
            $params = [];

            if ($filter === 'unread') {
                $whereConditions[] = "lu = 0";
            } elseif ($filter === 'unreplied') {
                $whereConditions[] = "repondu = 0";
            }

            if (!empty($search)) {
                $whereConditions[] = "(nom LIKE :search1 OR email LIKE :search2 OR sujet LIKE :search3 OR message LIKE :search4)";
                $searchTerm = '%' . $search . '%';
                $params[':search1'] = $searchTerm;
                $params[':search2'] = $searchTerm;
                $params[':search3'] = $searchTerm;
                $params[':search4'] = $searchTerm;
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            // Compter le total
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages $whereClause");
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $totalMessages = $countStmt->fetchColumn();
            $totalPages = ceil($totalMessages / $perPage);

            // Récupérer les messages
            $messagesStmt = $pdo->prepare("
                SELECT * FROM contact_messages 
                $whereClause
                ORDER BY date_creation DESC 
                LIMIT :limit OFFSET :offset
            ");
            foreach ($params as $key => $value) {
                $messagesStmt->bindValue($key, $value);
            }
            $messagesStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $messagesStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $messagesStmt->execute();
            $messages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);

            // Générer le HTML
            ob_start();
            if (empty($messages)) {
                echo '<div class="empty-state"><h3>Aucun message trouvé</h3><p>Aucun message ne correspond à vos critères.</p></div>';
            } else {
                foreach ($messages as $message) {
                    $unreadClass = $message['lu'] == 0 ? 'unread' : '';
                    echo '<div class="message-item ' . $unreadClass . '" data-message-id="' . htmlspecialchars($message['id']) . '">';
                    echo '<div class="message-header">';
                    echo '<div class="message-info">';
                    echo '<h3 class="message-name">' . htmlspecialchars($message['nom']) . '</h3>';
                    echo '<a href="mailto:' . htmlspecialchars($message['email']) . '" class="message-email">' . htmlspecialchars($message['email']) . '</a>';
                    if (!empty($message['telephone'])) {
                        echo '<div class="message-contact mt-2"><i class="fas fa-phone"></i> <a href="tel:' . htmlspecialchars($message['telephone']) . '">' . htmlspecialchars($message['telephone']) . '</a></div>';
                    }
                    if (!empty($message['whatsapp'])) {
                        $whatsappNumber = preg_replace('/[^0-9]/', '', $message['whatsapp']);
                        echo '<div class="message-contact mt-2"><i class="fab fa-whatsapp"></i> <a href="https://wa.me/' . $whatsappNumber . '" target="_blank">' . htmlspecialchars($message['whatsapp']) . '</a></div>';
                    }
                    echo '<div class="message-date mt-2"><i class="fas fa-calendar"></i> ' . date('d/m/Y à H:i:s', strtotime($message['date_creation']));
                    if ($message['ip_address']) {
                        echo ' • IP: ' . htmlspecialchars($message['ip_address']);
                    }
                    echo '</div>';
                    echo '</div>';
                    echo '<div class="message-badges">';
                    if ($message['lu'] == 0) {
                        echo '<span class="badge unread">Non lu</span>';
                    }
                    if ($message['repondu'] == 1) {
                        echo '<span class="badge replied">Répondu</span>';
                    } else {
                        echo '<span class="badge not-replied">Non répondu</span>';
                    }
                    echo '</div>';
                    echo '</div>';
                    if (!empty($message['sujet'])) {
                        echo '<div class="message-subject mt-2"><strong><i class="fas fa-tag"></i> Objet:</strong> ' . htmlspecialchars($message['sujet']) . '</div>';
                    }
                    echo '<div class="message-text mt-3">' . nl2br(htmlspecialchars($message['message'])) . '</div>';
                    echo '<div class="message-actions mt-3">';
                    // Actions buttons
                    echo '</div>';
                    echo '</div>';
                }
            }
            $html = ob_get_clean();

            // Pagination
            $pagination = [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalMessages' => $totalMessages,
                'startPage' => max(1, $page - 2),
                'endPage' => min($totalPages, $page + 2)
            ];

            echo json_encode([
                'success' => true,
                'html' => $html,
                'pagination' => $pagination
            ]);
            break;

        case 'get_message':
            $messageId = (int)($_GET['message_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = ?");
            $stmt->execute([$messageId]);
            $message = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($message) {
                echo json_encode(['success' => true, 'message' => $message]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Message non trouvé']);
            }
            break;

        case 'mark_read':
        case 'mark_unread':
        case 'mark_replied':
        case 'delete':
            // Vérifier CSRF token
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
                exit;
            }

            $messageId = (int)($_POST['message_id'] ?? 0);
            $messageText = '';

            switch ($action) {
                case 'mark_read':
                    $stmt = $pdo->prepare("UPDATE contact_messages SET lu = 1 WHERE id = ?");
                    $messageText = 'Message marqué comme lu';
                    break;
                case 'mark_unread':
                    $stmt = $pdo->prepare("UPDATE contact_messages SET lu = 0 WHERE id = ?");
                    $messageText = 'Message marqué comme non lu';
                    break;
                case 'mark_replied':
                    $stmt = $pdo->prepare("UPDATE contact_messages SET repondu = 1 WHERE id = ?");
                    $messageText = 'Message marqué comme répondu';
                    break;
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
                    $messageText = 'Message supprimé';
                    break;
            }

            $stmt->execute([$messageId]);
            echo json_encode(['success' => true, 'message' => $messageText]);
            break;

        case 'get_service':
            // Récupérer un service par ID
            $serviceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($serviceId) {
                $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
                $stmt->execute([$serviceId]);
                $service = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($service) {
                    echo json_encode(['success' => true, 'service' => $service]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Service introuvable']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'ID manquant']);
            }
            break;
            
        case 'get_blog':
            // Récupérer un article de blog par ID
            $blogId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($blogId) {
                $stmt = $pdo->prepare("SELECT * FROM blog WHERE id = ?");
                $stmt->execute([$blogId]);
                $post = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($post) {
                    echo json_encode(['success' => true, 'post' => $post]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Article introuvable']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'ID manquant']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}

