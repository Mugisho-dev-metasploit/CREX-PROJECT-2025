<?php
/**
 * Envoi de réponse aux rendez-vous - CREx
 * Envoie un email ou prépare un lien WhatsApp pour répondre aux rendez-vous
 */

session_start();
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// Vérifier CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erreur de sécurité']);
    exit;
}

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    
    $appointmentId = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : ''; // confirm, cancel, complete
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $sendMethod = isset($_POST['send_method']) ? $_POST['send_method'] : 'email'; // email, whatsapp, both
    
    if (!$appointmentId) {
        echo json_encode(['success' => false, 'error' => 'ID de rendez-vous manquant']);
        exit;
    }
    
    // Récupérer les informations du rendez-vous
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        echo json_encode(['success' => false, 'error' => 'Rendez-vous introuvable']);
        exit;
    }
    
    // Labels des services
    $serviceLabels = [
        'consultation_medicale' => 'Consultation médicale',
        'kinesitherapie' => 'Kinésithérapie',
        'readaptation' => 'Réadaptation',
        'psychologie' => 'Psychologie clinique',
        'orthopedie' => 'Orthopédie et rééducation',
        'ergotherapie' => 'Ergothérapie',
        'developpement_enfant' => 'Développement de l\'enfant',
        'therapies_groupe' => 'Thérapies de groupe',
        'personnes_agees' => 'Accompagnement des personnes âgées',
        'autre' => 'Autre'
    ];
    
    $serviceLabel = $serviceLabels[$appointment['service_type']] ?? $appointment['service_type'];
    
    // Mettre à jour le statut du rendez-vous (sauf pour 'reply' qui ne change pas le statut)
    $statusLabel = '';
    if ($action === 'confirm') {
        $stmt = $pdo->prepare("UPDATE appointments SET statut = 'confirme', date_confirmation = NOW(), confirmed_by = ? WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'] ?? null, $appointmentId]);
        $statusLabel = 'Confirmé';
    } elseif ($action === 'cancel') {
        $stmt = $pdo->prepare("UPDATE appointments SET statut = 'annule' WHERE id = ?");
        $stmt->execute([$appointmentId]);
        $statusLabel = 'Annulé';
    } elseif ($action === 'complete') {
        $stmt = $pdo->prepare("UPDATE appointments SET statut = 'termine' WHERE id = ?");
        $stmt->execute([$appointmentId]);
        $statusLabel = 'Terminé';
    } elseif ($action === 'reply') {
        // Pour 'reply', on ne change pas le statut, juste envoyer un message
        $statusLabel = 'Réponse envoyée';
    } else {
        echo json_encode(['success' => false, 'error' => 'Action invalide']);
        exit;
    }
    
    $dateObj = DateTime::createFromFormat('Y-m-d', $appointment['date_souhaitee']);
    $dateFormatted = $dateObj ? $dateObj->format('d/m/Y') : $appointment['date_souhaitee'];
    
    // Préparer le message par défaut si aucun message personnalisé
    if (empty($message)) {
        if ($action === 'confirm') {
            $message = "Bonjour " . htmlspecialchars($appointment['nom'], ENT_QUOTES, 'UTF-8') . ",\n\n";
            $message .= "Nous avons le plaisir de vous confirmer votre rendez-vous pour :\n\n";
            $message .= "Service : " . $serviceLabel . "\n";
            $message .= "Date : " . $dateFormatted . "\n";
            $message .= "Heure : " . $appointment['heure_souhaitee'] . "\n\n";
            $message .= "Nous vous attendons au Centre CREx.\n\n";
            $message .= "Cordialement,\nL'équipe CREx";
        } elseif ($action === 'cancel') {
            $message = "Bonjour " . htmlspecialchars($appointment['nom'], ENT_QUOTES, 'UTF-8') . ",\n\n";
            $message .= "Nous sommes au regret de vous informer que votre rendez-vous prévu le " . $dateFormatted . " à " . $appointment['heure_souhaitee'] . " a été annulé.\n\n";
            $message .= "N'hésitez pas à nous contacter pour planifier un nouveau rendez-vous.\n\n";
            $message .= "Cordialement,\nL'équipe CREx";
        } elseif ($action === 'complete') {
            $message = "Bonjour " . htmlspecialchars($appointment['nom'], ENT_QUOTES, 'UTF-8') . ",\n\n";
            $message .= "Votre rendez-vous du " . $dateFormatted . " a été marqué comme terminé.\n\n";
            $message .= "Merci de votre confiance.\n\n";
            $message .= "Cordialement,\nL'équipe CREx";
        } else {
            $message = "Bonjour " . htmlspecialchars($appointment['nom'], ENT_QUOTES, 'UTF-8') . ",\n\n";
            $message .= "Concernant votre rendez-vous prévu le " . $dateFormatted . " à " . $appointment['heure_souhaitee'] . " pour " . $serviceLabel . ".\n\n";
            $message .= "Cordialement,\nL'équipe CREx";
        }
    }
    
    // Message pour WhatsApp (encoder correctement)
    $whatsappMessage = urlencode($message);
    
    $emailSent = false;
    $whatsappLink = '';
    
    // Envoyer par email si demandé
    if ($sendMethod === 'email' || $sendMethod === 'both') {
        $to = $appointment['email'];
        $subject = "CREx - Rendez-vous " . $statusLabel;
        
        // Préparer le corps de l'email
        $emailBody = $message;
        $emailBody .= "\n\n";
        $emailBody .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $emailBody .= "DÉTAILS DU RENDEZ-VOUS\n";
        $emailBody .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $emailBody .= "Service : " . $serviceLabel . "\n";
        $emailBody .= "Date souhaitée : " . $dateFormatted . "\n";
        $emailBody .= "Heure souhaitée : " . $appointment['heure_souhaitee'] . "\n";
        $emailBody .= "Statut : " . $statusLabel . "\n";
        $emailBody .= "\n";
        $emailBody .= "Centre CREx\n";
        $emailBody .= "Kinindo Ouest, Avenue Beraka N°30\n";
        $emailBody .= "Bujumbura, Burundi\n";
        $emailBody .= "Téléphone : +257 77 510 647 / +257 61 343 682\n";
        $emailBody .= "Email : crex.bdi@gmail.com\n";
        
        $headers = array();
        $headers[] = "From: CREx <crex.bdi@gmail.com>";
        $headers[] = "Reply-To: crex.bdi@gmail.com";
        $headers[] = "Content-Type: text/plain; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: 8bit";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        
        $emailSent = @mail($to, $subject, $emailBody, implode("\r\n", $headers));
    }
    
    // Préparer le lien WhatsApp si demandé
    if ($sendMethod === 'whatsapp' || $sendMethod === 'both') {
        $phone = preg_replace('/[^0-9]/', '', $appointment['telephone']);
        // Ajouter l'indicatif si nécessaire (Burundi: 257)
        // Vérifier si le numéro commence déjà par 257
        if (substr($phone, 0, 3) !== '257') {
            // Si le numéro fait plus de 9 chiffres, prendre les 9 derniers
            if (strlen($phone) > 9) {
                $phone = '257' . substr($phone, -9);
            } else {
                // Ajouter 257 au début
                $phone = '257' . $phone;
            }
        }
        $whatsappLink = "https://wa.me/" . $phone . "?text=" . $whatsappMessage;
    }
    
    // Sauvegarder le message dans les notes
    $currentNotes = $appointment['notes'] ?? '';
    $notePrefix = "\n\n[" . date('d/m/Y H:i') . "] Réponse envoyée";
    if ($statusLabel) {
        $notePrefix .= " (" . $statusLabel . ")";
    }
    $notePrefix .= ":\n" . $message;
    $stmt = $pdo->prepare("UPDATE appointments SET notes = CONCAT(COALESCE(notes, ''), ?) WHERE id = ?");
    $stmt->execute([$notePrefix, $appointmentId]);
    
    $response = [
        'success' => true,
        'status' => $statusLabel,
        'email_sent' => $emailSent,
        'whatsapp_link' => $whatsappLink,
        'message' => 'Rendez-vous mis à jour avec succès'
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur: ' . $e->getMessage()]);
}
?>

