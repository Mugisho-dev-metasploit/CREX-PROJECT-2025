<?php
// ============================================
// Contact Form Backend - CREx
// Version améliorée avec sécurité renforcée
// ============================================

// Inclure la configuration de la base de données
require_once 'config.php';

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.html?status=error&message=' . urlencode('Méthode non autorisée.'));
    exit;
}

// Configuration
$TO_EMAIL = 'mugishomerci123@gmail.com';
$MAX_NAME_LENGTH = 100;
$MAX_EMAIL_LENGTH = 150;
$MAX_SUBJECT_LENGTH = 200;
$MAX_MESSAGE_LENGTH = 2000;
$MIN_MESSAGE_LENGTH = 10;

// ============================================
// Fonctions de sécurité et validation
// ============================================

/**
 * Nettoie et sécurise une valeur d'entrée
 */
function clean($val) {
    if (!isset($val)) {
        return '';
    }
    $val = trim($val);
    $val = strip_tags($val);
    $val = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
    return $val;
}

/**
 * Valide le format d'un email
 */
function validateEmail($email) {
    if (empty($email)) {
        return false;
    }
    if (strlen($email) > 150) {
        return false;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    if (preg_match('/[\r\n]/', $email)) {
        return false;
    }
    return true;
}

/**
 * Valide un nom
 */
function validateName($name) {
    if (empty($name)) {
        return false;
    }
    if (strlen($name) < 2 || strlen($name) > 100) {
        return false;
    }
    if (preg_match('/[\r\n<>]/', $name)) {
        return false;
    }
    return true;
}

/**
 * Protection anti-spam : vérification du champ honeypot
 */
function checkHoneypot() {
    if (isset($_POST['website']) && !empty($_POST['website'])) {
        return false; // Bot détecté
    }
    return true;
}

/**
 * Vérification du taux de requêtes (protection contre le spam)
 */
function checkRateLimit() {
    session_start();
    $lastSubmission = isset($_SESSION['last_form_submission']) ? $_SESSION['last_form_submission'] : 0;
    $currentTime = time();
    $minInterval = 30; // 30 secondes entre deux soumissions
    
    if ($currentTime - $lastSubmission < $minInterval) {
        return false;
    }
    
    $_SESSION['last_form_submission'] = $currentTime;
    return true;
}

// ============================================
// Traitement du formulaire
// ============================================

$nom = isset($_POST['nom']) ? clean($_POST['nom']) : '';
$email = isset($_POST['email']) ? clean($_POST['email']) : '';
$telephone = isset($_POST['telephone']) ? clean($_POST['telephone']) : '';
$whatsapp = isset($_POST['whatsapp']) ? clean($_POST['whatsapp']) : '';
$sujet = isset($_POST['sujet']) ? clean($_POST['sujet']) : '';
$message = isset($_POST['message']) ? clean($_POST['message']) : '';

$errors = array();

// Protection anti-spam : honeypot
if (!checkHoneypot()) {
    header('Location: contact.html?status=success&message=' . urlencode('Merci, votre message a été envoyé.'));
    exit;
}

// Vérification du taux de requêtes
if (!checkRateLimit()) {
    $errors[] = 'Veuillez patienter quelques instants avant de renvoyer un message.';
}

// Validation du nom
if (!validateName($nom)) {
    if (empty($nom)) {
        $errors[] = 'Le nom complet est obligatoire.';
    } elseif (strlen($nom) < 2) {
        $errors[] = 'Le nom doit contenir au moins 2 caractères.';
    } elseif (strlen($nom) > $MAX_NAME_LENGTH) {
        $errors[] = 'Le nom ne doit pas dépasser ' . $MAX_NAME_LENGTH . ' caractères.';
    } else {
        $errors[] = 'Le nom contient des caractères non autorisés.';
    }
}

// Validation de l'email
if (!validateEmail($email)) {
    if (empty($email)) {
        $errors[] = 'L\'adresse e-mail est obligatoire.';
    } elseif (strlen($email) > $MAX_EMAIL_LENGTH) {
        $errors[] = 'L\'adresse e-mail ne doit pas dépasser ' . $MAX_EMAIL_LENGTH . ' caractères.';
    } else {
        $errors[] = 'L\'adresse e-mail n\'est pas valide.';
    }
}

// Validation du téléphone (facultatif)
if (!empty($telephone)) {
    // Nettoyer le numéro (supprimer espaces, tirets, etc.)
    $telephone = preg_replace('/[^0-9+\s()\-]/', '', $telephone);
    if (strlen($telephone) > 20) {
        $errors[] = 'Le numéro de téléphone ne doit pas dépasser 20 caractères.';
    }
}

// Validation du WhatsApp (facultatif)
if (!empty($whatsapp)) {
    // Nettoyer le numéro (supprimer espaces, tirets, etc.)
    $whatsapp = preg_replace('/[^0-9+\s()\-]/', '', $whatsapp);
    if (strlen($whatsapp) > 20) {
        $errors[] = 'Le numéro WhatsApp ne doit pas dépasser 20 caractères.';
    }
}

// Validation du sujet (facultatif)
if (!empty($sujet)) {
    if (strlen($sujet) > $MAX_SUBJECT_LENGTH) {
        $errors[] = 'L\'objet ne doit pas dépasser ' . $MAX_SUBJECT_LENGTH . ' caractères.';
    }
    if (preg_match('/[\r\n<>]/', $sujet)) {
        $errors[] = 'L\'objet contient des caractères non autorisés.';
    }
}

// Validation du message
if (empty($message)) {
    $errors[] = 'Le message est obligatoire.';
} elseif (strlen($message) < $MIN_MESSAGE_LENGTH) {
    $errors[] = 'Le message doit contenir au moins ' . $MIN_MESSAGE_LENGTH . ' caractères.';
} elseif (strlen($message) > $MAX_MESSAGE_LENGTH) {
    $errors[] = 'Le message ne doit pas dépasser ' . $MAX_MESSAGE_LENGTH . ' caractères.';
}

// Si des erreurs existent
if (!empty($errors)) {
    $errorMessage = urlencode(implode(' ', $errors));
    header('Location: contact.html?status=error&message=' . $errorMessage);
    exit;
}

// ============================================
// Sauvegarde dans la base de données
// ============================================

$messageId = null;
try {
    $pdo = getDBConnection();
    
    // Préparer la requête SQL avec des paramètres liés (protection contre les injections SQL)
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages (nom, email, telephone, whatsapp, sujet, message, ip_address) 
        VALUES (:nom, :email, :telephone, :whatsapp, :sujet, :message, :ip_address)
    ");
    
    // Récupérer l'adresse IP
    $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    
    // Exécuter la requête avec les valeurs
    $stmt->execute([
        ':nom' => $nom,
        ':email' => $email,
        ':telephone' => !empty($telephone) ? $telephone : null,
        ':whatsapp' => !empty($whatsapp) ? $whatsapp : null,
        ':sujet' => !empty($sujet) ? $sujet : null,
        ':message' => $message,
        ':ip_address' => $ip_address
    ]);
    
    $messageId = $pdo->lastInsertId();
    
} catch (PDOException $e) {
    // Log l'erreur mais continue quand même l'envoi d'email
    error_log("Erreur lors de la sauvegarde en BDD: " . $e->getMessage());
    // Ne pas bloquer l'envoi d'email si la BDD échoue
}

// ============================================
// Préparation et envoi de l'email
// ============================================

$emailSubject = 'Nouveau message depuis le site CREx';
if (!empty($sujet)) {
    $emailSubject .= ' - ' . $sujet;
}

$emailBody = "Bonjour,\n\n";
$emailBody .= "Vous avez reçu un nouveau message depuis le formulaire de contact du site CREx.\n\n";
$emailBody .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
$emailBody .= "NOM COMPLET :\n";
$emailBody .= $nom . "\n\n";
$emailBody .= "ADRESSE E-MAIL :\n";
$emailBody .= $email . "\n\n";
if (!empty($telephone)) {
    $emailBody .= "TÉLÉPHONE :\n";
    $emailBody .= $telephone . "\n\n";
}
if (!empty($whatsapp)) {
    $emailBody .= "WHATSAPP :\n";
    $emailBody .= $whatsapp . "\n\n";
}
if (!empty($sujet)) {
    $emailBody .= "OBJET :\n";
    $emailBody .= $sujet . "\n\n";
}
$emailBody .= "MESSAGE :\n";
$emailBody .= $message . "\n\n";
$emailBody .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
$emailBody .= "Date de réception : " . date('d/m/Y à H:i:s') . "\n";
$emailBody .= "Adresse IP : " . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Non disponible') . "\n";

$headers = array();
$headers[] = "From: " . $email;
$headers[] = "Reply-To: " . $email;
$headers[] = "Content-Type: text/plain; charset=UTF-8";
$headers[] = "Content-Transfer-Encoding: 8bit";
$headers[] = "X-Mailer: PHP/" . phpversion();
$headers[] = "X-Priority: 3";
$headersString = implode("\r\n", $headers);

$mailSent = @mail($TO_EMAIL, $emailSubject, $emailBody, $headersString);

// Redirection avec message de succès si l'email est envoyé OU si le message est sauvegardé en BDD
if ($mailSent || $messageId) {
    $successMessage = urlencode('Merci, votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.');
    header('Location: contact.html?status=success&message=' . $successMessage);
    exit;
} else {
    $errorMessage = urlencode('Une erreur est survenue lors de l\'envoi de votre message. Veuillez réessayer plus tard ou nous contacter directement par téléphone.');
    header('Location: contact.html?status=error&message=' . $errorMessage);
    exit;
}
?>
