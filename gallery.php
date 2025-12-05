<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Galerie Photos - CREx</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css?family=Poppins:400,600&display=swap" rel="stylesheet">
  <script src="theme-init.js"></script>
  <script src="dark-mode.js"></script>
</head>
<body>
  <!-- HEADER -->
  <header class="main-header animate-slide-down">
    <div class="container header-flex">
      <div class="logo animate-fade-in">
        <img src="https://cdn.pixabay.com/photo/2017/04/03/01/41/logo-2191012_1280.png" alt="Logo CREx" height="48">
        <span>CREx</span>
      </div>
      <nav class="main-nav">
        <ul>
          <li class="nav-item"><a href="index.html">Accueil</a></li>
          <li class="nav-item"><a href="about.html">À propos</a></li>
          <li class="nav-item"><a href="services.php">Services</a></li>
          <li class="nav-item"><a href="gallery.php" class="current">Galerie</a></li>
          <li class="nav-item"><a href="contact.html">Contact</a></li>
          <li class="nav-item"><a href="appointment.html">Rendez-vous</a></li>
        </ul>
      </nav>
      <button class="theme-toggle" aria-label="Basculer le thème" type="button">
        <span class="theme-icon moon-icon">🌙</span>
      </button>
      <button class="burger" aria-label="Menu mobile">&#9776;</button>
    </div>
  </header>

  <!-- HERO SECTION -->
  <section class="gallery-hero-section" data-scroll>
    <div class="gallery-hero-background">
      <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?auto=format&fit=crop&w=1600&q=80" alt="Centre CREx">
    </div>
    <div class="gallery-hero-overlay"></div>
    <div class="container gallery-hero-content">
      <div class="gallery-hero-text animate-fade-up" data-scroll>
        <span class="hero-badge animate-bounce">📸 Galerie</span>
        <h1>Galerie du Centre CREx</h1>
        <p>Découvrez notre environnement, nos activités et notre esprit d'équipe.</p>
        <a href="index.html" class="btn-secondary">
          Retour à l'accueil
        </a>
      </div>
    </div>
  </section>

  <!-- SECTION PRÉSENTATION -->
  <section class="gallery-intro" data-scroll>
    <div class="container">
      <div class="gallery-intro-text animate-fade-in" data-scroll>
        <span class="section-label">Notre Environnement</span>
        <h2>Un espace pensé pour votre bien-être</h2>
        <p class="section-intro">Chez CREx, nous croyons que l'environnement joue un rôle essentiel dans le processus de réadaptation.</p>
        <p>Découvrez à travers cette galerie nos installations modernes, nos équipements professionnels, et les moments qui reflètent la passion de notre équipe pour votre rétablissement et votre bien-être.</p>
      </div>
    </div>
  </section>

  <!-- GALERIE D'IMAGES -->
  <?php
  require_once 'config.php';
  try {
    $pdo = getDBConnection();
    $galleryQuery = "SELECT * FROM gallery WHERE active = 1 ORDER BY order_index ASC, uploaded_at DESC";
    $galleryStmt = $pdo->query($galleryQuery);
    $galleryItems = $galleryStmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    $galleryItems = [];
  }
  ?>
  <section class="gallery-main" data-scroll>
    <div class="container">
      <div class="section-header">
        <span class="section-label animate-fade-in" data-scroll>Visite Virtuelle</span>
        <h2 class="animate-fade-in" data-scroll>Notre Galerie Photos</h2>
      </div>
      <div class="gallery-grid-new">
        <?php if (empty($galleryItems)): ?>
          <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
            <p style="font-size: 1.2rem; color: #666;">Aucune image dans la galerie pour le moment.</p>
            <p style="color: #999; margin-top: 1rem;">Les images ajoutées depuis l'administration apparaîtront ici.</p>
          </div>
        <?php else: ?>
          <?php 
          $delayClasses = ['with-delay-1', 'with-delay-2', 'with-delay-3'];
          $delayIndex = 0;
          foreach ($galleryItems as $item): 
            $delayClass = $delayClasses[$delayIndex % 3];
            $delayIndex++;
          ?>
            <div class="gallery-item animate-scale-in <?php echo $delayClass; ?>" data-scroll>
              <img src="<?php echo htmlspecialchars($item['image_path']); ?>" 
                   alt="<?php echo htmlspecialchars($item['alt_text'] ?? $item['title'] ?? 'Image CREx'); ?>" 
                   data-full="<?php echo htmlspecialchars($item['image_path']); ?>" 
                   data-description="<?php echo htmlspecialchars($item['description'] ?? $item['title'] ?? ''); ?>">
              <div class="gallery-item-description"><?php echo htmlspecialchars($item['title'] ?? 'Image CREx'); ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- LIGHTBOX (masqué par défaut) -->
  <div id="lightbox" class="lightbox">
    <button class="lightbox-close" aria-label="Fermer">&times;</button>
    <button class="lightbox-prev" aria-label="Image précédente">&#8249;</button>
    <button class="lightbox-next" aria-label="Image suivante">&#8250;</button>
    <div class="lightbox-content">
      <img id="lightbox-image" src="" alt="">
      <p class="lightbox-caption"></p>
    </div>
  </div>

  <!-- SECTION MOMENTS DE VIE -->
  <section class="gallery-moments" data-scroll>
    <div class="container">
      <div class="section-header">
        <span class="section-label animate-fade-in" data-scroll>Vie du Centre</span>
        <h2 class="animate-fade-in" data-scroll>Moments marquants au CREx</h2>
      </div>
      <div class="moments-grid">
        <div class="moment-card animate-fade-up with-delay-1" data-scroll>
          <div class="moment-image">
            <img src="https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=800&q=80" alt="Atelier de rééducation en groupe">
          </div>
          <div class="moment-content">
            <h3>Atelier de rééducation en groupe</h3>
            <p>Des séances collectives pour favoriser l'entraide et la motivation entre patients.</p>
          </div>
        </div>
        <div class="moment-card animate-fade-up with-delay-2" data-scroll>
          <div class="moment-image">
            <img src="https://images.unsplash.com/photo-1559757148-5c350d0d3c56?auto=format&fit=crop&w=800&q=80" alt="Suivi personnalisé">
          </div>
          <div class="moment-content">
            <h3>Suivi personnalisé avec nos spécialistes</h3>
            <p>Un accompagnement sur-mesure adapté aux besoins spécifiques de chaque patient.</p>
          </div>
        </div>
        <div class="moment-card animate-fade-up with-delay-3" data-scroll>
          <div class="moment-image">
            <img src="https://images.unsplash.com/photo-1519690889869-abaa7b96dc23?auto=format&fit=crop&w=800&q=80" alt="Journée de sensibilisation">
          </div>
          <div class="moment-content">
            <h3>Journée de sensibilisation à la mobilité</h3>
            <p>Des événements organisés pour promouvoir la santé et le bien-être dans la communauté.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION VIDÉO -->
  <section class="gallery-video" data-scroll>
    <div class="container">
      <div class="section-header">
        <span class="section-label animate-fade-in" data-scroll>Découverte</span>
        <h2 class="animate-fade-in" data-scroll>Découvrez CREx en vidéo</h2>
      </div>
      <div class="video-wrapper animate-fade-up with-delay-1" data-scroll>
        <div class="video-container">
          <iframe 
            src="https://www.youtube.com/embed/dQw4w9WgXcQ" 
            frameborder="0" 
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
            allowfullscreen
            title="Vidéo de présentation du Centre CREx">
          </iframe>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION APPEL À L'ACTION -->
  <section class="gallery-cta" data-scroll>
    <div class="container">
      <div class="gallery-cta-content animate-fade-up" data-scroll>
        <h2>Envie d'en savoir plus ?</h2>
        <p>Visitez-nous ou contactez notre équipe dès aujourd'hui.</p>
        <a href="contact.html" class="btn-primary pulse-animation">
          <span>Nous contacter</span>
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </a>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="main-footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-logo">CREx - Centre d'Excellence</div>
        <div class="footer-contact">
          <p><strong>📍 Adresse :</strong> Kinindo Ouest, Avenue Beraka N°30 — Bujumbura, Burundi</p>
          <p><strong>☎️ Téléphone :</strong> <a href="tel:+25777510647">+257 77 510 647</a> / <a href="tel:+25761343682">+257 61 343 682</a></p>
          <p><strong>✉️ Email :</strong> <a href="mailto:crex.bdi@gmail.com">crex.bdi@gmail.com</a></p>
        </div>
        <div class="footer-social">
          <h3>Suivez-nous</h3>
          <div class="social-links">
            <a href="https://facebook.com" target="_blank" rel="noopener" aria-label="Facebook">Facebook</a>
            <a href="https://linkedin.com" target="_blank" rel="noopener" aria-label="LinkedIn">LinkedIn</a>
            <a href="https://wa.me/25777510647" target="_blank" rel="noopener" aria-label="WhatsApp">WhatsApp</a>
          </div>
        </div>
      </div>
      <div class="footer-copy">© 2025 CREx – Tous droits réservés.</div>
    </div>
  </footer>
  <script src="script.js"></script>
  <script src="gallery-lightbox.js"></script>
</body>
</html>
