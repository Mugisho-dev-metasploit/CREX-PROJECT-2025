<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nos Services - CREx</title>
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
          <li class="nav-item"><a href="services.php" class="current">Services</a></li>
          <li class="nav-item"><a href="gallery.php">Galerie</a></li>
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
  <section class="services-hero-section" data-scroll>
    <div class="services-hero-background">
      <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?auto=format&fit=crop&w=1600&q=80" alt="Séance de kinésithérapie">
    </div>
    <div class="services-hero-overlay"></div>
    <div class="container services-hero-content">
      <div class="services-hero-text animate-fade-up" data-scroll>
        <span class="hero-badge animate-bounce">✨ Nos Services</span>
        <h1>Nos services professionnels</h1>
        <p>Découvrez notre approche globale du bien-être physique et psychologique.</p>
        <a href="contact.html" class="btn-primary pulse-animation">
          <span>Prendre contact</span>
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </a>
      </div>
    </div>
  </section>

  <!-- SECTION PRÉSENTATION GÉNÉRALE -->
  <section class="services-intro" data-scroll>
    <div class="container services-intro-flex">
      <div class="services-intro-text animate-fade-in" data-scroll>
        <span class="section-label">Notre Approche</span>
        <h2>Un accompagnement complet et personnalisé</h2>
        <p class="lead-text">Le Centre CREx propose un accompagnement complet pour toutes les personnes cherchant à retrouver mobilité, autonomie et équilibre.</p>
        <p>Nos spécialistes travaillent main dans la main pour vous offrir un parcours de soin personnalisé et humain. Chaque patient bénéficie d'une évaluation approfondie, d'un plan de traitement sur-mesure et d'un suivi régulier pour garantir les meilleurs résultats.</p>
        <ul class="about-advantages">
          <li class="advantage-item">
            <div class="advantage-icon">✓</div>
            <span>Évaluation complète et personnalisée</span>
          </li>
          <li class="advantage-item">
            <div class="advantage-icon">✓</div>
            <span>Équipe pluridisciplinaire qualifiée</span>
          </li>
          <li class="advantage-item">
            <div class="advantage-icon">✓</div>
            <span>Suivi régulier et adaptatif</span>
          </li>
        </ul>
      </div>
      <div class="services-intro-image animate-fade-up with-delay-1" data-scroll>
        <div class="image-wrapper">
          <img src="https://images.unsplash.com/photo-1559757148-5c350d0d3c56?auto=format&fit=crop&w=800&q=70" alt="Kinésithérapeute en séance">
          <div class="image-decoration"></div>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION NOS PRINCIPAUX SERVICES -->
  <section class="services-main" data-scroll>
    <div class="container">
      <div class="section-header">
        <span class="section-label animate-fade-in" data-scroll>Nos Offres</span>
        <h2 class="animate-fade-in" data-scroll>Nos principaux services</h2>
        <p class="section-intro animate-fade-in with-delay-1" data-scroll>Des solutions adaptées à chaque besoin pour votre bien-être et votre rétablissement</p>
      </div>
      <?php
      require_once 'config.php';
      try {
        $pdo = getDBConnection();
        $servicesQuery = "SELECT * FROM services WHERE active = 1 ORDER BY order_index ASC, last_updated DESC";
        $servicesStmt = $pdo->query($servicesQuery);
        $services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
        $services = [];
      }
      ?>
      <div class="services-grid-main">
        <?php if (empty($services)): ?>
          <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
            <p style="font-size: 1.2rem; color: #666;">Aucun service disponible pour le moment.</p>
            <p style="color: #999; margin-top: 1rem;">Les services ajoutés depuis l'administration apparaîtront ici.</p>
          </div>
        <?php else: ?>
          <?php 
          $delayClasses = ['with-delay-1', 'with-delay-2', 'with-delay-3'];
          $delayIndex = 0;
          foreach ($services as $service): 
            $delayClass = $delayClasses[$delayIndex % 3];
            $delayIndex++;
          ?>
            <div class="service-card-main animate-scale-in <?php echo $delayClass; ?>" data-scroll>
              <div class="service-icon-wrapper-main">
                <?php if (!empty($service['image_url'])): ?>
                  <img src="<?php echo htmlspecialchars($service['image_url']); ?>" alt="<?php echo htmlspecialchars($service['title']); ?>">
                <?php elseif (!empty($service['icon'])): ?>
                  <i class="<?php echo htmlspecialchars($service['icon']); ?>" style="font-size: 4rem; color: var(--color-primary);"></i>
                <?php else: ?>
                  <i class="fas fa-heart" style="font-size: 4rem; color: var(--color-primary);"></i>
                <?php endif; ?>
              </div>
              <h3><?php echo htmlspecialchars($service['title']); ?></h3>
              <p><?php echo htmlspecialchars($service['description'] ?? $service['full_description'] ?? ''); ?></p>
              <?php if ($service['price']): ?>
                <div style="margin-top: 1rem; font-weight: 600; color: var(--color-primary);">
                  <?php echo number_format($service['price'], 0, ',', ' '); ?> FBU
                  <?php if ($service['duration']): ?>
                    <span style="font-size: 0.9rem; color: #666;"> / <?php echo htmlspecialchars($service['duration']); ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- SECTION POURQUOI CHOISIR CREX -->
  <section class="why-crex-services" data-scroll>
    <div class="container">
      <div class="section-header">
        <span class="section-label animate-fade-in" data-scroll>Notre Engagement</span>
        <h2 class="animate-fade-in" data-scroll>Pourquoi nous faire confiance ?</h2>
      </div>
      <div class="why-crex-services-grid">
        <div class="why-card-services animate-scale-in with-delay-1" data-scroll>
          <div class="why-icon-wrapper-services">👨‍⚕️</div>
          <h3>Équipe expérimentée</h3>
          <p>Des professionnels passionnés et qualifiés à votre service.</p>
        </div>
        <div class="why-card-services animate-scale-in with-delay-2" data-scroll>
          <div class="why-icon-wrapper-services">💡</div>
          <h3>Approche personnalisée</h3>
          <p>Chaque patient est unique, chaque traitement l'est aussi.</p>
        </div>
        <div class="why-card-services animate-scale-in with-delay-3" data-scroll>
          <div class="why-icon-wrapper-services">🏥</div>
          <h3>Infrastructures modernes</h3>
          <p>Des équipements adaptés à chaque besoin et aux dernières normes.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION TÉMOIGNAGES -->
  <section class="testimonials-services" data-scroll>
    <div class="container">
      <div class="section-header">
        <span class="section-label animate-fade-in" data-scroll>Témoignages</span>
        <h2 class="animate-fade-in" data-scroll>Ce que disent nos patients</h2>
      </div>
      <div class="testimonials-services-grid">
        <div class="testimonial-card-services animate-scale-in with-delay-1" data-scroll>
          <div class="quote-icon">"</div>
          <p class="testimonial-text-services">Grâce au CREx, j'ai retrouvé confiance et mobilité. Une équipe bienveillante et compétente qui m'a accompagné tout au long de ma rééducation !</p>
          <div class="testimonial-footer-services">
            <div class="testimonial-avatar-services">
              <span>J</span>
            </div>
            <div class="testimonial-info-services">
              <p class="testimonial-author-services">Jean-Claude N.</p>
              <p class="testimonial-role-services">Patient en rééducation post-traumatique</p>
            </div>
          </div>
        </div>
        <div class="testimonial-card-services animate-scale-in with-delay-2" data-scroll>
          <div class="quote-icon">"</div>
          <p class="testimonial-text-services">L'approche globale du CREx a transformé ma vie. La combinaison kinésithérapie et psychologie a fait toute la différence dans mon parcours de guérison.</p>
          <div class="testimonial-footer-services">
            <div class="testimonial-avatar-services">
              <span>M</span>
            </div>
            <div class="testimonial-info-services">
              <p class="testimonial-author-services">Marie K.</p>
              <p class="testimonial-role-services">Patient en accompagnement global</p>
            </div>
          </div>
        </div>
        <div class="testimonial-card-services animate-scale-in with-delay-3" data-scroll>
          <div class="quote-icon">"</div>
          <p class="testimonial-text-services">Des professionnels à l'écoute, des équipements modernes et un suivi personnalisé. Je recommande vivement le CREx à tous ceux qui recherchent l'excellence.</p>
          <div class="testimonial-footer-services">
            <div class="testimonial-avatar-services">
              <span>P</span>
            </div>
            <div class="testimonial-info-services">
              <p class="testimonial-author-services">Paul M.</p>
              <p class="testimonial-role-services">Patient en kinésithérapie sportive</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION APPEL À L'ACTION -->
  <section class="cta-services" data-scroll>
    <div class="container">
      <div class="cta-services-content animate-fade-up" data-scroll>
        <h2>Besoin d'un accompagnement personnalisé ?</h2>
        <p>Prenez rendez-vous dès aujourd'hui et démarrez votre parcours vers le bien-être.</p>
        <a href="contact.html" class="btn-primary pulse-animation">
          <span>Contactez-nous</span>
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
</body>
</html>
