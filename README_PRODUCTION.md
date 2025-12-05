# 🚀 CREx - Guide de Déploiement en Production

## 📋 Vue d'Ensemble

Ce document fournit un guide rapide pour déployer le projet CREx en production.

## ⚡ Démarrage Rapide

### 1. Préparation Locale

```bash
# Exécuter le script de préparation complet
php prepare-production.php

# Vérifier que tout est prêt
php pre-deployment-check.php
```

### 2. Créer la Configuration de Production

```bash
# Copier le fichier d'exemple
cp config.production.php.example config.production.php

# Modifier avec vos identifiants de production
# ⚠️ NE JAMAIS COMMITER config.production.php !
```

### 3. Générer le Sitemap

```bash
# Modifier $baseUrl dans le script, puis :
php generate-sitemap.php
```

## 📁 Structure de Production

```
crex_site/
├── index.html
├── config.php
├── config.production.php      ⚠️ À créer sur le serveur
├── .htaccess
├── robots.txt
├── sitemap.xml
├── database.sql
├── assets/
│   ├── css/
│   │   ├── *.css
│   │   └── *.min.css          ✅ Versions minifiées
│   ├── js/
│   │   ├── *.js
│   │   └── *.min.js           ✅ Versions minifiées
│   └── images/
├── includes/
└── admin-*.php
```

## 🔧 Scripts Disponibles

| Script | Description |
|--------|-------------|
| `prepare-production.php` | Préparation complète (nettoyage + minification + export) |
| `pre-deployment-check.php` | Vérification avant déploiement |
| `clean-production.php` | Nettoyage des fichiers inutiles |
| `minify-assets.php` | Minification CSS/JS |
| `export-database.php` | Export de la base de données |
| `optimize-images.php` | Optimisation des images |
| `generate-sitemap.php` | Génération du sitemap.xml |

## 📝 Checklist de Déploiement

### Avant le Déploiement
- [ ] Script de préparation exécuté
- [ ] Vérification pré-déploiement passée
- [ ] config.production.php créé et configuré
- [ ] Base de données exportée
- [ ] Assets minifiés
- [ ] Images optimisées
- [ ] Sitemap généré

### Sur le Serveur
- [ ] Fichiers transférés
- [ ] Base de données importée
- [ ] config.production.php configuré
- [ ] Permissions des fichiers correctes
- [ ] .htaccess actif
- [ ] SSL/HTTPS configuré
- [ ] Domaine configuré

### Après le Déploiement
- [ ] Site accessible
- [ ] Tous les liens fonctionnent
- [ ] Formulaires fonctionnent
- [ ] Interface admin accessible
- [ ] Base de données opérationnelle
- [ ] Thème clair/sombre fonctionne
- [ ] Tests sur mobile effectués

## 🔒 Sécurité

### Mots de Passe
- ✅ Changer le mot de passe admin par défaut
- ✅ Utiliser un mot de passe fort pour MySQL
- ✅ Ne jamais commiter config.production.php

### Permissions
```bash
chmod 644 *.php *.html *.css *.js
chmod 755 assets/ includes/
chmod 600 config.production.php
```

### Fichiers à Protéger
- `config.production.php` : Ne jamais commiter
- `.htaccess` : Protège les fichiers sensibles
- `robots.txt` : Bloque l'indexation des fichiers sensibles

## 📊 Performance

### Optimisations Actives
- ✅ Compression GZIP (.htaccess)
- ✅ Cache navigateur (.htaccess)
- ✅ Assets minifiés
- ✅ Images optimisées (WebP recommandé)

### Recommandations
- Utiliser un CDN pour les assets statiques
- Activer le cache PHP (OPcache)
- Utiliser Cloudflare (gratuit)

## 📞 Support

Pour plus de détails, consulter :
- `DEPLOYMENT_COMPLETE_GUIDE.md` : Guide complet
- `PRODUCTION_PREPARATION.md` : Préparation détaillée

---

**Version** : 1.0
**Dernière mise à jour** : $(date)

