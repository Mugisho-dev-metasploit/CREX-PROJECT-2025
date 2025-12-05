<?php
/**
 * Upload d'images pour la galerie - CREx
 */

session_start();
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

// Vérifier CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header('Location: admin.php?section=gallery&error=' . urlencode('Erreur de sécurité'));
    exit;
}

// Vérifier qu'un fichier a été uploadé
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    header('Location: admin.php?section=gallery&error=' . urlencode('Erreur lors de l\'upload du fichier'));
    exit;
}

$file = $_FILES['image'];

// Vérifier le type de fichier
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    header('Location: admin.php?section=gallery&error=' . urlencode('Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.'));
    exit;
}

// Vérifier la taille (max 5MB)
$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    header('Location: admin.php?section=gallery&error=' . urlencode('Fichier trop volumineux. Maximum 5MB.'));
    exit;
}

// Créer le dossier d'upload s'il n'existe pas
$uploadDir = 'assets/images/gallery/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Générer un nom de fichier unique
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('gallery_') . '_' . time() . '.' . $extension;
$filepath = $uploadDir . $filename;

// Déplacer le fichier uploadé
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    header('Location: admin.php?section=gallery&error=' . urlencode('Erreur lors du déplacement du fichier'));
    exit;
}

// Obtenir les dimensions de l'image
$imageInfo = getimagesize($filepath);
$width = $imageInfo[0] ?? null;
$height = $imageInfo[1] ?? null;

// Récupérer les données du formulaire
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? 'general');
$altText = trim($_POST['alt_text'] ?? '');

try {
    $pdo = getDBConnection();
    
    // Insérer dans la base de données
    $stmt = $pdo->prepare("INSERT INTO gallery (image_path, title, description, alt_text, category, file_size, width, height, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $filepath,
        $title ?: null,
        $description ?: null,
        $altText ?: null,
        $category,
        $file['size'],
        $width,
        $height,
        $_SESSION['user_id'] ?? null
    ]);
    
    header('Location: admin.php?section=gallery&success=' . urlencode('Image uploadée avec succès'));
    exit;
    
} catch (PDOException $e) {
    // Supprimer le fichier en cas d'erreur
    @unlink($filepath);
    header('Location: admin.php?section=gallery&error=' . urlencode('Erreur lors de la sauvegarde: ' . $e->getMessage()));
    exit;
}
?>

