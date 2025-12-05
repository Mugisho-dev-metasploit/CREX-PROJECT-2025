<?php
// ============================================
// Appointment Form Backend - CREx
// Traitement des demandes de rendez-vous
// ============================================

// Inclure la configuration de la base de données
require_once 'config.php';

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: appointment.html?status=error&message=' . urlencode('Méthode non autorisée.'));
    exit;
}

// Configuration
$TO_EMAIL = 'mugishomerci123@gmail.com'; // Email de l'administrateur
$MAX_NAME_LENGTH = 100;
$MAX_EMAIL_LENGTH = 150;
$MAX_PHONE_LENGTH = 20;
$MAX_MESSAGE_LENGTH = 1000;

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
 * Valide un numéro de téléphone
 */
function validatePhone($phone) {
    if (empty($phone)) {
        return false;
    }
    // Nettoyer le numéro (supprimer espaces, tirets, etc.)
    $phone = preg_replace('/[^0-9+\s()\-]/', '', $phone);
    if (strlen($phone) < 8 || strlen($phone) > 20) {
        return false;
    }
    return true;
}

/**
 * Valide une date
 */
function validateDate($date) {
    if (empty($date)) {
        return false;
    }
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj) {
        return false;
    }
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    $dateObj->setTime(0, 0, 0);
    if ($dateObj < $today) {
        return false; // Date dans le passé
    }
    return true;
}

/**
 * Valide une heure
 */
function validateTime($time) {
    if (empty($time)) {
        return false;
    }
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
        return false;
    }
    $hours = (int)substr($time, 0, 2);
    if ($hours < 8 || $hours > 19) {
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
    $lastSubmission = isset($_SESSION['last_appointment_submission']) ? $_SESSION['last_appointment_submission'] : 0;
    $currentTime = time();
    $minInterval = 60; // 60 secondes entre deux soumissions
    
    if ($currentTime - $lastSubmission < $minInterval) {
        return false;
    }
    
    $_SESSION['last_appointment_submission'] = $currentTime;
    return true;
}

// ============================================
// Traitement du formulaire
// ============================================

$nom = isset($_POST['nom']) ? clean($_POST['nom']) : '';
$telephone = isset($_POST['telephone']) ? clean($_POST['telephone']) : '';
$email = isset($_POST['email']) ? clean($_POST['email']) : '';
$service_type = isset($_POST['service_type']) ? clean($_POST['service_type']) : '';
$date_souhaitee = isset($_POST['date_souhaitee']) ? clean($_POST['date_souhaitee']) : '';
$heure_souhaitee = isset($_POST['heure_souhaitee']) ? clean($_POST['heure_souhaitee']) : '';
$message = isset($_POST['message']) ? clean($_POST['message']) : '';

// Nouveaux champs
$date_naissance = isset($_POST['date_naissance']) ? clean($_POST['date_naissance']) : '';
$genre = isset($_POST['genre']) ? clean($_POST['genre']) : 'non_specifie';
$nationalite = isset($_POST['nationalite']) ? clean($_POST['nationalite']) : '';
$profession = isset($_POST['profession']) ? clean($_POST['profession']) : '';
$adresse_complete = isset($_POST['adresse_complete']) ? clean($_POST['adresse_complete']) : '';
$code_postal = isset($_POST['code_postal']) ? clean($_POST['code_postal']) : '';
$ville = isset($_POST['ville']) ? clean($_POST['ville']) : '';
$pays = isset($_POST['pays']) ? clean($_POST['pays']) : 'Burundi';
$assurance_sante = isset($_POST['assurance_sante']) ? clean($_POST['assurance_sante']) : '';
$motif_consultation = isset($_POST['motif_consultation']) ? clean($_POST['motif_consultation']) : '';

$errors = array();

// Protection anti-spam : honeypot
if (!checkHoneypot()) {
    header('Location: appointment.html?status=success&message=' . urlencode('Merci, votre demande de rendez-vous a été envoyée.'));
    exit;
}

// Vérification du taux de requêtes
if (!checkRateLimit()) {
    $errors[] = 'Veuillez patienter quelques instants avant de renvoyer une demande.';
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

// Validation du téléphone
if (!validatePhone($telephone)) {
    if (empty($telephone)) {
        $errors[] = 'Le numéro de téléphone est obligatoire.';
    } else {
        $errors[] = 'Le numéro de téléphone n\'est pas valide.';
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

// Validation de l'adresse
if (empty($adresse_complete)) {
    $errors[] = 'L\'adresse complète est obligatoire.';
}

// Validation de la ville
if (empty($ville)) {
    $errors[] = 'La ville est obligatoire.';
}

// Validation du pays
if (empty($pays)) {
    $errors[] = 'Le pays est obligatoire.';
}

// Validation du type de service
$validServices = [
    'consultation_medicale',
    'kinesitherapie',
    'readaptation',
    'psychologie',
    'orthopedie',
    'ergotherapie',
    'developpement_enfant',
    'therapies_groupe',
    'personnes_agees',
    'autre'
];
if (empty($service_type) || !in_array($service_type, $validServices)) {
    $errors[] = 'Veuillez sélectionner un type de service valide.';
}

// Validation du genre
$validGenres = ['homme', 'femme', 'autre', 'non_specifie'];
if (!in_array($genre, $validGenres)) {
    $genre = 'non_specifie';
}

// Validation de la date de naissance (si fournie)
if (!empty($date_naissance)) {
    $dateNaissanceObj = DateTime::createFromFormat('Y-m-d', $date_naissance);
    if (!$dateNaissanceObj) {
        $errors[] = 'La date de naissance n\'est pas valide.';
    } else {
        $today = new DateTime();
        if ($dateNaissanceObj > $today) {
            $errors[] = 'La date de naissance ne peut pas être dans le futur.';
        }
    }
}

// Validation de la date
if (!validateDate($date_souhaitee)) {
    if (empty($date_souhaitee)) {
        $errors[] = 'La date souhaitée est obligatoire.';
    } else {
        $errors[] = 'La date sélectionnée n\'est pas valide ou est dans le passé.';
    }
}

// Validation de l'heure
if (!validateTime($heure_souhaitee)) {
    if (empty($heure_souhaitee)) {
        $errors[] = 'L\'heure souhaitée est obligatoire.';
    } else {
        $errors[] = 'L\'heure sélectionnée n\'est pas valide. Les rendez-vous sont disponibles entre 08h00 et 19h00.';
    }
}

// Validation du message (facultatif)
if (!empty($message)) {
    if (strlen($message) > $MAX_MESSAGE_LENGTH) {
        $errors[] = 'Le message ne doit pas dépasser ' . $MAX_MESSAGE_LENGTH . ' caractères.';
    }
}

// Si des erreurs existent
if (!empty($errors)) {
    $errorMessage = urlencode(implode(' ', $errors));
    header('Location: appointment.html?status=error&message=' . $errorMessage);
    exit;
}

// Nettoyer le téléphone
$telephone = preg_replace('/[^0-9+\s()\-]/', '', $telephone);

// ============================================
// Sauvegarde dans la base de données
// ============================================

$appointmentId = null;
try {
    $pdo = getDBConnection();
    
    // Préparer la requête SQL avec des paramètres liés (protection contre les injections SQL)
    $stmt = $pdo->prepare("
        INSERT INTO appointments (
            nom, telephone, email, service_type, date_souhaitee, heure_souhaitee, message,
            adresse_complete, code_postal, ville, pays, nationalite, date_naissance, genre,
            profession, assurance_sante, motif_consultation, ip_address
        ) 
        VALUES (
            :nom, :telephone, :email, :service_type, :date_souhaitee, :heure_souhaitee, :message,
            :adresse_complete, :code_postal, :ville, :pays, :nationalite, :date_naissance, :genre,
            :profession, :assurance_sante, :motif_consultation, :ip_address
        )
    ");
    
    // Récupérer l'adresse IP
    $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    
    // Exécuter la requête avec les valeurs
    $stmt->execute([
        ':nom' => $nom,
        ':telephone' => $telephone,
        ':email' => $email,
        ':service_type' => $service_type,
        ':date_souhaitee' => $date_souhaitee,
        ':heure_souhaitee' => $heure_souhaitee,
        ':message' => !empty($message) ? $message : null,
        ':adresse_complete' => !empty($adresse_complete) ? $adresse_complete : null,
        ':code_postal' => !empty($code_postal) ? $code_postal : null,
        ':ville' => !empty($ville) ? $ville : null,
        ':pays' => !empty($pays) ? $pays : 'Burundi',
        ':nationalite' => !empty($nationalite) ? $nationalite : null,
        ':date_naissance' => !empty($date_naissance) ? $date_naissance : null,
        ':genre' => $genre,
        ':profession' => !empty($profession) ? $profession : null,
        ':assurance_sante' => !empty($assurance_sante) ? $assurance_sante : null,
        ':motif_consultation' => !empty($motif_consultation) ? $motif_consultation : null,
        ':ip_address' => $ip_address
    ]);
    
    $appointmentId = $pdo->lastInsertId();
    
} catch (PDOException $e) {
    // Log l'erreur mais continue quand même l'envoi d'email
    error_log("Erreur lors de la sauvegarde du rendez-vous en BDD: " . $e->getMessage());
    // Ne pas bloquer l'envoi d'email si la BDD échoue
}

// ============================================
// Préparation et envoi de l'email
// ============================================

// Mapper les types de service pour l'affichage
$serviceLabels = [
    'kinesitherapie' => 'Kinésithérapie',
    'readaptation' => 'Réadaptation',
    'psychologie' => 'Accompagnement psychologique',
    'consultation_medicale' => 'Consultation médicale',
    'orthopedie' => 'Orthopédie et rééducation fonctionnelle',
    'ergotherapie' => 'Ergothérapie',
    'developpement_enfant' => 'Développement de l\'enfant',
    'therapies_groupe' => 'Thérapies de groupe',
    'personnes_agees' => 'Accompagnement des personnes âgées',
    'autre' => 'Autre'
];

$serviceLabel = isset($serviceLabels[$service_type]) ? $serviceLabels[$service_type] : $service_type;

// Formater la date en français
$dateFormatted = '';
if (!empty($date_souhaitee)) {
    $dateObj = DateTime::createFromFormat('Y-m-d', $date_souhaitee);
    if ($dateObj) {
        $dateFormatted = $dateObj->format('d/m/Y');
    }
}

$emailSubject = 'Nouvelle demande de rendez-vous - CREx';

$emailBody = "Bonjour,\n\n";
$emailBody .= "Vous avez reçu une nouvelle demande de rendez-vous depuis le site CREx.\n\n";
$emailBody .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
$emailBody .= "INFORMATIONS DU PATIENT :\n\n";
$emailBody .= "Nom complet : " . $nom . "\n";
$emailBody .= "Téléphone : " . $telephone . "\n";
$emailBody .= "E-mail : " . $email . "\n";
if (!empty($date_naissance)) {
    $dateNaissFormatted = DateTime::createFromFormat('Y-m-d', $date_naissance);
    if ($dateNaissFormatted) {
        $emailBody .= "Date de naissance : " . $dateNaissFormatted->format('d/m/Y') . "\n";
    }
}
if ($genre !== 'non_specifie') {
    $genreLabels = ['homme' => 'Homme', 'femme' => 'Femme', 'autre' => 'Autre'];
    $emailBody .= "Genre : " . (isset($genreLabels[$genre]) ? $genreLabels[$genre] : $genre) . "\n";
}
if (!empty($nationalite)) {
    $emailBody .= "Nationalité : " . $nationalite . "\n";
}
if (!empty($profession)) {
    $emailBody .= "Profession : " . $profession . "\n";
}
if (!empty($assurance_sante)) {
    $emailBody .= "Assurance santé : " . $assurance_sante . "\n";
}
$emailBody .= "\n";
$emailBody .= "ADRESSE :\n";
$emailBody .= "Adresse complète : " . $adresse_complete . "\n";
if (!empty($code_postal)) {
    $emailBody .= "Code postal : " . $code_postal . "\n";
}
$emailBody .= "Ville : " . $ville . "\n";
$emailBody .= "Pays : " . $pays . "\n";
$emailBody .= "\n";
$emailBody .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
$emailBody .= "DÉTAILS DU RENDEZ-VOUS :\n\n";
$emailBody .= "Type de service : " . $serviceLabel . "\n";
$emailBody .= "Date souhaitée : " . $dateFormatted . "\n";
$emailBody .= "Heure souhaitée : " . $heure_souhaitee . "\n\n";
if (!empty($motif_consultation)) {
    $emailBody .= "Motif de consultation :\n";
    $emailBody .= $motif_consultation . "\n\n";
}
if (!empty($message)) {
    $emailBody .= "Message/Commentaires :\n";
    $emailBody .= $message . "\n\n";
}
$emailBody .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
$emailBody .= "Date de la demande : " . date('d/m/Y à H:i:s') . "\n";
$emailBody .= "Adresse IP : " . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Non disponible') . "\n";
if ($appointmentId) {
    $emailBody .= "ID de la demande : #" . $appointmentId . "\n";
}

$headers = array();
$headers[] = "From: " . $email;
$headers[] = "Reply-To: " . $email;
$headers[] = "Content-Type: text/plain; charset=UTF-8";
$headers[] = "Content-Transfer-Encoding: 8bit";
$headers[] = "X-Mailer: PHP/" . phpversion();
$headers[] = "X-Priority: 1"; // Priorité haute pour les rendez-vous
$headersString = implode("\r\n", $headers);

$mailSent = @mail($TO_EMAIL, $emailSubject, $emailBody, $headersString);

// Redirection avec message de succès si l'email est envoyé OU si le rendez-vous est sauvegardé en BDD
if ($mailSent || $appointmentId) {
    $successMessage = urlencode('Votre demande de rendez-vous a bien été envoyée. Nous vous contacterons dans les plus brefs délais pour confirmer votre rendez-vous.');
    header('Location: appointment.html?status=success&message=' . $successMessage);
    exit;
} else {
    $errorMessage = urlencode('Une erreur est survenue lors de l\'envoi de votre demande. Veuillez réessayer plus tard ou nous contacter directement par téléphone.');
    header('Location: appointment.html?status=error&message=' . $errorMessage);
    exit;
}
?>

