<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blog - CREx</title>
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
          <li class="nav-item"><a href="gallery.php">Galerie</a></li>
          <li class="nav-item"><a href="contact.html">Contact</a></li>
          <li class="nav-item"><a href="appointment.html">Rendez-vous</a></li>
          <li class="nav-item"><a href="blog.php" class="current">Blog</a></li>
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
        <span class="hero-badge animate-bounce">📰 Blog</span>
        <h1>Actualités et Articles CREx</h1>
        <p>Découvrez nos articles sur la santé, le bien-être et la rééducation.</p>
      </div>
    </div>
  </section>

  <!-- ARTICLES BLOG -->
  <?php
  require_once 'config.php';
  try {
    $pdo = getDBConnection();
    $blogQuery = "SELECT b.*, bc.name as category_name 
                 FROM blog b 
                 LEFT JOIN blog_categories bc ON b.category_id = bc.id 
                 WHERE b.status = 'published'
                 ORDER BY b.published_at DESC, b.created_at DESC
                 LIMIT 20";
    $blogStmt = $pdo->query($blogQuery);
    $blogPosts = $blogStmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    $blogPosts = [];
  }
  ?>
  
  <section class="services-main" data-scroll style="padding: 5rem 0;">
    <div class="container">
      <?php if (empty($blogPosts)): ?>
        <div style="text-align: center; padding: 3rem;">
          <p style="font-size: 1.2rem; color: #666;">Aucun article publié pour le moment.</p>
          <p style="color: #999; margin-top: 1rem;">Les articles publiés depuis l'administration apparaîtront ici.</p>
        </div>
      <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem;">
          <?php foreach ($blogPosts as $post): ?>
            <article class="service-card-main" style="display: flex; flex-direction: column;">
              <?php if (!empty($post['image'])): ?>
                <div style="width: 100%; height: 200px; overflow: hidden; border-radius: 1rem 1rem 0 0;">
                  <img src="<?php echo htmlspecialchars($post['image']); ?>" 
                       alt="<?php echo htmlspecialchars($post['title']); ?>"
                       style="width: 100%; height: 100%; object-fit: cover;">
                </div>
              <?php endif; ?>
              <div style="padding: 1.5rem; flex-grow: 1; display: flex; flex-direction: column;">
                <?php if ($post['category_name']): ?>
                  <span style="display: inline-block; background: var(--color-primary); color: white; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.85rem; margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($post['category_name']); ?>
                  </span>
                <?php endif; ?>
                <h3 style="margin-bottom: 0.8rem; color: var(--color-primary);">
                  <?php echo htmlspecialchars($post['title']); ?>
                </h3>
                <?php if ($post['description']): ?>
                  <p style="color: #666; margin-bottom: 1rem; flex-grow: 1;">
                    <?php echo htmlspecialchars(substr($post['description'], 0, 150)) . (strlen($post['description']) > 150 ? '...' : ''); ?>
                  </p>
                <?php endif; ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 1rem; border-top: 1px solid #eee;">
                  <span style="font-size: 0.85rem; color: #999;">
                    <?php echo date('d/m/Y', strtotime($post['published_at'] ?? $post['created_at'])); ?>
                  </span>
                  <?php if ($post['views']): ?>
                    <span style="font-size: 0.85rem; color: #999;">
                      👁️ <?php echo $post['views']; ?> vues
                    </span>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
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

