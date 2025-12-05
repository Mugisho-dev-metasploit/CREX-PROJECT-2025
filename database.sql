-- ============================================
-- Script de création COMPLET de la base de données CREx
-- Version corrigée et améliorée - Toutes les tables en un seul fichier
-- ============================================
-- 
-- INSTRUCTIONS D'INSTALLATION :
-- 1. Ouvrez phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Cliquez sur l'onglet "SQL"
-- 3. Copiez-collez tout le contenu de ce fichier
-- 4. Cliquez sur "Exécuter"
-- 
-- OU utilisez le script install-database.php dans votre navigateur
-- ============================================

-- Supprimer la base de données si elle existe (optionnel, pour réinstallation propre)
-- DÉCOMMENTEZ LA LIGNE SUIVANTE SEULEMENT SI VOUS VOULEZ TOUT RECOMMENCER À ZÉRO
-- DROP DATABASE IF EXISTS crex_db;

-- ============================================
-- CRÉATION DE LA BASE DE DONNÉES
-- ============================================
CREATE DATABASE IF NOT EXISTS crex_db 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE crex_db;

-- Désactiver temporairement les vérifications de clés étrangères pour éviter les erreurs
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- SECTION 1: GESTION DES UTILISATEURS
-- ============================================

-- TABLE: users (système amélioré avec rôles)
-- Gestion des utilisateurs avec rôles (admin, editor, visitor)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    username VARCHAR(50) NOT NULL UNIQUE COMMENT 'Nom d''utilisateur (unique)',
    password VARCHAR(255) NOT NULL COMMENT 'Mot de passe hashé avec bcrypt',
    role ENUM('admin', 'editor', 'visitor') DEFAULT 'visitor' COMMENT 'Rôle: admin, editor, visitor',
    email VARCHAR(150) DEFAULT NULL COMMENT 'Email de l''utilisateur',
    phone VARCHAR(20) DEFAULT NULL COMMENT 'Téléphone',
    avatar VARCHAR(500) DEFAULT NULL COMMENT 'URL de l''avatar',
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    last_login DATETIME DEFAULT NULL COMMENT 'Dernière connexion',
    active TINYINT(1) DEFAULT 1 COMMENT 'Compte actif',
    email_verified TINYINT(1) DEFAULT 0 COMMENT 'Email vérifié',
    verification_token VARCHAR(100) DEFAULT NULL COMMENT 'Token de vérification email',
    
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_active (active),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: admin_users (système multi-admin avec rôles et permissions)
-- Stocke les comptes administrateurs pour la gestion du site
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique de l''administrateur',
    username VARCHAR(50) NOT NULL UNIQUE COMMENT 'Nom d''utilisateur (unique)',
    password_hash VARCHAR(255) NOT NULL COMMENT 'Mot de passe hashé avec password_hash()',
    email VARCHAR(150) NOT NULL UNIQUE COMMENT 'Email de l''administrateur (unique)',
    nom_complet VARCHAR(100) DEFAULT NULL COMMENT 'Nom complet de l''administrateur',
    role ENUM('super_admin', 'admin', 'editor', 'moderator') DEFAULT 'admin' COMMENT 'Rôle: super_admin, admin, editor, moderator',
    permissions TEXT DEFAULT NULL COMMENT 'Permissions JSON (pour permissions personnalisées)',
    actif TINYINT(1) DEFAULT 1 COMMENT 'Compte actif : 0 = désactivé, 1 = actif',
    email_verified TINYINT(1) DEFAULT 0 COMMENT 'Email vérifié : 0 = non, 1 = oui',
    verification_token VARCHAR(100) DEFAULT NULL COMMENT 'Token de vérification email',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création du compte',
    derniere_connexion DATETIME DEFAULT NULL COMMENT 'Date de dernière connexion',
    cree_par INT DEFAULT NULL COMMENT 'ID de l''admin qui a créé ce compte',
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_actif (actif),
    INDEX idx_role (role),
    FOREIGN KEY (cree_par) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: user_sessions
-- Gestion des sessions utilisateurs
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    user_id INT DEFAULT NULL COMMENT 'ID de l''utilisateur',
    session_token VARCHAR(255) NOT NULL UNIQUE COMMENT 'Token de session',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'Adresse IP',
    user_agent TEXT DEFAULT NULL COMMENT 'User Agent',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    expires_at DATETIME NOT NULL COMMENT 'Date d''expiration',
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Dernière activité',
    
    INDEX idx_user_id (user_id),
    INDEX idx_token (session_token),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SECTION 2: CONTENU ET PAGES
-- ============================================

-- TABLE: pages
-- Gestion dynamique du contenu des pages
CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    name VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nom de la page (ex: home, about, services)',
    title VARCHAR(255) DEFAULT NULL COMMENT 'Titre de la page',
    content LONGTEXT DEFAULT NULL COMMENT 'Contenu HTML de la page',
    meta_title VARCHAR(255) DEFAULT NULL COMMENT 'Meta title SEO',
    meta_description TEXT DEFAULT NULL COMMENT 'Meta description SEO',
    meta_keywords VARCHAR(500) DEFAULT NULL COMMENT 'Meta keywords',
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Dernière mise à jour',
    updated_by INT DEFAULT NULL COMMENT 'ID de l''utilisateur qui a modifié',
    
    INDEX idx_name (name),
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: site_content
-- Gestion du contenu dynamique du site (pages, sections, articles, etc.)
CREATE TABLE IF NOT EXISTS site_content (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique du contenu',
    type VARCHAR(50) NOT NULL COMMENT 'Type de contenu: page, section, article, service, etc.',
    slug VARCHAR(200) NOT NULL UNIQUE COMMENT 'Identifiant URL unique (ex: page-accueil)',
    titre VARCHAR(255) NOT NULL COMMENT 'Titre du contenu',
    sous_titre VARCHAR(255) DEFAULT NULL COMMENT 'Sous-titre optionnel',
    contenu LONGTEXT DEFAULT NULL COMMENT 'Contenu principal (HTML/text)',
    description TEXT DEFAULT NULL COMMENT 'Description/meta description',
    image_url VARCHAR(500) DEFAULT NULL COMMENT 'URL de l''image principale',
    ordre INT DEFAULT 0 COMMENT 'Ordre d''affichage (pour les listes)',
    statut VARCHAR(20) DEFAULT 'publie' COMMENT 'Statut: brouillon, publie, archive',
    auteur_id INT DEFAULT NULL COMMENT 'ID de l''administrateur qui a créé/modifié',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date de dernière modification',
    date_publication DATETIME DEFAULT NULL COMMENT 'Date de publication',
    meta_keywords VARCHAR(500) DEFAULT NULL COMMENT 'Mots-clés SEO',
    
    INDEX idx_type (type),
    INDEX idx_slug (slug),
    INDEX idx_statut (statut),
    INDEX idx_ordre (ordre),
    INDEX idx_date_publication (date_publication),
    INDEX idx_type_statut (type, statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: content_history
-- Historique des modifications de contenu
CREATE TABLE IF NOT EXISTS content_history (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    content_id INT NOT NULL COMMENT 'ID du contenu modifié',
    content_type VARCHAR(50) NOT NULL COMMENT 'Type de contenu (page, blog, etc.)',
    old_content LONGTEXT DEFAULT NULL COMMENT 'Ancien contenu',
    new_content LONGTEXT DEFAULT NULL COMMENT 'Nouveau contenu',
    changed_by INT DEFAULT NULL COMMENT 'ID de l''utilisateur qui a modifié',
    change_date DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de modification',
    change_reason VARCHAR(255) DEFAULT NULL COMMENT 'Raison de la modification',
    
    INDEX idx_content (content_id, content_type),
    INDEX idx_date (change_date),
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SECTION 3: SERVICES ET GALERIE
-- ============================================

-- TABLE: services
-- Gestion dynamique des services
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    title VARCHAR(255) NOT NULL COMMENT 'Titre du service',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Slug URL unique',
    description TEXT DEFAULT NULL COMMENT 'Description du service',
    full_description LONGTEXT DEFAULT NULL COMMENT 'Description complète',
    icon VARCHAR(100) DEFAULT NULL COMMENT 'Icône Font Awesome ou image',
    image_url VARCHAR(500) DEFAULT NULL COMMENT 'URL de l''image du service',
    price DECIMAL(10,2) DEFAULT NULL COMMENT 'Prix du service',
    duration VARCHAR(50) DEFAULT NULL COMMENT 'Durée du service',
    order_index INT DEFAULT 0 COMMENT 'Ordre d''affichage',
    active TINYINT(1) DEFAULT 1 COMMENT 'Service actif',
    featured TINYINT(1) DEFAULT 0 COMMENT 'Service mis en avant',
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Dernière mise à jour',
    updated_by INT DEFAULT NULL COMMENT 'ID de l''utilisateur qui a modifié',
    
    INDEX idx_active (active),
    INDEX idx_order (order_index),
    INDEX idx_slug (slug),
    INDEX idx_featured (featured),
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: gallery
-- Gestion dynamique de la galerie d'images
CREATE TABLE IF NOT EXISTS gallery (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    image_path VARCHAR(500) NOT NULL COMMENT 'Chemin de l''image',
    thumbnail_path VARCHAR(500) DEFAULT NULL COMMENT 'Chemin de la miniature',
    title VARCHAR(255) DEFAULT NULL COMMENT 'Titre de l''image',
    description TEXT DEFAULT NULL COMMENT 'Description de l''image',
    alt_text VARCHAR(255) DEFAULT NULL COMMENT 'Texte alternatif pour SEO',
    category VARCHAR(50) DEFAULT 'general' COMMENT 'Catégorie de l''image',
    tags VARCHAR(500) DEFAULT NULL COMMENT 'Tags séparés par virgules',
    file_size INT DEFAULT NULL COMMENT 'Taille du fichier en octets',
    width INT DEFAULT NULL COMMENT 'Largeur de l''image',
    height INT DEFAULT NULL COMMENT 'Hauteur de l''image',
    order_index INT DEFAULT 0 COMMENT 'Ordre d''affichage',
    active TINYINT(1) DEFAULT 1 COMMENT 'Image active',
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date d''upload',
    uploaded_by INT DEFAULT NULL COMMENT 'ID de l''utilisateur qui a uploadé',
    
    INDEX idx_category (category),
    INDEX idx_active (active),
    INDEX idx_order (order_index),
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: media_files
-- Gestion centralisée de tous les fichiers médias
CREATE TABLE IF NOT EXISTS media_files (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    filename VARCHAR(255) NOT NULL COMMENT 'Nom du fichier',
    original_filename VARCHAR(255) DEFAULT NULL COMMENT 'Nom original',
    file_path VARCHAR(500) NOT NULL COMMENT 'Chemin du fichier',
    file_type VARCHAR(50) DEFAULT NULL COMMENT 'Type MIME',
    file_size BIGINT DEFAULT NULL COMMENT 'Taille en octets',
    file_category ENUM('image', 'video', 'document', 'audio', 'other') DEFAULT 'other' COMMENT 'Catégorie',
    title VARCHAR(255) DEFAULT NULL COMMENT 'Titre',
    description TEXT DEFAULT NULL COMMENT 'Description',
    uploaded_by INT DEFAULT NULL COMMENT 'ID de l''utilisateur',
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date d''upload',
    
    INDEX idx_category (file_category),
    INDEX idx_type (file_type),
    INDEX idx_uploaded (uploaded_at),
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SECTION 4: BLOG ET CONTENU ÉDITORIAL
-- ============================================

-- TABLE: blog_categories
-- Catégories pour les articles de blog
CREATE TABLE IF NOT EXISTS blog_categories (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    name VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nom de la catégorie',
    slug VARCHAR(100) NOT NULL UNIQUE COMMENT 'Slug URL',
    description TEXT DEFAULT NULL COMMENT 'Description',
    parent_id INT DEFAULT NULL COMMENT 'ID de la catégorie parente',
    order_index INT DEFAULT 0 COMMENT 'Ordre d''affichage',
    active TINYINT(1) DEFAULT 1 COMMENT 'Catégorie active',
    
    INDEX idx_slug (slug),
    INDEX idx_parent (parent_id),
    INDEX idx_active (active),
    FOREIGN KEY (parent_id) REFERENCES blog_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: blog_tags
-- Tags pour les articles de blog
CREATE TABLE IF NOT EXISTS blog_tags (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    name VARCHAR(50) NOT NULL UNIQUE COMMENT 'Nom du tag',
    slug VARCHAR(50) NOT NULL UNIQUE COMMENT 'Slug URL',
    description TEXT DEFAULT NULL COMMENT 'Description',
    usage_count INT DEFAULT 0 COMMENT 'Nombre d''utilisations',
    
    INDEX idx_slug (slug),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: blog
-- Gestion des articles de blog/news
CREATE TABLE IF NOT EXISTS blog (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    title VARCHAR(255) NOT NULL COMMENT 'Titre de l''article',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Slug URL unique',
    image VARCHAR(500) DEFAULT NULL COMMENT 'Image principale',
    description TEXT DEFAULT NULL COMMENT 'Description/Résumé',
    content LONGTEXT DEFAULT NULL COMMENT 'Contenu complet de l''article',
    author_id INT DEFAULT NULL COMMENT 'ID de l''auteur',
    category_id INT DEFAULT NULL COMMENT 'ID de la catégorie',
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft' COMMENT 'Statut de publication',
    featured TINYINT(1) DEFAULT 0 COMMENT 'Article mis en avant',
    views INT DEFAULT 0 COMMENT 'Nombre de vues',
    likes INT DEFAULT 0 COMMENT 'Nombre de likes',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date de mise à jour',
    published_at DATETIME DEFAULT NULL COMMENT 'Date de publication',
    meta_title VARCHAR(255) DEFAULT NULL COMMENT 'Meta title SEO',
    meta_description TEXT DEFAULT NULL COMMENT 'Meta description SEO',
    
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_featured (featured),
    INDEX idx_created (created_at),
    INDEX idx_category (category_id),
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: blog_post_tags
-- Relation entre articles et tags (many-to-many)
CREATE TABLE IF NOT EXISTS blog_post_tags (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    blog_id INT NOT NULL COMMENT 'ID de l''article',
    tag_id INT NOT NULL COMMENT 'ID du tag',
    
    UNIQUE KEY unique_blog_tag (blog_id, tag_id),
    INDEX idx_blog (blog_id),
    INDEX idx_tag (tag_id),
    FOREIGN KEY (blog_id) REFERENCES blog(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES blog_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: blog_comments
-- Commentaires sur les articles de blog
CREATE TABLE IF NOT EXISTS blog_comments (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    blog_id INT NOT NULL COMMENT 'ID de l''article',
    parent_id INT DEFAULT NULL COMMENT 'ID du commentaire parent (réponse)',
    author_name VARCHAR(100) NOT NULL COMMENT 'Nom de l''auteur',
    author_email VARCHAR(150) NOT NULL COMMENT 'Email de l''auteur',
    author_website VARCHAR(255) DEFAULT NULL COMMENT 'Site web de l''auteur',
    content TEXT NOT NULL COMMENT 'Contenu du commentaire',
    status ENUM('pending', 'approved', 'spam', 'trash') DEFAULT 'pending' COMMENT 'Statut',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'Adresse IP',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    
    INDEX idx_blog (blog_id),
    INDEX idx_status (status),
    INDEX idx_parent (parent_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (blog_id) REFERENCES blog(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES blog_comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: testimonials
-- Gestion des témoignages clients
CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    nom VARCHAR(100) NOT NULL COMMENT 'Nom de la personne',
    role VARCHAR(100) DEFAULT NULL COMMENT 'Rôle/Fonction (ex: Patient, Sportif)',
    message TEXT NOT NULL COMMENT 'Contenu du témoignage',
    photo_url VARCHAR(500) DEFAULT NULL COMMENT 'URL de la photo',
    note INT DEFAULT 5 COMMENT 'Note de 1 à 5',
    actif TINYINT(1) DEFAULT 1 COMMENT 'Témoignage actif (affiché sur le site)',
    ordre INT DEFAULT 0 COMMENT 'Ordre d''affichage',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date de modification',
    
    INDEX idx_actif (actif),
    INDEX idx_ordre (ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SECTION 5: COMMUNICATION ET CONTACT
-- ============================================

-- TABLE: contact_messages
-- Stocke tous les messages envoyés via le formulaire de contact
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique du message',
    nom VARCHAR(100) NOT NULL COMMENT 'Nom complet de l''expéditeur',
    email VARCHAR(150) NOT NULL COMMENT 'Adresse e-mail de l''expéditeur',
    telephone VARCHAR(20) DEFAULT NULL COMMENT 'Numéro de téléphone de l''expéditeur',
    whatsapp VARCHAR(20) DEFAULT NULL COMMENT 'Numéro WhatsApp de l''expéditeur',
    sujet VARCHAR(200) DEFAULT NULL COMMENT 'Sujet du message (optionnel)',
    message TEXT NOT NULL COMMENT 'Contenu du message',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'Adresse IP de l''expéditeur (IPv4 ou IPv6)',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date et heure de création du message',
    lu TINYINT(1) DEFAULT 0 COMMENT 'Statut de lecture : 0 = non lu, 1 = lu',
    repondu TINYINT(1) DEFAULT 0 COMMENT 'Statut de réponse : 0 = non répondu, 1 = répondu',
    replied_at DATETIME DEFAULT NULL COMMENT 'Date de réponse',
    replied_by INT DEFAULT NULL COMMENT 'ID de l''utilisateur qui a répondu',
    
    INDEX idx_email (email),
    INDEX idx_telephone (telephone),
    INDEX idx_date (date_creation),
    INDEX idx_lu (lu),
    INDEX idx_repondu (repondu),
    INDEX idx_date_lu (date_creation, lu),
    FOREIGN KEY (replied_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: appointments
-- Gestion des demandes de rendez-vous en ligne
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique du rendez-vous',
    nom VARCHAR(100) NOT NULL COMMENT 'Nom complet du patient',
    telephone VARCHAR(20) NOT NULL COMMENT 'Numéro de téléphone',
    email VARCHAR(150) NOT NULL COMMENT 'Adresse e-mail',
    service_type VARCHAR(50) NOT NULL COMMENT 'Type de service demandé',
    date_souhaitee DATE NOT NULL COMMENT 'Date souhaitée pour le rendez-vous',
    heure_souhaitee TIME NOT NULL COMMENT 'Heure souhaitée pour le rendez-vous',
    message TEXT DEFAULT NULL COMMENT 'Message ou commentaires du patient',
    adresse_complete TEXT DEFAULT NULL COMMENT 'Adresse complète du patient',
    code_postal VARCHAR(20) DEFAULT NULL COMMENT 'Code postal',
    ville VARCHAR(100) DEFAULT NULL COMMENT 'Ville',
    pays VARCHAR(100) DEFAULT 'Burundi' COMMENT 'Pays',
    nationalite VARCHAR(100) DEFAULT NULL COMMENT 'Nationalité',
    date_naissance DATE DEFAULT NULL COMMENT 'Date de naissance',
    genre ENUM('homme', 'femme', 'autre', 'non_specifie') DEFAULT 'non_specifie' COMMENT 'Genre',
    profession VARCHAR(150) DEFAULT NULL COMMENT 'Profession',
    assurance_sante VARCHAR(150) DEFAULT NULL COMMENT 'Assurance santé',
    motif_consultation TEXT DEFAULT NULL COMMENT 'Motif de consultation détaillé',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'Adresse IP de l''expéditeur',
    statut ENUM('en_attente', 'confirme', 'annule', 'termine') DEFAULT 'en_attente' COMMENT 'Statut du rendez-vous',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date et heure de création de la demande',
    date_confirmation DATETIME DEFAULT NULL COMMENT 'Date de confirmation du rendez-vous',
    confirmed_by INT DEFAULT NULL COMMENT 'ID de l''utilisateur qui a confirmé',
    notes TEXT DEFAULT NULL COMMENT 'Notes internes',
    
    INDEX idx_email (email),
    INDEX idx_telephone (telephone),
    INDEX idx_date_souhaitee (date_souhaitee),
    INDEX idx_statut (statut),
    INDEX idx_date_creation (date_creation),
    INDEX idx_ville (ville),
    INDEX idx_nationalite (nationalite),
    FOREIGN KEY (confirmed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: newsletter
-- Gestion des abonnés à la newsletter
CREATE TABLE IF NOT EXISTS newsletter (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    email VARCHAR(150) NOT NULL UNIQUE COMMENT 'Email de l''abonné',
    name VARCHAR(100) DEFAULT NULL COMMENT 'Nom de l''abonné',
    status ENUM('active', 'unsubscribed', 'bounced') DEFAULT 'active' COMMENT 'Statut de l''abonnement',
    subscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date d''abonnement',
    unsubscribed_at DATETIME DEFAULT NULL COMMENT 'Date de désabonnement',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'Adresse IP lors de l''abonnement',
    verification_token VARCHAR(100) DEFAULT NULL COMMENT 'Token de vérification',
    verified TINYINT(1) DEFAULT 0 COMMENT 'Email vérifié',
    
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_verified (verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: notifications
-- Système de notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    user_id INT DEFAULT NULL COMMENT 'ID de l''utilisateur destinataire (NULL = tous)',
    type VARCHAR(50) NOT NULL COMMENT 'Type de notification',
    title VARCHAR(255) NOT NULL COMMENT 'Titre de la notification',
    message TEXT NOT NULL COMMENT 'Message de la notification',
    link VARCHAR(500) DEFAULT NULL COMMENT 'Lien associé',
    read_status TINYINT(1) DEFAULT 0 COMMENT 'Statut de lecture',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_read (read_status),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SECTION 6: PARAMÈTRES ET CONFIGURATION
-- ============================================

-- TABLE: site_settings
-- Paramètres et configurations du site
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique du paramètre',
    cle VARCHAR(100) NOT NULL UNIQUE COMMENT 'Clé du paramètre (ex: site_titre, contact_email)',
    valeur TEXT DEFAULT NULL COMMENT 'Valeur du paramètre',
    type VARCHAR(50) DEFAULT 'text' COMMENT 'Type: text, number, boolean, json, html',
    description TEXT DEFAULT NULL COMMENT 'Description du paramètre',
    categorie VARCHAR(50) DEFAULT 'general' COMMENT 'Catégorie: general, contact, seo, social, etc.',
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date de dernière modification',
    
    INDEX idx_cle (cle),
    INDEX idx_categorie (categorie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: admin_settings
-- Paramètres personnels des administrateurs
CREATE TABLE IF NOT EXISTS admin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    admin_id INT NOT NULL COMMENT 'ID de l''administrateur',
    cle VARCHAR(100) NOT NULL COMMENT 'Clé du paramètre',
    valeur TEXT DEFAULT NULL COMMENT 'Valeur du paramètre',
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date de modification',
    
    UNIQUE KEY unique_admin_setting (admin_id, cle),
    INDEX idx_admin_id (admin_id),
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SECTION 7: SÉCURITÉ ET JOURNALISATION
-- ============================================

-- TABLE: security_logs
-- Logs de sécurité et d'activité (ancien système)
CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    type VARCHAR(50) NOT NULL COMMENT 'Type: login, logout, failed_login, modification, etc.',
    admin_id INT DEFAULT NULL COMMENT 'ID de l''administrateur concerné',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'Adresse IP',
    user_agent TEXT DEFAULT NULL COMMENT 'User Agent du navigateur',
    description TEXT DEFAULT NULL COMMENT 'Description de l''action',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date et heure',
    
    INDEX idx_type (type),
    INDEX idx_admin_id (admin_id),
    INDEX idx_date (date_creation),
    INDEX idx_ip (ip_address),
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: activity_log
-- Journal d'activité complet (nouveau système)
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    user_id INT DEFAULT NULL COMMENT 'ID de l''utilisateur',
    action VARCHAR(100) NOT NULL COMMENT 'Action effectuée',
    entity_type VARCHAR(50) DEFAULT NULL COMMENT 'Type d''entité (page, gallery, blog, etc.)',
    entity_id INT DEFAULT NULL COMMENT 'ID de l''entité concernée',
    description TEXT DEFAULT NULL COMMENT 'Description détaillée',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'Adresse IP',
    user_agent TEXT DEFAULT NULL COMMENT 'User Agent',
    date_time DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date et heure',
    
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_date (date_time),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SECTION 8: STATISTIQUES ET ANALYTICS
-- ============================================

-- TABLE: visitor_stats
-- Statistiques des visiteurs
CREATE TABLE IF NOT EXISTS visitor_stats (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    ip_address VARCHAR(45) NOT NULL COMMENT 'Adresse IP',
    country VARCHAR(100) DEFAULT NULL COMMENT 'Pays',
    city VARCHAR(100) DEFAULT NULL COMMENT 'Ville',
    page_visited VARCHAR(255) DEFAULT NULL COMMENT 'Page visitée',
    referrer VARCHAR(500) DEFAULT NULL COMMENT 'Référent',
    user_agent TEXT DEFAULT NULL COMMENT 'User Agent',
    visit_date DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de visite',
    session_id VARCHAR(100) DEFAULT NULL COMMENT 'ID de session',
    visit_duration INT DEFAULT NULL COMMENT 'Durée de visite en secondes',
    device_type VARCHAR(50) DEFAULT NULL COMMENT 'Type d''appareil (mobile, desktop, tablet)',
    browser VARCHAR(100) DEFAULT NULL COMMENT 'Navigateur',
    
    INDEX idx_ip (ip_address),
    INDEX idx_date (visit_date),
    INDEX idx_page (page_visited),
    INDEX idx_session (session_id),
    INDEX idx_device (device_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SECTION 9: SAUVEGARDE ET MAINTENANCE
-- ============================================

-- TABLE: backups
-- Historique des sauvegardes de base de données
CREATE TABLE IF NOT EXISTS backups (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    filename VARCHAR(255) NOT NULL COMMENT 'Nom du fichier de sauvegarde',
    file_path VARCHAR(500) NOT NULL COMMENT 'Chemin du fichier',
    file_size BIGINT DEFAULT NULL COMMENT 'Taille du fichier en octets',
    created_by INT DEFAULT NULL COMMENT 'ID de l''utilisateur qui a créé la sauvegarde',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    description TEXT DEFAULT NULL COMMENT 'Description de la sauvegarde',
    backup_type ENUM('full', 'incremental', 'manual') DEFAULT 'manual' COMMENT 'Type de sauvegarde',
    
    INDEX idx_created (created_at),
    INDEX idx_type (backup_type),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SECTION 10: FONCTIONNALITÉS AVANCÉES
-- ============================================

-- TABLE: faq
-- Questions fréquemment posées
CREATE TABLE IF NOT EXISTS faq (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    question TEXT NOT NULL COMMENT 'Question',
    answer LONGTEXT NOT NULL COMMENT 'Réponse',
    category VARCHAR(100) DEFAULT NULL COMMENT 'Catégorie',
    order_index INT DEFAULT 0 COMMENT 'Ordre d''affichage',
    active TINYINT(1) DEFAULT 1 COMMENT 'FAQ active',
    views INT DEFAULT 0 COMMENT 'Nombre de vues',
    helpful_count INT DEFAULT 0 COMMENT 'Nombre de "utile"',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date de mise à jour',
    
    INDEX idx_category (category),
    INDEX idx_active (active),
    INDEX idx_order (order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: team_members
-- Membres de l'équipe
CREATE TABLE IF NOT EXISTS team_members (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    name VARCHAR(100) NOT NULL COMMENT 'Nom complet',
    role VARCHAR(100) DEFAULT NULL COMMENT 'Rôle/Fonction',
    bio TEXT DEFAULT NULL COMMENT 'Biographie',
    photo_url VARCHAR(500) DEFAULT NULL COMMENT 'URL de la photo',
    email VARCHAR(150) DEFAULT NULL COMMENT 'Email',
    phone VARCHAR(20) DEFAULT NULL COMMENT 'Téléphone',
    social_links TEXT DEFAULT NULL COMMENT 'Liens sociaux (JSON stocké en TEXT)',
    order_index INT DEFAULT 0 COMMENT 'Ordre d''affichage',
    active TINYINT(1) DEFAULT 1 COMMENT 'Membre actif',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    
    INDEX idx_active (active),
    INDEX idx_order (order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: partners
-- Partenaires et sponsors
CREATE TABLE IF NOT EXISTS partners (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    name VARCHAR(255) NOT NULL COMMENT 'Nom du partenaire',
    logo_url VARCHAR(500) DEFAULT NULL COMMENT 'URL du logo',
    website VARCHAR(255) DEFAULT NULL COMMENT 'Site web',
    description TEXT DEFAULT NULL COMMENT 'Description',
    category VARCHAR(100) DEFAULT NULL COMMENT 'Catégorie',
    order_index INT DEFAULT 0 COMMENT 'Ordre d''affichage',
    active TINYINT(1) DEFAULT 1 COMMENT 'Partenaire actif',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    
    INDEX idx_category (category),
    INDEX idx_active (active),
    INDEX idx_order (order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: events
-- Événements et calendrier
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    title VARCHAR(255) NOT NULL COMMENT 'Titre de l''événement',
    description TEXT DEFAULT NULL COMMENT 'Description',
    start_date DATETIME NOT NULL COMMENT 'Date de début',
    end_date DATETIME DEFAULT NULL COMMENT 'Date de fin',
    location VARCHAR(255) DEFAULT NULL COMMENT 'Lieu',
    image_url VARCHAR(500) DEFAULT NULL COMMENT 'URL de l''image',
    status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming' COMMENT 'Statut',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    created_by INT DEFAULT NULL COMMENT 'ID du créateur',
    
    INDEX idx_start_date (start_date),
    INDEX idx_status (status),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: translations
-- Système de traduction multilingue
CREATE TABLE IF NOT EXISTS translations (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    language_code VARCHAR(10) NOT NULL COMMENT 'Code langue (fr, en, sw)',
    key_name VARCHAR(255) NOT NULL COMMENT 'Clé de traduction',
    value TEXT NOT NULL COMMENT 'Valeur traduite',
    context VARCHAR(100) DEFAULT NULL COMMENT 'Contexte',
    
    UNIQUE KEY unique_translation (language_code, key_name),
    INDEX idx_language (language_code),
    INDEX idx_key (key_name),
    INDEX idx_context (context)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: dynamic_forms
-- Formulaires dynamiques personnalisables
CREATE TABLE IF NOT EXISTS dynamic_forms (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    name VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nom du formulaire',
    title VARCHAR(255) DEFAULT NULL COMMENT 'Titre',
    description TEXT DEFAULT NULL COMMENT 'Description',
    fields TEXT NOT NULL COMMENT 'Champs du formulaire (JSON stocké en TEXT)',
    settings TEXT DEFAULT NULL COMMENT 'Paramètres (JSON stocké en TEXT)',
    active TINYINT(1) DEFAULT 1 COMMENT 'Formulaire actif',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    created_by INT DEFAULT NULL COMMENT 'ID du créateur',
    
    INDEX idx_name (name),
    INDEX idx_active (active),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: form_submissions
-- Soumissions des formulaires dynamiques
CREATE TABLE IF NOT EXISTS form_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    form_id INT NOT NULL COMMENT 'ID du formulaire',
    data TEXT NOT NULL COMMENT 'Données soumises (JSON stocké en TEXT)',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'Adresse IP',
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de soumission',
    read_status TINYINT(1) DEFAULT 0 COMMENT 'Statut de lecture',
    
    INDEX idx_form (form_id),
    INDEX idx_submitted (submitted_at),
    INDEX idx_read (read_status),
    FOREIGN KEY (form_id) REFERENCES dynamic_forms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: ratings
-- Système d'évaluation/notation
CREATE TABLE IF NOT EXISTS ratings (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    entity_type VARCHAR(50) NOT NULL COMMENT 'Type d''entité (service, blog, etc.)',
    entity_id INT NOT NULL COMMENT 'ID de l''entité',
    user_id INT DEFAULT NULL COMMENT 'ID de l''utilisateur (NULL = anonyme)',
    rating INT NOT NULL COMMENT 'Note de 1 à 5',
    comment TEXT DEFAULT NULL COMMENT 'Commentaire',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' COMMENT 'Statut',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_rating (rating),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SECTION 11: GESTION MÉDICALE ET PATIENTS
-- ============================================

-- TABLE: patients
-- Gestion complète des patients du centre
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique du patient',
    numero_dossier VARCHAR(50) UNIQUE COMMENT 'Numéro de dossier unique',
    nom VARCHAR(100) NOT NULL COMMENT 'Nom complet',
    prenom VARCHAR(100) NOT NULL COMMENT 'Prénom',
    date_naissance DATE DEFAULT NULL COMMENT 'Date de naissance',
    genre ENUM('homme', 'femme', 'autre', 'non_specifie') DEFAULT 'non_specifie' COMMENT 'Genre',
    telephone VARCHAR(20) NOT NULL COMMENT 'Numéro de téléphone',
    email VARCHAR(150) DEFAULT NULL COMMENT 'Adresse e-mail',
    adresse_complete TEXT DEFAULT NULL COMMENT 'Adresse complète',
    code_postal VARCHAR(20) DEFAULT NULL COMMENT 'Code postal',
    ville VARCHAR(100) DEFAULT NULL COMMENT 'Ville',
    pays VARCHAR(100) DEFAULT 'Burundi' COMMENT 'Pays',
    nationalite VARCHAR(100) DEFAULT NULL COMMENT 'Nationalité',
    profession VARCHAR(150) DEFAULT NULL COMMENT 'Profession',
    assurance_sante VARCHAR(150) DEFAULT NULL COMMENT 'Assurance santé',
    contact_urgence_nom VARCHAR(100) DEFAULT NULL COMMENT 'Nom du contact d''urgence',
    contact_urgence_telephone VARCHAR(20) DEFAULT NULL COMMENT 'Téléphone du contact d''urgence',
    contact_urgence_lien VARCHAR(50) DEFAULT NULL COMMENT 'Lien avec le contact d''urgence',
    antecedents_medicaux TEXT DEFAULT NULL COMMENT 'Antécédents médicaux',
    allergies TEXT DEFAULT NULL COMMENT 'Allergies connues',
    medicaments_actuels TEXT DEFAULT NULL COMMENT 'Médicaments actuels',
    notes_medicales TEXT DEFAULT NULL COMMENT 'Notes médicales générales',
    statut ENUM('actif', 'inactif', 'archive') DEFAULT 'actif' COMMENT 'Statut du patient',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création du dossier',
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date de dernière modification',
    cree_par INT DEFAULT NULL COMMENT 'ID de l''utilisateur qui a créé le dossier',
    
    INDEX idx_numero_dossier (numero_dossier),
    INDEX idx_nom_prenom (nom, prenom),
    INDEX idx_telephone (telephone),
    INDEX idx_email (email),
    INDEX idx_statut (statut),
    INDEX idx_date_creation (date_creation),
    FOREIGN KEY (cree_par) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: traitements
-- Suivi des séances de traitement des patients
CREATE TABLE IF NOT EXISTS traitements (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique du traitement',
    patient_id INT NOT NULL COMMENT 'ID du patient',
    appointment_id INT DEFAULT NULL COMMENT 'ID du rendez-vous associé',
    therapeute_id INT DEFAULT NULL COMMENT 'ID du thérapeute',
    type_traitement VARCHAR(100) NOT NULL COMMENT 'Type de traitement',
    service_id INT DEFAULT NULL COMMENT 'ID du service',
    date_seance DATETIME NOT NULL COMMENT 'Date et heure de la séance',
    duree_seance INT DEFAULT 60 COMMENT 'Durée de la séance en minutes',
    observations TEXT DEFAULT NULL COMMENT 'Observations du thérapeute',
    exercices_prescrits TEXT DEFAULT NULL COMMENT 'Exercices prescrits',
    progres TEXT DEFAULT NULL COMMENT 'Progrès notés',
    douleur_avant INT DEFAULT NULL COMMENT 'Niveau de douleur avant (0-10)',
    douleur_apres INT DEFAULT NULL COMMENT 'Niveau de douleur après (0-10)',
    mobilite_avant VARCHAR(50) DEFAULT NULL COMMENT 'Mobilité avant',
    mobilite_apres VARCHAR(50) DEFAULT NULL COMMENT 'Mobilité après',
    statut ENUM('planifie', 'en_cours', 'termine', 'annule') DEFAULT 'planifie' COMMENT 'Statut de la séance',
    facture_id INT DEFAULT NULL COMMENT 'ID de la facture associée',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date de modification',
    
    INDEX idx_patient (patient_id),
    INDEX idx_appointment (appointment_id),
    INDEX idx_therapeute (therapeute_id),
    INDEX idx_date_seance (date_seance),
    INDEX idx_statut (statut),
    INDEX idx_service (service_id),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (therapeute_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: factures
-- Gestion des factures et facturation
CREATE TABLE IF NOT EXISTS factures (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique de la facture',
    numero_facture VARCHAR(50) UNIQUE NOT NULL COMMENT 'Numéro de facture unique',
    patient_id INT NOT NULL COMMENT 'ID du patient',
    date_facture DATE NOT NULL COMMENT 'Date de la facture',
    date_echeance DATE DEFAULT NULL COMMENT 'Date d''échéance',
    montant_total DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Montant total',
    montant_ht DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Montant HT',
    tva DECIMAL(10,2) DEFAULT 0.00 COMMENT 'TVA',
    remise DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Remise',
    statut ENUM('brouillon', 'envoyee', 'payee', 'partielle', 'annulee', 'impayee') DEFAULT 'brouillon' COMMENT 'Statut de la facture',
    mode_paiement VARCHAR(50) DEFAULT NULL COMMENT 'Mode de paiement',
    notes TEXT DEFAULT NULL COMMENT 'Notes sur la facture',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date de modification',
    cree_par INT DEFAULT NULL COMMENT 'ID de l''utilisateur qui a créé la facture',
    
    INDEX idx_numero_facture (numero_facture),
    INDEX idx_patient (patient_id),
    INDEX idx_date_facture (date_facture),
    INDEX idx_statut (statut),
    INDEX idx_date_echeance (date_echeance),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (cree_par) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: facture_lignes
-- Lignes de détail des factures
CREATE TABLE IF NOT EXISTS facture_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    facture_id INT NOT NULL COMMENT 'ID de la facture',
    traitement_id INT DEFAULT NULL COMMENT 'ID du traitement associé',
    description VARCHAR(255) NOT NULL COMMENT 'Description de la ligne',
    quantite INT DEFAULT 1 COMMENT 'Quantité',
    prix_unitaire DECIMAL(10,2) NOT NULL COMMENT 'Prix unitaire',
    montant DECIMAL(10,2) NOT NULL COMMENT 'Montant total de la ligne',
    ordre INT DEFAULT 0 COMMENT 'Ordre d''affichage',
    
    INDEX idx_facture (facture_id),
    INDEX idx_traitement (traitement_id),
    FOREIGN KEY (facture_id) REFERENCES factures(id) ON DELETE CASCADE,
    FOREIGN KEY (traitement_id) REFERENCES traitements(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: paiements
-- Suivi des paiements
CREATE TABLE IF NOT EXISTS paiements (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique du paiement',
    facture_id INT NOT NULL COMMENT 'ID de la facture',
    patient_id INT NOT NULL COMMENT 'ID du patient',
    montant DECIMAL(10,2) NOT NULL COMMENT 'Montant payé',
    mode_paiement ENUM('especes', 'cheque', 'virement', 'carte', 'mobile_money', 'autre') NOT NULL COMMENT 'Mode de paiement',
    reference_paiement VARCHAR(100) DEFAULT NULL COMMENT 'Référence du paiement',
    date_paiement DATETIME NOT NULL COMMENT 'Date et heure du paiement',
    notes TEXT DEFAULT NULL COMMENT 'Notes sur le paiement',
    enregistre_par INT DEFAULT NULL COMMENT 'ID de l''utilisateur qui a enregistré le paiement',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    
    INDEX idx_facture (facture_id),
    INDEX idx_patient (patient_id),
    INDEX idx_date_paiement (date_paiement),
    INDEX idx_mode_paiement (mode_paiement),
    FOREIGN KEY (facture_id) REFERENCES factures(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (enregistre_par) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: documents_medicaux
-- Stockage des documents médicaux des patients
CREATE TABLE IF NOT EXISTS documents_medicaux (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    patient_id INT NOT NULL COMMENT 'ID du patient',
    traitement_id INT DEFAULT NULL COMMENT 'ID du traitement associé',
    type_document ENUM('ordonnance', 'prescription', 'examen', 'radio', 'rapport', 'autre') DEFAULT 'autre' COMMENT 'Type de document',
    titre VARCHAR(255) NOT NULL COMMENT 'Titre du document',
    description TEXT DEFAULT NULL COMMENT 'Description',
    fichier_path VARCHAR(500) NOT NULL COMMENT 'Chemin du fichier',
    fichier_nom VARCHAR(255) DEFAULT NULL COMMENT 'Nom original du fichier',
    fichier_taille BIGINT DEFAULT NULL COMMENT 'Taille du fichier en octets',
    fichier_type VARCHAR(50) DEFAULT NULL COMMENT 'Type MIME',
    date_document DATE DEFAULT NULL COMMENT 'Date du document',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    cree_par INT DEFAULT NULL COMMENT 'ID de l''utilisateur qui a uploadé',
    
    INDEX idx_patient (patient_id),
    INDEX idx_traitement (traitement_id),
    INDEX idx_type (type_document),
    INDEX idx_date_document (date_document),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (traitement_id) REFERENCES traitements(id) ON DELETE SET NULL,
    FOREIGN KEY (cree_par) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: rappels
-- Système de rappels automatiques
CREATE TABLE IF NOT EXISTS rappels (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    patient_id INT DEFAULT NULL COMMENT 'ID du patient',
    appointment_id INT DEFAULT NULL COMMENT 'ID du rendez-vous',
    type_rappel ENUM('rendez_vous', 'traitement', 'paiement', 'examen', 'autre') NOT NULL COMMENT 'Type de rappel',
    titre VARCHAR(255) NOT NULL COMMENT 'Titre du rappel',
    message TEXT NOT NULL COMMENT 'Message du rappel',
    date_rappel DATETIME NOT NULL COMMENT 'Date et heure du rappel',
    canal ENUM('email', 'sms', 'telephone', 'whatsapp', 'systeme') DEFAULT 'email' COMMENT 'Canal de rappel',
    statut ENUM('planifie', 'envoye', 'lu', 'annule') DEFAULT 'planifie' COMMENT 'Statut du rappel',
    envoye_le DATETIME DEFAULT NULL COMMENT 'Date d''envoi',
    lu_le DATETIME DEFAULT NULL COMMENT 'Date de lecture',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    
    INDEX idx_patient (patient_id),
    INDEX idx_appointment (appointment_id),
    INDEX idx_date_rappel (date_rappel),
    INDEX idx_statut (statut),
    INDEX idx_type (type_rappel),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: prescriptions
-- Prescriptions médicales
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    patient_id INT NOT NULL COMMENT 'ID du patient',
    traitement_id INT DEFAULT NULL COMMENT 'ID du traitement associé',
    medecin_id INT DEFAULT NULL COMMENT 'ID du médecin prescripteur',
    date_prescription DATE NOT NULL COMMENT 'Date de prescription',
    medicaments TEXT NOT NULL COMMENT 'Liste des médicaments (JSON stocké en TEXT)',
    instructions TEXT DEFAULT NULL COMMENT 'Instructions pour le patient',
    duree_traitement INT DEFAULT NULL COMMENT 'Durée du traitement en jours',
    renouvelable TINYINT(1) DEFAULT 0 COMMENT 'Prescription renouvelable',
    nombre_renouvellements INT DEFAULT 0 COMMENT 'Nombre de renouvellements autorisés',
    statut ENUM('active', 'terminee', 'annulee') DEFAULT 'active' COMMENT 'Statut de la prescription',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    
    INDEX idx_patient (patient_id),
    INDEX idx_traitement (traitement_id),
    INDEX idx_medecin (medecin_id),
    INDEX idx_date_prescription (date_prescription),
    INDEX idx_statut (statut),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (traitement_id) REFERENCES traitements(id) ON DELETE SET NULL,
    FOREIGN KEY (medecin_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: statistiques_medicales
-- Statistiques médicales et rapports
CREATE TABLE IF NOT EXISTS statistiques_medicales (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    type_statistique VARCHAR(50) NOT NULL COMMENT 'Type de statistique',
    periode DATE NOT NULL COMMENT 'Période (date de début)',
    donnees TEXT NOT NULL COMMENT 'Données statistiques (JSON stocké en TEXT)',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    
    INDEX idx_type (type_statistique),
    INDEX idx_periode (periode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: calendrier
-- Calendrier avancé pour la gestion des rendez-vous
CREATE TABLE IF NOT EXISTS calendrier (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identifiant unique',
    titre VARCHAR(255) NOT NULL COMMENT 'Titre de l''événement',
    description TEXT DEFAULT NULL COMMENT 'Description',
    type_evenement ENUM('rendez_vous', 'traitement', 'reunion', 'formation', 'autre') DEFAULT 'rendez_vous' COMMENT 'Type d''événement',
    date_debut DATETIME NOT NULL COMMENT 'Date et heure de début',
    date_fin DATETIME DEFAULT NULL COMMENT 'Date et heure de fin',
    patient_id INT DEFAULT NULL COMMENT 'ID du patient (si applicable)',
    therapeute_id INT DEFAULT NULL COMMENT 'ID du thérapeute',
    appointment_id INT DEFAULT NULL COMMENT 'ID du rendez-vous associé',
    traitement_id INT DEFAULT NULL COMMENT 'ID du traitement associé',
    couleur VARCHAR(7) DEFAULT '#4AB0D9' COMMENT 'Couleur de l''événement (hex)',
    lieu VARCHAR(255) DEFAULT NULL COMMENT 'Lieu',
    statut ENUM('planifie', 'confirme', 'en_cours', 'termine', 'annule') DEFAULT 'planifie' COMMENT 'Statut',
    rappel_envoye TINYINT(1) DEFAULT 0 COMMENT 'Rappel envoyé',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    cree_par INT DEFAULT NULL COMMENT 'ID de l''utilisateur créateur',
    
    INDEX idx_date_debut (date_debut),
    INDEX idx_patient (patient_id),
    INDEX idx_therapeute (therapeute_id),
    INDEX idx_statut (statut),
    INDEX idx_type (type_evenement),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (therapeute_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (traitement_id) REFERENCES traitements(id) ON DELETE SET NULL,
    FOREIGN KEY (cree_par) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- SECTION 12: DONNÉES PAR DÉFAUT
-- ============================================

-- INSERTION D'UN UTILISATEUR ADMIN PAR DÉFAUT
-- Utilisateur: mugisho | Mot de passe: merci@1234
INSERT INTO users (username, password, role, email, active, email_verified) VALUES
('mugisho', '$2y$12$hD5RiN.QECWhOuRnwDQFbeXCM/lGbJv48VnztZhtTxHUaFfbqA1gS', 'admin', 'mugishomerci123@gmail.com', 1, 1)
ON DUPLICATE KEY UPDATE username = username;

-- Note: Le hash correspond au mot de passe 'merci@1234'
-- Pour générer un nouveau hash, utilisez PHP: password_hash('votre_mot_de_passe', PASSWORD_DEFAULT);

-- INSERTION DES PAGES PAR DÉFAUT
INSERT INTO pages (name, title, meta_title, meta_description) VALUES
('home', 'Accueil', 'CREx - Centre de Réadaptation et d''Excellence', 'Centre de réadaptation et d''excellence offrant des services de kinésithérapie, réadaptation et accompagnement psychologique'),
('about', 'À propos', 'À propos - CREx', 'Découvrez le Centre CREx, notre équipe et notre mission'),
('services', 'Services', 'Nos Services - CREx', 'Découvrez tous nos services de kinésithérapie, réadaptation et accompagnement psychologique'),
('gallery', 'Galerie', 'Galerie Photos - CREx', 'Découvrez notre centre en images'),
('contact', 'Contact', 'Contactez-nous - CREx', 'Contactez le Centre CREx pour toute question ou prise de rendez-vous')
ON DUPLICATE KEY UPDATE name = name;

-- INSERTION DES CATÉGORIES DE BLOG PAR DÉFAUT
INSERT INTO blog_categories (name, slug, description, order_index) VALUES
('Actualités', 'actualites', 'Actualités et nouvelles du centre', 1),
('Santé & Bien-être', 'sante-bien-etre', 'Articles sur la santé et le bien-être', 2),
('Conseils', 'conseils', 'Conseils et astuces', 3),
('Témoignages', 'temoignages', 'Témoignages de patients', 4)
ON DUPLICATE KEY UPDATE name = name;

-- INSERTION DES PARAMÈTRES PAR DÉFAUT DU SITE
INSERT INTO site_settings (cle, valeur, type, description, categorie) VALUES
('site_titre', 'CREx - Centre de Réadaptation et d''Excellence', 'text', 'Titre principal du site', 'general'),
('site_description', 'Centre de réadaptation et d''excellence offrant des services de kinésithérapie, réadaptation et accompagnement psychologique', 'text', 'Description du site', 'general'),
('site_language', 'fr', 'text', 'Langue par défaut du site', 'general'),
('contact_email', 'crex.bdi@gmail.com', 'text', 'Email de contact principal', 'contact'),
('contact_telephone_1', '+257 77 510 647', 'text', 'Premier numéro de téléphone', 'contact'),
('contact_telephone_2', '+257 61 343 682', 'text', 'Deuxième numéro de téléphone', 'contact'),
('contact_whatsapp', '+257 77 510 647', 'text', 'Numéro WhatsApp', 'contact'),
('contact_adresse', 'Kinindo Ouest, Avenue Beraka N°30 — Bujumbura, Burundi', 'text', 'Adresse complète', 'contact'),
('horaires_lundi_vendredi', '08h00 – 19h00', 'text', 'Horaires du lundi au vendredi', 'contact'),
('horaires_samedi', '09h00 – 14h00', 'text', 'Horaires du samedi', 'contact'),
('horaires_dimanche', 'Fermé', 'text', 'Horaires du dimanche', 'contact'),
('site_facebook', 'https://facebook.com', 'text', 'Lien Facebook', 'social'),
('site_linkedin', 'https://linkedin.com', 'text', 'Lien LinkedIn', 'social'),
('site_whatsapp_link', 'https://wa.me/25777510647', 'text', 'Lien WhatsApp', 'social'),
('site_instagram', '', 'text', 'Lien Instagram', 'social'),
('site_twitter', '', 'text', 'Lien Twitter', 'social'),
('google_maps_api_key', '', 'text', 'Clé API Google Maps', 'seo'),
('recaptcha_site_key', '', 'text', 'Clé site reCAPTCHA', 'security'),
('recaptcha_secret_key', '', 'text', 'Clé secrète reCAPTCHA', 'security'),
('telegram_bot_token', '', 'text', 'Token du bot Telegram', 'notifications'),
('telegram_chat_id', '', 'text', 'ID du chat Telegram', 'notifications'),
('maintenance_mode', '0', 'boolean', 'Mode maintenance (0 = non, 1 = oui)', 'general'),
('max_login_attempts', '5', 'number', 'Nombre maximum de tentatives de connexion', 'security'),
('login_lockout_time', '300', 'number', 'Temps de verrouillage après échecs (secondes)', 'security'),
('session_timeout', '3600', 'number', 'Timeout de session en secondes', 'security'),
('items_per_page', '10', 'number', 'Nombre d''éléments par page', 'general'),
('enable_comments', '1', 'boolean', 'Activer les commentaires sur le blog', 'blog'),
('enable_ratings', '1', 'boolean', 'Activer le système de notation', 'general'),
('enable_newsletter', '1', 'boolean', 'Activer la newsletter', 'newsletter'),
('enable_analytics', '1', 'boolean', 'Activer les statistiques', 'analytics')
ON DUPLICATE KEY UPDATE valeur = valeur;

-- ============================================
-- VÉRIFICATION DE L'INSTALLATION
-- ============================================
-- Afficher les tables créées
SHOW TABLES;

-- Compter les tables créées
SELECT COUNT(*) as total_tables 
FROM information_schema.tables 
WHERE table_schema = 'crex_db';

-- ============================================
-- FIN DU SCRIPT
-- ============================================
-- La base de données est maintenant prête à être utilisée !
-- 
-- TABLES CRÉÉES (Total: 45+ tables) :
-- ✅ users, admin_users, user_sessions (gestion utilisateurs)
-- ✅ pages, site_content, content_history (contenu dynamique)
-- ✅ services, gallery, media_files (services et médias)
-- ✅ blog, blog_categories, blog_tags, blog_post_tags, blog_comments (blog complet)
-- ✅ testimonials (témoignages)
-- ✅ contact_messages, appointments, newsletter, notifications (communication)
-- ✅ site_settings, admin_settings (paramètres)
-- ✅ security_logs, activity_log (journalisation)
-- ✅ visitor_stats (statistiques)
-- ✅ backups (sauvegardes)
-- ✅ faq, team_members, partners, events (fonctionnalités avancées)
-- ✅ translations (multilingue)
-- ✅ dynamic_forms, form_submissions (formulaires dynamiques)
-- ✅ ratings (système de notation)
-- ✅ patients (gestion complète des patients)
-- ✅ traitements (suivi des séances de traitement)
-- ✅ factures, facture_lignes (gestion financière)
-- ✅ paiements (suivi des paiements)
-- ✅ documents_medicaux (stockage des documents)
-- ✅ rappels (système de rappels automatiques)
-- ✅ prescriptions (prescriptions médicales)
-- ✅ statistiques_medicales (statistiques et rapports)
-- ✅ calendrier (calendrier avancé)
-- 
-- PROCHAINES ÉTAPES :
-- 1. Testez le formulaire de contact sur contact.html
-- 2. Connectez-vous à l'administration via admin-login.php
-- 3. Gérez les messages depuis admin.php
-- 4. Accédez au dashboard via admin-dashboard.php
-- 
-- Pour tester la connexion, utilisez : test-db-connection.php
-- ============================================
