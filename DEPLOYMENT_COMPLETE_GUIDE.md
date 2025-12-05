# 🚀 Guide Complet de Déploiement - CREx

## 📋 Vue d'Ensemble

Ce guide vous accompagne étape par étape pour déployer votre projet CREx en production.

---

## 1️⃣ Préparation Locale

### Étape 1.1 : Exécuter le Script de Préparation

```bash
php prepare-production.php
```

Ce script automatise :
- ✅ Nettoyage des fichiers inutiles
- ✅ Minification CSS/JS
- ✅ Export de la base de données
- ✅ Vérification de la structure
- ✅ Génération du rapport

### Étape 1.2 : Vérification Pré-Déploiement

```bash
php pre-deployment-check.php
```

Ce script vérifie :
- ✅ Fichiers essentiels présents
- ✅ Configuration correcte
- ✅ Sécurité
- ✅ Base de données
- ✅ Assets

### Étape 1.3 : Créer config.production.php

```bash
cp config.production.php.example config.production.php
```

Puis modifier avec vos identifiants de production :

```php
<?php
// Configuration PRODUCTION - CREx
// ⚠️ NE JAMAIS COMMITER CE FICHIER !

define('DB_HOST', 'mysql.votreserveur.com');
define('DB_NAME', 'crex_db');
define('DB_USER', 'crex_user');
define('DB_PASS', 'MOT_DE_PASSE_FORT_ET_SECURISE');
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL', 'https://votredomaine.com');
define('BASE_PATH', '');

define('DEBUG_MODE', false);
define('DISPLAY_ERRORS', false);
define('LOG_ERRORS', true);

// Clé secrète pour les sessions (GÉNÉRER UNE CLÉ UNIQUE)
define('SESSION_SECRET', bin2hex(random_bytes(32)));

// Email
define('CONTACT_EMAIL', 'contact@votredomaine.com');
define('ADMIN_EMAIL', 'admin@votredomaine.com');
?>
```

### Étape 1.4 : Générer le Sitemap

```bash
php generate-sitemap.php
```

⚠️ **Modifier `$baseUrl` dans le script avec votre domaine réel.**

---

## 2️⃣ Choisir l'Hébergement

### Sites Statiques (HTML, CSS, JS uniquement)
- **Netlify** : Gratuit, rapide, HTTPS automatique
- **Vercel** : Gratuit, excellent pour les sites statiques
- **GitHub Pages** : Gratuit, intégration Git

### Sites Dynamiques (PHP + MySQL)
- **Serveur Partagé** :
  - OVH (à partir de 3€/mois)
  - Hostinger (à partir de 2€/mois)
  - PlanetHoster (à partir de 4€/mois)
  
- **VPS/Cloud** :
  - DigitalOcean (à partir de 4$/mois)
  - AWS (pay-as-you-go)
  - Google Cloud Platform
  - Azure

### Recommandation pour CREx
**Serveur partagé avec PHP 7.4+ et MySQL 5.7+** (OVH, Hostinger, PlanetHoster)

---

## 3️⃣ Transférer les Fichiers

### Option A : FTP/SFTP (FileZilla)

1. **Installer FileZilla** : https://filezilla-project.org/
2. **Se connecter au serveur** :
   - Hôte : `ftp.votredomaine.com` ou IP
   - Utilisateur : Fourni par l'hébergeur
   - Mot de passe : Fourni par l'hébergeur
   - Port : 21 (FTP) ou 22 (SFTP)
3. **Transférer tous les fichiers** vers `/public_html/` ou `/www/`
4. **Vérifier la structure** :
   ```
   public_html/
   ├── index.html
   ├── config.php
   ├── config.production.php  ⚠️ À créer sur le serveur
   ├── .htaccess
   ├── assets/
   ├── includes/
   └── ...
   ```

### Option B : Git (Recommandé)

1. **Créer un dépôt Git** :
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   git remote add origin https://github.com/votre-username/crex-site.git
   git push -u origin main
   ```

2. **Connecter l'hébergeur à Git** :
   - Netlify/Vercel : Connexion automatique
   - Serveur partagé : Utiliser Git + hook de déploiement

3. **Déployer automatiquement** à chaque push

---

## 4️⃣ Configurer la Base de Données

### Étape 4.1 : Créer la Base de Données

Via phpMyAdmin ou ligne de commande :

```sql
CREATE DATABASE crex_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Étape 4.2 : Créer un Utilisateur Dédié

```sql
CREATE USER 'crex_user'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE_FORT';
GRANT ALL PRIVILEGES ON crex_db.* TO 'crex_user'@'localhost';
FLUSH PRIVILEGES;
```

### Étape 4.3 : Importer la Base de Données

**Via phpMyAdmin** :
1. Sélectionner la base `crex_db`
2. Cliquer sur "Importer"
3. Choisir le fichier `database.sql` ou `crex_db_export_*.sql`
4. Cliquer sur "Exécuter"

**Via ligne de commande** :
```bash
mysql -u crex_user -p crex_db < database.sql
```

### Étape 4.4 : Vérifier l'Import

```sql
SHOW TABLES;
SELECT COUNT(*) FROM admin_users;
SELECT COUNT(*) FROM contact_messages;
```

---

## 5️⃣ Configurer le Domaine et SSL

### Étape 5.1 : Configurer les DNS

1. **Acheter un domaine** (si nécessaire)
2. **Configurer les DNS** :
   - Type A : `@` → IP du serveur
   - Type A : `www` → IP du serveur
   - Type CNAME : `www` → `votredomaine.com` (alternative)

### Étape 5.2 : Activer SSL (HTTPS)

**Option A : Let's Encrypt (Gratuit)**
- Via cPanel (si disponible)
- Via Certbot : `certbot --apache -d votredomaine.com`

**Option B : Certificat fourni par l'hébergeur**
- Généralement inclus avec l'hébergement

### Étape 5.3 : Forcer HTTPS dans .htaccess

Décommenter dans `.htaccess` :
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 6️⃣ Tester le Site en Ligne

### Checklist de Test

- [ ] **Page d'accueil** : S'affiche correctement
- [ ] **Navigation** : Tous les liens fonctionnent
- [ ] **Thème** : Mode clair/sombre fonctionne
- [ ] **Formulaires** :
  - [ ] Formulaire de contact
  - [ ] Formulaire de rendez-vous
  - [ ] Formulaire de connexion admin
- [ ] **Base de données** :
  - [ ] Connexion fonctionne
  - [ ] Données s'affichent
  - [ ] Insertions fonctionnent
- [ ] **Interface admin** :
  - [ ] Connexion fonctionne
  - [ ] Dashboard s'affiche
  - [ ] Gestion des messages fonctionne
  - [ ] Administration MySQL fonctionne
- [ ] **Responsive** : Test sur mobile/tablette
- [ ] **Performance** : Temps de chargement acceptable

### Outils de Test

- **Google PageSpeed Insights** : https://pagespeed.web.dev/
- **GTmetrix** : https://gtmetrix.com/
- **W3C Validator** : https://validator.w3.org/

---

## 7️⃣ Sécuriser et Optimiser

### Sécurité

1. **Changer les mots de passe par défaut**
   ```sql
   UPDATE admin_users SET password_hash = '$2y$10$...' WHERE username = 'mugisho';
   ```

2. **Vérifier les permissions des fichiers**
   ```bash
   chmod 644 *.php *.html *.css *.js
   chmod 755 assets/ includes/
   chmod 600 config.production.php
   ```

3. **Activer le firewall** (si VPS)
   ```bash
   ufw enable
   ufw allow 22/tcp
   ufw allow 80/tcp
   ufw allow 443/tcp
   ```

4. **Mettre à jour PHP et MySQL**
   - PHP 7.4+ recommandé
   - MySQL 5.7+ ou MariaDB 10.3+

### Optimisation

1. **Activer le cache navigateur** (déjà dans .htaccess)
2. **Utiliser un CDN** (optionnel) :
   - Cloudflare (gratuit)
   - jsDelivr pour les bibliothèques
3. **Optimiser les images** :
   - Convertir en WebP
   - Compresser avec TinyPNG
4. **Minifier les assets** (déjà fait avec le script)

---

## 8️⃣ Monitoring et Maintenance

### Sauvegardes

**Automatiser les sauvegardes** :
- Fichiers : Tous les jours
- Base de données : Tous les jours

**Script de sauvegarde** (à créer sur le serveur) :
```bash
#!/bin/bash
# backup.sh
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u crex_user -p crex_db > /backups/db_$DATE.sql
tar -czf /backups/files_$DATE.tar.gz /var/www/html/
```

### Monitoring

1. **Logs d'erreurs** : Vérifier régulièrement `logs/`
2. **Uptime monitoring** : UptimeRobot (gratuit)
3. **Analytics** : Google Analytics

### Maintenance

- **Mises à jour** : PHP, MySQL, bibliothèques
- **Sécurité** : Vérifier les vulnérabilités
- **Performance** : Optimiser régulièrement

---

## 9️⃣ Checklist Finale

### Avant le Déploiement
- [ ] Script de préparation exécuté
- [ ] Vérification pré-déploiement passée
- [ ] config.production.php créé
- [ ] Base de données exportée
- [ ] Assets minifiés
- [ ] Images optimisées
- [ ] Sitemap généré
- [ ] robots.txt créé

### Après le Déploiement
- [ ] Fichiers transférés
- [ ] Base de données importée
- [ ] config.production.php configuré
- [ ] Domaine configuré
- [ ] SSL activé
- [ ] Tests effectués
- [ ] Mots de passe changés
- [ ] Sauvegardes configurées

---

## 🔧 Scripts Utiles

### Préparation
```bash
php prepare-production.php          # Préparation complète
php pre-deployment-check.php       # Vérification
php clean-production.php           # Nettoyage uniquement
php minify-assets.php              # Minification uniquement
php export-database.php           # Export DB uniquement
php generate-sitemap.php           # Générer sitemap
```

### Après Déploiement
```bash
# Tester la connexion DB
php -r "require 'config.php'; getDBConnection(); echo 'OK';"

# Vérifier les permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
```

---

## 📞 Support

En cas de problème :
1. Vérifier les logs : `logs/`
2. Vérifier la configuration : `config.production.php`
3. Tester la connexion DB
4. Vérifier les permissions
5. Consulter les logs du serveur

---

**Date de création** : $(date)
**Version** : 1.0
**Statut** : ✅ Complet

