<?php
/**
 * Interface d'Administration MySQL Complète - CREx
 * Gestion complète de la base de données depuis l'interface web
 */

session_start();
require_once 'config.php';
require_once 'includes/database-admin-functions.php';

// Vérifier l'authentification
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

// Vérifier les privilèges (seuls super_admin et admin peuvent accéder)
if (isset($_SESSION['user_role']) && !in_array($_SESSION['user_role'], ['super_admin', 'admin'])) {
    header('Location: admin.php');
    exit;
}

// Vérifier les privilèges MySQL
$hasAdminPrivileges = hasDatabaseAdminPrivileges();
if (!$hasAdminPrivileges) {
    $warning = "Attention: Votre compte MySQL n'a pas tous les privilèges nécessaires. Certaines fonctionnalités peuvent être limitées.";
}

// Récupérer la base de données actuelle
$currentDatabase = $_GET['database'] ?? DB_NAME;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration MySQL - CREx</title>
    <script>
        // Inline theme initialization to prevent FOUC
        (function(){
            const t = localStorage.getItem('crex-theme') || 
                     (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            const h = document.documentElement;
            h.classList.remove('dark-mode', 'light-mode');
            h.setAttribute('data-theme', t);
            h.classList.add(t === 'dark' ? 'dark-mode' : 'light-mode');
        })();
    </script>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CodeMirror pour l'éditeur SQL -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/eclipse.min.css">
    
    <!-- Theme Variables -->
    <link rel="stylesheet" href="assets/css/theme-variables.css">
    
    <style>
        /* ============================================
           BASE STYLES
           ============================================ */
        body {
            font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* ============================================
           HEADER
           ============================================ */
        .admin-header {
            background: var(--gradient-primary);
            color: var(--text-inverse);
            padding: 1.5rem 0;
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }
        
        .admin-header h1 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-inverse);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .admin-header h1 i {
            font-size: 1.5rem;
            opacity: 0.9;
        }
        
        .admin-header .btn-light,
        .admin-header .btn-outline-light {
            background-color: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            color: var(--text-inverse);
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }
        
        .admin-header .btn-light:hover,
        .admin-header .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
            color: var(--text-inverse);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        /* ============================================
           SIDEBAR
           ============================================ */
        .sidebar {
            background: var(--bg-secondary);
            min-height: calc(100vh - 80px);
            box-shadow: var(--shadow-md);
            padding: 1.5rem 0;
            border-right: 1px solid var(--border-color);
            position: sticky;
            top: 80px;
            height: calc(100vh - 80px);
            overflow-y: auto;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: var(--bg-primary);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: var(--color-primary);
        }
        
        .sidebar .nav-link {
            color: var(--text-secondary);
            padding: 0.875rem 1.5rem;
            border-left: 3px solid transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin: 0.25rem 0.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover {
            background-color: var(--bg-hover);
            border-left-color: var(--color-primary);
            color: var(--color-primary);
            transform: translateX(4px);
        }
        
        .sidebar .nav-link.active {
            background: linear-gradient(90deg, var(--bg-hover) 0%, transparent 100%);
            border-left-color: var(--color-primary);
            color: var(--color-primary);
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(74, 176, 217, 0.15);
        }
        
        .sidebar .nav-link i {
            width: 22px;
            margin-right: 12px;
            font-size: 1.1rem;
            text-align: center;
        }
        
        /* ============================================
           MAIN CONTENT
           ============================================ */
        .main-content {
            padding: 2rem;
            background-color: var(--bg-primary);
            min-height: calc(100vh - 80px);
        }
        
        /* ============================================
           CARDS
           ============================================ */
        .card {
            border: 1px solid var(--border-color);
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            background-color: var(--card-bg);
            color: var(--text-primary);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }
        
        .card-header {
            background: var(--gradient-card);
            border-bottom: 2px solid var(--color-primary);
            font-weight: 600;
            padding: 1.25rem 1.5rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .card-header i {
            color: var(--color-primary);
            font-size: 1.2rem;
        }
        
        .card-body {
            background-color: var(--card-bg);
            color: var(--text-primary);
            padding: 1.5rem;
        }
        
        /* ============================================
           BUTTONS
           ============================================ */
        .btn {
            border-radius: 8px;
            padding: 0.625rem 1.25rem;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
        }
        
        .btn i {
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: var(--button-primary-text);
            box-shadow: 0 2px 8px rgba(74, 176, 217, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(74, 176, 217, 0.4);
            filter: brightness(1.05);
        }
        
        .btn-success {
            background-color: var(--color-success);
            border-color: var(--color-success);
            color: var(--text-inverse);
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }
        
        .btn-success:hover {
            background-color: var(--color-success-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
        }
        
        .btn-danger {
            background-color: var(--color-danger);
            border-color: var(--color-danger);
            color: var(--text-inverse);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }
        
        .btn-danger:hover {
            background-color: var(--color-danger-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }
        
        .btn-info {
            background-color: var(--color-info);
            border-color: var(--color-info);
            color: var(--text-inverse);
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
        }
        
        .btn-info:hover {
            background-color: var(--color-info-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4);
        }
        
        .btn-secondary {
            background-color: var(--button-secondary-bg);
            border-color: var(--button-secondary-bg);
            color: var(--button-secondary-text);
        }
        
        .btn-secondary:hover {
            background-color: var(--button-secondary-hover);
            border-color: var(--button-secondary-hover);
            transform: translateY(-2px);
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        /* ============================================
           TABLES
           ============================================ */
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }
        
        .table {
            background-color: var(--table-bg);
            color: var(--text-primary);
            margin-bottom: 0;
        }
        
        .table thead {
            background: var(--gradient-primary);
            color: var(--table-header-text);
        }
        
        .table thead th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            border: none;
        }
        
        .table tbody {
            background-color: var(--table-bg);
        }
        
        .table tbody tr {
            border-bottom: 1px solid var(--table-border);
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: var(--table-row-hover);
            transform: scale(1.01);
        }
        
        .table td {
            padding: 1rem;
            color: var(--text-primary);
            border-color: var(--table-border);
            vertical-align: middle;
        }
        
        .table th {
            border-color: var(--table-border);
        }
        
        /* ============================================
           FORMS
           ============================================ */
        .form-control,
        .form-select {
            background-color: var(--input-bg);
            border: 2px solid var(--input-border);
            color: var(--input-text);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus,
        .form-select:focus {
            background-color: var(--input-bg);
            border-color: var(--input-focus-border);
            color: var(--input-text);
            box-shadow: 0 0 0 0.2rem rgba(74, 176, 217, 0.25);
            outline: none;
        }
        
        .form-control::placeholder {
            color: var(--input-placeholder);
            opacity: 0.7;
        }
        
        .form-label {
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        /* ============================================
           SQL EDITOR
           ============================================ */
        .sql-editor {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            min-height: 300px;
            background-color: var(--code-bg);
            overflow: hidden;
        }
        
        .sql-editor .CodeMirror {
            background-color: var(--code-bg) !important;
            color: var(--code-text) !important;
            height: 100%;
            font-family: 'Courier New', monospace;
        }
        
        .sql-editor .CodeMirror-gutters {
            background-color: var(--bg-secondary) !important;
            border-right: 1px solid var(--border-color) !important;
        }
        
        .sql-editor .CodeMirror-linenumber {
            color: var(--text-muted) !important;
        }
        
        .sql-editor .CodeMirror-cursor {
            border-left: 2px solid var(--color-primary) !important;
        }
        
        /* Override CodeMirror themes pour garantir la lisibilité */
        html.dark-mode .sql-editor .CodeMirror,
        html[data-theme="dark"] .sql-editor .CodeMirror {
            background-color: var(--code-bg) !important;
            color: var(--code-text) !important;
        }
        
        html.light-mode .sql-editor .CodeMirror,
        html[data-theme="light"] .sql-editor .CodeMirror {
            background-color: var(--code-bg) !important;
            color: var(--code-text) !important;
        }
        
        /* ============================================
           RESULT TABLE
           ============================================ */
        .result-table {
            max-height: 500px;
            overflow-y: auto;
            background-color: var(--table-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        
        .result-table::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        .result-table::-webkit-scrollbar-track {
            background: var(--bg-primary);
        }
        
        .result-table::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }
        
        .result-table::-webkit-scrollbar-thumb:hover {
            background: var(--color-primary);
        }
        
        /* ============================================
           BADGES
           ============================================ */
        .badge-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.875rem;
        }
        
        .privilege-badge {
            display: inline-block;
            padding: 0.375rem 0.875rem;
            margin: 0.25rem;
            background-color: var(--bg-hover);
            border-radius: 6px;
            font-size: 0.875rem;
            color: var(--text-secondary);
            transition: all 0.2s ease;
        }
        
        .privilege-badge:hover {
            transform: scale(1.05);
        }
        
        .privilege-badge.active {
            background-color: var(--color-success-light);
            color: var(--color-success-dark);
            font-weight: 600;
        }
        
        html.dark-mode .privilege-badge.active {
            background-color: rgba(40, 167, 69, 0.25);
            color: var(--color-success);
            border: 1px solid var(--color-success);
        }
        
        /* ============================================
           LOADING
           ============================================ */
        .loading {
            display: none;
        }
        
        .loading.active {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }
        
        /* ============================================
           ALERTS
           ============================================ */
        .alert {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 12px;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: var(--shadow-sm);
        }
        
        .alert i {
            font-size: 1.25rem;
        }
        
        .alert-success {
            background-color: var(--color-success-light);
            border-color: var(--color-success);
            color: var(--color-success-dark);
        }
        
        html.dark-mode .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            color: var(--color-success);
            border-color: var(--color-success);
        }
        
        .alert-danger {
            background-color: var(--color-danger-light);
            border-color: var(--color-danger);
            color: var(--color-danger-dark);
        }
        
        html.dark-mode .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            color: var(--color-danger);
            border-color: var(--color-danger);
        }
        
        .alert-warning {
            background-color: var(--color-warning-light);
            border-color: var(--color-warning);
            color: var(--color-warning-dark);
        }
        
        html.dark-mode .alert-warning {
            background-color: rgba(255, 193, 7, 0.2);
            color: var(--color-warning);
            border-color: var(--color-warning);
        }
        
        .alert-info {
            background-color: var(--color-info-light);
            border-color: var(--color-info);
            color: var(--color-info-dark);
        }
        
        html.dark-mode .alert-info {
            background-color: rgba(23, 162, 184, 0.2);
            color: var(--color-info);
            border-color: var(--color-info);
        }
        
        .alert-custom {
            border-left: 4px solid;
            border-radius: 8px;
        }
        
        /* ============================================
           TABS
           ============================================ */
        .tab-content {
            padding: 1.5rem 0;
        }
        
        .nav-tabs {
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 1.5rem;
        }
        
        .nav-tabs .nav-link {
            color: var(--text-secondary);
            border: none;
            border-bottom: 3px solid transparent;
            background-color: transparent;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--color-primary);
            border-bottom-color: var(--border-color);
            background-color: var(--bg-hover);
        }
        
        .nav-tabs .nav-link.active {
            color: var(--color-primary);
            border-bottom-color: var(--color-primary);
            background: transparent;
            font-weight: 600;
        }
        
        /* ============================================
           DATABASE SELECTOR
           ============================================ */
        .database-selector {
            max-width: 300px;
        }
        
        /* ============================================
           STATS CARD
           ============================================ */
        .stats-card {
            text-align: center;
            padding: 2rem 1.5rem;
            background: var(--gradient-card);
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .stats-card .number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--color-primary);
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .stats-card .label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* ============================================
           MODALS
           ============================================ */
        .modal-content {
            background-color: var(--modal-bg);
            color: var(--modal-text);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: var(--shadow-xl);
        }
        
        .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem;
        }
        
        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 1.5rem;
        }
        
        .modal-body {
            padding: 1.5rem;
            color: var(--text-primary);
        }
        
        .modal-title {
            color: var(--text-primary);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .modal-title i {
            color: var(--color-primary);
        }
        
        .btn-close {
            filter: brightness(0) invert(0.5);
            opacity: 0.7;
            transition: all 0.2s ease;
        }
        
        .btn-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }
        
        html.dark-mode .btn-close,
        html[data-theme="dark"] .btn-close {
            filter: brightness(0) invert(1);
        }
        
        /* Modal header danger */
        .modal-header[style*="background-color: var(--color-danger)"] {
            background-color: var(--color-danger) !important;
        }
        
        html.dark-mode .modal-header[style*="background-color: var(--color-danger)"],
        html[data-theme="dark"] .modal-header[style*="background-color: var(--color-danger)"] {
            background-color: var(--color-danger) !important;
        }
        
        /* ============================================
           CODE BLOCKS
           ============================================ */
        pre {
            background-color: var(--code-bg);
            color: var(--code-text);
            border: 1px solid var(--code-border);
            border-radius: 8px;
            padding: 1.5rem;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            line-height: 1.6;
        }
        
        pre::-webkit-scrollbar {
            height: 8px;
        }
        
        pre::-webkit-scrollbar-track {
            background: var(--bg-primary);
        }
        
        pre::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }
        
        /* ============================================
           UTILITY CLASSES
           ============================================ */
        .text-muted {
            color: var(--text-muted) !important;
        }
        
        .text-secondary {
            color: var(--text-secondary) !important;
        }
        
        /* Override Bootstrap classes pour le thème */
        html.dark-mode .text-dark,
        html[data-theme="dark"] .text-dark {
            color: var(--text-primary) !important;
        }
        
        html.dark-mode .bg-light,
        html[data-theme="dark"] .bg-light {
            background-color: var(--bg-secondary) !important;
        }
        
        html.dark-mode .bg-white,
        html[data-theme="dark"] .bg-white {
            background-color: var(--bg-secondary) !important;
        }
        
        html.dark-mode .text-light,
        html[data-theme="dark"] .text-light {
            color: var(--text-primary) !important;
        }
        
        /* Spinner colors */
        .spinner-border {
            border-color: var(--color-primary);
            border-right-color: transparent;
        }
        
        html.dark-mode .spinner-border,
        html[data-theme="dark"] .spinner-border {
            border-color: var(--color-primary);
            border-right-color: transparent;
        }
        
        /* Visually hidden */
        .visually-hidden {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                top: 0;
                height: auto;
                min-height: auto;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .admin-header h1 {
                font-size: 1.5rem;
            }
            
            .card-body {
                padding: 1rem;
            }
        }
        
        /* ============================================
           ANIMATIONS
           ============================================ */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .tab-pane {
            animation: fadeIn 0.3s ease;
        }
        
        /* ============================================
           FOCUS STATES
           ============================================ */
        *:focus-visible {
            outline: 2px solid var(--color-primary);
            outline-offset: 2px;
            border-radius: 4px;
        }
        
        /* ============================================
           ADDITIONAL THEME FIXES
           ============================================ */
        /* Assurer que tous les textes sont lisibles */
        html.dark-mode,
        html[data-theme="dark"] {
            /* Override pour les éléments qui pourraient avoir des couleurs codées en dur */
        }
        
        html.dark-mode p,
        html[data-theme="dark"] p,
        html.dark-mode span,
        html[data-theme="dark"] span,
        html.dark-mode div,
        html[data-theme="dark"] div {
            color: inherit;
        }
        
        /* Tables - garantir la lisibilité */
        html.dark-mode .table td,
        html[data-theme="dark"] .table td,
        html.dark-mode .table th,
        html[data-theme="dark"] .table th {
            color: var(--text-primary) !important;
        }
        
        /* Form labels */
        html.dark-mode label,
        html[data-theme="dark"] label {
            color: var(--text-primary) !important;
        }
        
        /* Select options */
        html.dark-mode select option,
        html[data-theme="dark"] select option {
            background-color: var(--input-bg);
            color: var(--input-text);
        }
        
        /* Modals - garantir la lisibilité */
        html.dark-mode .modal-body *,
        html[data-theme="dark"] .modal-body * {
            color: inherit;
        }
        
        /* Cards - garantir la lisibilité */
        html.dark-mode .card *,
        html[data-theme="dark"] .card * {
            color: inherit;
        }
        
        /* Alerts - améliorer le contraste */
        html.dark-mode .alert,
        html[data-theme="dark"] .alert {
            border-width: 2px;
        }
        
        /* Transitions fluides */
        html.dark-mode *,
        html[data-theme="dark"] *,
        html.light-mode *,
        html[data-theme="light"] * {
            transition: background-color 0.3s ease, 
                        color 0.3s ease, 
                        border-color 0.3s ease,
                        box-shadow 0.3s ease;
        }
        
        /* Exclure les éléments qui ne doivent pas transitionner */
        html.dark-mode img,
        html.dark-mode svg,
        html.dark-mode video,
        html[data-theme="dark"] img,
        html[data-theme="dark"] svg,
        html[data-theme="dark"] video {
            transition: none !important;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="admin-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-database"></i> Administration MySQL</h1>
                </div>
                <div class="col-md-6 text-end">
                    <a href="admin.php" class="btn btn-light btn-sm me-2">
                        <i class="fas fa-arrow-left"></i> Retour au Dashboard
                    </a>
                    <a href="admin-logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link active" href="#" data-tab="databases">
                        <i class="fas fa-database"></i> Bases de données
                    </a>
                    <a class="nav-link" href="#" data-tab="tables">
                        <i class="fas fa-table"></i> Tables
                    </a>
                    <a class="nav-link" href="#" data-tab="sql">
                        <i class="fas fa-code"></i> SQL Query
                    </a>
                    <a class="nav-link" href="#" data-tab="users">
                        <i class="fas fa-users"></i> Utilisateurs
                    </a>
                    <a class="nav-link" href="#" data-tab="privileges">
                        <i class="fas fa-key"></i> Privilèges
                    </a>
                    <a class="nav-link" href="#" data-tab="import-export">
                        <i class="fas fa-file-import"></i> Import/Export
                    </a>
                    <a class="nav-link" href="#" data-tab="indexes">
                        <i class="fas fa-list"></i> Index
                    </a>
                    <a class="nav-link" href="#" data-tab="views">
                        <i class="fas fa-eye"></i> Vues
                    </a>
                    <a class="nav-link" href="#" data-tab="procedures">
                        <i class="fas fa-cogs"></i> Procédures
                    </a>
                    <a class="nav-link" href="#" data-tab="triggers">
                        <i class="fas fa-bolt"></i> Triggers
                    </a>
                    <a class="nav-link" href="#" data-tab="monitoring">
                        <i class="fas fa-chart-line"></i> Monitoring
                    </a>
                    <a class="nav-link" href="#" data-tab="logs">
                        <i class="fas fa-file-alt"></i> Logs
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <?php if (isset($warning)): ?>
                    <div class="alert alert-warning alert-custom">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($warning); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Database Selector -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <label class="form-label"><strong>Base de données actuelle:</strong></label>
                                <select class="form-select database-selector" id="databaseSelector">
                                    <option value="<?php echo htmlspecialchars(DB_NAME); ?>"><?php echo htmlspecialchars(DB_NAME); ?></option>
                                </select>
                            </div>
                            <div class="col-md-8 text-end">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDatabaseModal">
                                    <i class="fas fa-plus"></i> Créer une base
                                </button>
                                <button class="btn btn-success" id="refreshDatabases">
                                    <i class="fas fa-sync-alt"></i> Actualiser
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Content -->
                <div id="tabContent">
                    <!-- Databases Tab -->
                    <div class="tab-pane active" id="databases-tab">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-database"></i> Bases de données
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="databasesTable">
                                        <thead>
                                            <tr>
                                                <th>Nom</th>
                                                <th>Tables</th>
                                                <th>Taille</th>
                                                <th>Encodage</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="5" class="text-center">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Chargement...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tables Tab -->
                    <div class="tab-pane" id="tables-tab" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-table"></i> Tables
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTableModal">
                                        <i class="fas fa-plus"></i> Créer une table
                                    </button>
                                    <button class="btn btn-success" id="refreshTables">
                                        <i class="fas fa-sync-alt"></i> Actualiser
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="tablesTable">
                                        <thead>
                                            <tr>
                                                <th>Nom</th>
                                                <th>Type</th>
                                                <th>Lignes</th>
                                                <th>Taille</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="5" class="text-center">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Chargement...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SQL Query Tab -->
                    <div class="tab-pane" id="sql-tab" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-code"></i> Exécuter une requête SQL
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <textarea class="form-control sql-editor" id="sqlQuery" rows="10" placeholder="Entrez votre requête SQL ici..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <button class="btn btn-primary" id="executeQuery">
                                        <i class="fas fa-play"></i> Exécuter
                                    </button>
                                    <button class="btn btn-info" id="explainQuery">
                                        <i class="fas fa-search"></i> EXPLAIN
                                    </button>
                                    <button class="btn btn-secondary" id="clearQuery">
                                        <i class="fas fa-eraser"></i> Effacer
                                    </button>
                                    <span class="loading ms-3" id="queryLoading">
                                        <div class="spinner-border spinner-border-sm" role="status"></div>
                                        Exécution en cours...
                                    </span>
                                </div>
                                <div id="queryResult"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Users Tab -->
                    <div class="tab-pane" id="users-tab" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-users"></i> Utilisateurs MySQL
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                                        <i class="fas fa-user-plus"></i> Créer un utilisateur
                                    </button>
                                    <button class="btn btn-success" id="refreshUsers">
                                        <i class="fas fa-sync-alt"></i> Actualiser
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="usersTable">
                                        <thead>
                                            <tr>
                                                <th>Utilisateur</th>
                                                <th>Host</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="3" class="text-center">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Chargement...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Privileges Tab -->
                    <div class="tab-pane" id="privileges-tab" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-key"></i> Gestion des Privilèges
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Utilisateur</label>
                                        <select class="form-select" id="privilegeUser">
                                            <option value="">Sélectionner un utilisateur</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Host</label>
                                        <input type="text" class="form-control" id="privilegeHost" value="%">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">&nbsp;</label>
                                        <button class="btn btn-primary w-100" id="loadPrivileges">
                                            <i class="fas fa-search"></i> Charger les privilèges
                                        </button>
                                    </div>
                                </div>
                                <div id="privilegesContent"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Import/Export Tab -->
                    <div class="tab-pane" id="import-export-tab" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fas fa-file-upload"></i> Importer
                                    </div>
                                    <div class="card-body">
                                        <ul class="nav nav-tabs" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" data-bs-toggle="tab" href="#import-sql">SQL</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="tab" href="#import-csv">CSV</a>
                                            </li>
                                        </ul>
                                        <div class="tab-content mt-3">
                                            <div class="tab-pane active" id="import-sql">
                                                <div class="mb-3">
                                                    <label class="form-label">Fichier SQL</label>
                                                    <input type="file" class="form-control" id="sqlFile" accept=".sql">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Ou coller le contenu SQL</label>
                                                    <textarea class="form-control" id="sqlContent" rows="10"></textarea>
                                                </div>
                                                <button class="btn btn-primary" id="importSQL">
                                                    <i class="fas fa-upload"></i> Importer SQL
                                                </button>
                                            </div>
                                            <div class="tab-pane" id="import-csv">
                                                <div class="mb-3">
                                                    <label class="form-label">Table cible</label>
                                                    <select class="form-select" id="csvTable">
                                                        <option value="">Sélectionner une table</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Fichier CSV</label>
                                                    <input type="file" class="form-control" id="csvFile" accept=".csv">
                                                </div>
                                                <button class="btn btn-primary" id="importCSV">
                                                    <i class="fas fa-upload"></i> Importer CSV
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fas fa-file-download"></i> Exporter
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Format</label>
                                            <select class="form-select" id="exportFormat">
                                                <option value="sql">SQL (Dump complet)</option>
                                                <option value="csv">CSV (Table sélectionnée)</option>
                                            </select>
                                        </div>
                                        <div class="mb-3" id="exportTableSelect" style="display: none;">
                                            <label class="form-label">Table</label>
                                            <select class="form-select" id="exportTable">
                                                <option value="">Sélectionner une table</option>
                                            </select>
                                        </div>
                                        <button class="btn btn-success" id="exportData">
                                            <i class="fas fa-download"></i> Exporter
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Indexes Tab -->
                    <div class="tab-pane" id="indexes-tab" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-list"></i> Gestion des Index
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Table</label>
                                        <select class="form-select" id="indexTable">
                                            <option value="">Sélectionner une table</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8 text-end">
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createIndexModal">
                                            <i class="fas fa-plus"></i> Créer un index
                                        </button>
                                    </div>
                                </div>
                                <div id="indexesContent"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Views Tab -->
                    <div class="tab-pane" id="views-tab" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-eye"></i> Vues
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="viewsTable">
                                        <thead>
                                            <tr>
                                                <th>Nom</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="2" class="text-center">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Chargement...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Procedures Tab -->
                    <div class="tab-pane" id="procedures-tab" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-cogs"></i> Procédures Stockées
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="proceduresTable">
                                        <thead>
                                            <tr>
                                                <th>Nom</th>
                                                <th>Type</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="3" class="text-center">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Chargement...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Triggers Tab -->
                    <div class="tab-pane" id="triggers-tab" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-bolt"></i> Triggers
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="triggersTable">
                                        <thead>
                                            <tr>
                                                <th>Nom</th>
                                                <th>Table</th>
                                                <th>Événement</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="4" class="text-center">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Chargement...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Monitoring Tab -->
                    <div class="tab-pane" id="monitoring-tab" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fas fa-chart-line"></i> Statut MySQL
                                    </div>
                                    <div class="card-body">
                                        <div id="mysqlStatus"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fas fa-plug"></i> Connexions Actives
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm" id="connectionsTable">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>User</th>
                                                        <th>Host</th>
                                                        <th>DB</th>
                                                        <th>Time</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td colspan="6" class="text-center">
                                                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <button class="btn btn-sm btn-success mt-2" id="refreshConnections">
                                            <i class="fas fa-sync-alt"></i> Actualiser
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Logs Tab -->
                    <div class="tab-pane" id="logs-tab" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-file-alt"></i> Logs d'Erreur MySQL
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <button class="btn btn-success" id="refreshLogs">
                                        <i class="fas fa-sync-alt"></i> Actualiser
                                    </button>
                                </div>
                                <pre id="errorLogs" style="max-height: 500px; overflow-y: auto; background-color: var(--code-bg); color: var(--code-text); border: 1px solid var(--code-border); border-radius: 8px; padding: 1.5rem; font-family: 'Courier New', monospace; line-height: 1.6;">
                                    <div class="text-center" style="color: var(--text-primary);">
                                        <div class="spinner-border" role="status"></div>
                                        Chargement des logs...
                                    </div>
                                </pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modals -->
    <?php include 'includes/database-admin-modals.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/sql/sql.min.js"></script>
    <script src="dark-mode.js"></script>
    <script>
        // S'assurer que CodeMirror s'adapte au thème même si l'événement est déjà émis
        document.addEventListener('DOMContentLoaded', function() {
            // Écouter les changements de thème
            document.addEventListener('themeChanged', function(e) {
                if (window.codeMirrorEditor) {
                    const newTheme = e.detail?.theme || 
                                   (document.documentElement.getAttribute('data-theme') || 
                                    (document.documentElement.classList.contains('dark-mode') ? 'dark' : 'light'));
                    const newCodeMirrorTheme = newTheme === 'dark' ? 'monokai' : 'eclipse';
                    window.codeMirrorEditor.setOption('theme', newCodeMirrorTheme);
                }
            });
            
            // Vérifier le thème au chargement
            setTimeout(function() {
                if (window.codeMirrorEditor) {
                    const currentTheme = document.documentElement.getAttribute('data-theme') || 
                                       (document.documentElement.classList.contains('dark-mode') ? 'dark' : 'light');
                    const codeMirrorTheme = currentTheme === 'dark' ? 'monokai' : 'eclipse';
                    window.codeMirrorEditor.setOption('theme', codeMirrorTheme);
                }
            }, 500);
        });
    </script>
    <script src="js/admin-database.js"></script>
</body>
</html>

