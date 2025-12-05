# CREx - Centre d'Excellence

Site web du Centre CREx - Centre de réadaptation et d'excellence.

## 🚀 Installation Rapide

### Prérequis
- PHP 7.4+ 
- MySQL 5.7+ ou MariaDB 10.3+
- Serveur web (Apache/Nginx)

### Installation

1. **Cloner ou télécharger le projet**
   ```bash
   git clone https://github.com/votre-username/crex-site.git
   cd crex-site
   ```

2. **Configurer la base de données**
   - Créer la base de données `crex_db`
   - Importer `database.sql` via phpMyAdmin ou ligne de commande
   ```bash
   mysql -u root -p crex_db < database.sql
   ```

3. **Configurer les fichiers**
   - Copier `config.production.php.example` vers `config.production.php`
   - Modifier les identifiants de connexion MySQL
   - Configurer l'URL de base si nécessaire

4. **Permissions**
   ```bash
   chmod 755 assets/images/gallery/
   ```

5. **Accéder au site**
   - Ouvrir `http://localhost/crex-site/` dans votre navigateur

## 📁 Structure du Projet

```
crex_site/
├── assets/
│   ├── css/          # Feuilles de style
│   ├── js/           # Scripts JavaScript
│   └── images/       # Images du site
├── includes/         # Fichiers PHP réutilisables
├── config.php        # Configuration (développement)
├── config.production.php.example  # Template de config production
├── database.sql      # Structure et données de la base
├── .htaccess        # Configuration Apache
└── index.html        # Page d'accueil
```

## 🔧 Configuration

### Développement
Le fichier `config.php` contient la configuration par défaut pour le développement local.

### Production
1. Créer `config.production.php` à partir de `config.production.php.example`
2. Remplir les identifiants de production
3. Le fichier `config.php` détectera automatiquement l'environnement

## 📚 Documentation

- **Guide de déploiement** : Voir `DEPLOYMENT_GUIDE.md`
- **Checklist de production** : Voir `PRODUCTION_CHECKLIST.md`
- **Interface d'administration** : Voir `ADMIN-GUIDE.md`

## 🔒 Sécurité

- ✅ Protection des fichiers sensibles (`.htaccess`)
- ✅ Requêtes SQL préparées (PDO)
- ✅ Validation des entrées utilisateur
- ✅ Protection CSRF
- ✅ Headers de sécurité
- ✅ HTTPS recommandé en production

## 🎨 Fonctionnalités

- ✅ Site responsive (mobile-friendly)
- ✅ Mode clair/sombre
- ✅ Formulaire de contact
- ✅ Prise de rendez-vous
- ✅ Galerie photos
- ✅ Interface d'administration complète
- ✅ Gestion MySQL intégrée

## 📞 Support

Pour toute question ou problème :
- Email : crex.bdi@gmail.com
- Téléphone : +257 77 510 647

## 📄 Licence

Tous droits réservés - CREx © 2025

---

**Version** : 1.0
**Dernière mise à jour** : $(date)

