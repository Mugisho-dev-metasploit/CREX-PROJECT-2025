<?php
/**
 * Déconnexion Admin - CREx
 * Version simplifiée
 */

session_start();

// Détruire toutes les variables de session
$_SESSION = array();

// Si une session cookie existe, le supprimer
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion
header('Location: admin-login.php');
exit;
