# 🚀 Guide de Déploiement - CREx

## 📋 Prérequis

- Serveur web avec PHP 7.4+ et MySQL 5.7+ (ou MariaDB 10.3+)
- Accès FTP/SFTP ou SSH
- Base de données MySQL créée
- Certificat SSL (HTTPS) recommandé

---

## 1️⃣ Préparation du Projet

### Étape 1.1 : Nettoyer le Projet

**Fichiers à supprimer avant le déploiement :**

```bash
# Fichiers de test et développement
test-db-connection.php
fix-testimonials-table.php
generate-password-hash.php
phpinfo.php
migrate-admin-users.php
install-database.php
init-settings.php

# Documentation de développement (optionnel - garder README.md)
ADMIN_DATABASE_THEME_FIXES_FINAL.md
ADMIN_DATABASE_THEME_FIXES.md
THEME_FIXES_SUMMARY.md
THEME_WHITE_TEXT_FIXES.md
THEME_COMPLETE_FIXES_SUMMARY.md
THEME_IMPROVEMENTS.md
DATABASE_FINAL_SUMMARY.md
DATABASE_SUMMARY.md
DATABASE-CHANGELOG.md
ENHANCEMENT-GUIDE.md
ADMIN-UNIFIED-GUIDE.md
ADMIN-GUIDE.md
ADMIN_DATABASE_GUIDE.md
readme-redist-bins.txt
```

### Étape 1.2 : Configurer la Production

1. **Créer `config.production.php`** :
   ```bash
   cp config.production.php.example config.production.php
   ```

2. **Modifier `config.production.php`** avec vos identifiants :
   - DB_HOST : Adresse de votre serveur MySQL
   - DB_NAME : Nom de votre base de données
   - DB_USER : Utilisateur MySQL
   - DB_PASS : Mot de passe MySQL (FORT)
   - BASE_URL : URL de votre site
   - Autres paramètres selon vos besoins

3. **Modifier `config.php`** pour utiliser la config de production :
   ```php
   // En production, utiliser config.production.php
   if (file_exists(__DIR__ . '/config.production.php')) {
       require_once __DIR__ . '/config.production.php';
   } else {
       // Configuration de développement
       // ... (votre config actuelle)
   }
   ```

### Étape 1.3 : Vérifier les Chemins

Tous les chemins doivent être **relatifs** ou utiliser `BASE_URL` :

- ✅ `assets/css/` → relatif
- ✅ `assets/js/` → relatif
- ✅ `includes/` → relatif
- ✅ Images → relatif ou `BASE_URL`

---

## 2️⃣ Préparation de la Base de Données

### Étape 2.1 : Exporter la Base de Données

1. **Via phpMyAdmin** :
   - Sélectionner la base `crex_db`
   - Cliquer sur "Exporter"
   - Format : SQL
   - Options : Cocher "Ajouter CREATE DATABASE"
   - Cliquer sur "Exécuter"

2. **Via ligne de commande** :
   ```bash
   mysqldump -u root -p crex_db > crex_db_production.sql
   ```

### Étape 2.2 : Sécuriser la Base de Données

1. **Créer un utilisateur MySQL dédié** (recommandé) :
   ```sql
   CREATE USER 'crex_user'@'localhost' IDENTIFIED BY 'mot_de_passe_fort_ici';
   GRANT ALL PRIVILEGES ON crex_db.* TO 'crex_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **Utiliser ce nouvel utilisateur** dans `config.production.php`

### Étape 2.3 : Importer sur le Serveur

1. **Via phpMyAdmin** :
   - Créer la base de données
   - Sélectionner la base
   - Cliquer sur "Importer"
   - Choisir le fichier SQL
   - Cliquer sur "Exécuter"

2. **Via ligne de commande** :
   ```bash
   mysql -u crex_user -p crex_db < crex_db_production.sql
   ```

---

## 3️⃣ Transfert des Fichiers

### Option A : Via FTP/SFTP (FileZilla)

1. **Se connecter au serveur** :
   - Hôte : `ftp.votredomaine.com` ou IP
   - Utilisateur : Votre identifiant FTP
   - Mot de passe : Votre mot de passe FTP
   - Port : 21 (FTP) ou 22 (SFTP)

2. **Transférer les fichiers** :
   - Glisser-déposer tous les fichiers vers `/public_html/` ou `/www/`
   - **Ne pas transférer** :
     - `config.production.php` (créer directement sur le serveur)
     - Fichiers de test
     - Documentation de développement

3. **Vérifier la structure** :
   ```
   public_html/
   ├── index.html
   ├── config.php
   ├── assets/
   ├── includes/
   └── ...
   ```

### Option B : Via Git (Recommandé)

1. **Créer un dépôt Git** :
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   git remote add origin https://github.com/votre-username/crex-site.git
   git push -u origin main
   ```

2. **Connecter à Netlify/Vercel** :
   - Connecter le dépôt GitHub
   - Configurer les variables d'environnement
   - Déployer automatiquement

---

## 4️⃣ Configuration du Serveur

### Étape 4.1 : Créer le fichier `.htaccess`

Voir le fichier `.htaccess` créé pour :
- Redirections HTTPS
- Compression GZIP
- Cache navigateur
- Sécurité
- URLs propres

### Étape 4.2 : Configurer PHP

Vérifier que PHP est configuré avec :
- `upload_max_filesize = 10M`
- `post_max_size = 10M`
- `memory_limit = 128M`
- `max_execution_time = 30`

### Étape 4.3 : Permissions des Fichiers

```bash
# Fichiers : 644
find . -type f -exec chmod 644 {} \;

# Dossiers : 755
find . -type d -exec chmod 755 {} \;

# Dossier uploads : 755 (si nécessaire)
chmod 755 assets/images/gallery/
```

---

## 5️⃣ Configuration du Domaine

### Étape 5.1 : DNS

1. **Acheter un domaine** (si nécessaire)
2. **Configurer les DNS** :
   - Type A : `@` → IP du serveur
   - Type CNAME : `www` → `votredomaine.com`

### Étape 5.2 : SSL/HTTPS

1. **Activer SSL** :
   - Let's Encrypt (gratuit)
   - Certificat fourni par l'hébergeur
   - Cloudflare (gratuit)

2. **Forcer HTTPS** (dans `.htaccess`) :
   ```apache
   RewriteEngine On
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

---

## 6️⃣ Tests Post-Déploiement

### Checklist de Vérification

- [ ] **Pages principales** :
  - [ ] Page d'accueil
  - [ ] Page À propos
  - [ ] Page Services
  - [ ] Page Contact
  - [ ] Page Rendez-vous
  - [ ] Page Galerie

- [ ] **Fonctionnalités** :
  - [ ] Formulaire de contact fonctionne
  - [ ] Formulaire de rendez-vous fonctionne
  - [ ] Authentification admin fonctionne
  - [ ] Interface d'administration accessible
  - [ ] Interface MySQL accessible

- [ ] **Thème** :
  - [ ] Mode clair fonctionne
  - [ ] Mode sombre fonctionne
  - [ ] Basculement de thème fonctionne
  - [ ] Tous les textes lisibles

- [ ] **Sécurité** :
  - [ ] HTTPS actif
  - [ ] Pas d'erreurs PHP visibles
  - [ ] Fichiers sensibles protégés
  - [ ] Base de données sécurisée

- [ ] **Performance** :
  - [ ] Temps de chargement < 3 secondes
  - [ ] Images optimisées
  - [ ] CSS/JS minifiés (optionnel)
  - [ ] Cache activé

---

## 7️⃣ Optimisations Post-Déploiement

### 7.1 : Minification CSS/JS (Optionnel)

```bash
# Utiliser des outils comme :
# - cssnano pour CSS
# - terser pour JS
# - ou un service en ligne
```

### 7.2 : Optimisation des Images

- Convertir en WebP
- Compresser les images
- Utiliser des tailles adaptées

### 7.3 : Cache et CDN

- Activer le cache navigateur (dans `.htaccess`)
- Utiliser un CDN (Cloudflare, etc.)

### 7.4 : Monitoring

- Configurer les logs d'erreurs
- Activer un système de monitoring
- Configurer les sauvegardes automatiques

---

## 8️⃣ Sauvegardes

### Sauvegardes Automatiques

1. **Fichiers** : Sauvegarder régulièrement via FTP ou Git
2. **Base de données** : Script de sauvegarde automatique
3. **Fréquence recommandée** : Quotidienne

### Script de Sauvegarde (exemple)

```bash
#!/bin/bash
# backup.sh
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u crex_user -p crex_db > backups/db_$DATE.sql
tar -czf backups/files_$DATE.tar.gz /path/to/site
```

---

## 🔒 Sécurité

### Checklist de Sécurité

- [ ] Mot de passe MySQL fort
- [ ] Utilisateur MySQL avec privilèges limités
- [ ] HTTPS activé
- [ ] Fichiers sensibles protégés (`.htaccess`)
- [ ] Pas de `phpinfo.php` en production
- [ ] Erreurs PHP masquées
- [ ] Sessions sécurisées
- [ ] Protection CSRF activée
- [ ] Validation des entrées utilisateur
- [ ] Protection contre les injections SQL (PDO préparé)

---

## 📞 Support

En cas de problème :
1. Vérifier les logs d'erreurs
2. Vérifier la configuration de la base de données
3. Vérifier les permissions des fichiers
4. Contacter l'hébergeur si nécessaire

---

**Date de création** : $(date)
**Version** : 1.0

