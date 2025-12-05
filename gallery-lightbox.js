/**
 * Lightbox Gallery - JavaScript pur
 * Gère l'ouverture, la navigation et la fermeture de la lightbox
 */

(function() {
  'use strict';

  // Éléments DOM
  const lightbox = document.getElementById('lightbox');
  const lightboxImage = document.getElementById('lightbox-image');
  const lightboxCaption = document.querySelector('.lightbox-caption');
  const lightboxClose = document.querySelector('.lightbox-close');
  const lightboxPrev = document.querySelector('.lightbox-prev');
  const lightboxNext = document.querySelector('.lightbox-next');
  const galleryItems = document.querySelectorAll('.gallery-item img');
  
  let currentIndex = 0;
  let images = [];

  // Initialisation : collecter toutes les images
  function initGallery() {
    images = Array.from(galleryItems).map((img, index) => ({
      src: img.getAttribute('data-full') || img.src,
      alt: img.alt,
      description: img.getAttribute('data-description') || img.alt,
      index: index
    }));
  }

  // Ouvrir la lightbox
  function openLightbox(index) {
    currentIndex = index;
    updateLightbox();
    lightbox.classList.add('active');
    document.body.style.overflow = 'hidden'; // Empêcher le scroll
    
    // Animation fade-in
    setTimeout(() => {
      lightbox.classList.add('fade-in');
    }, 10);
  }

  // Fermer la lightbox
  function closeLightbox() {
    lightbox.classList.remove('fade-in');
    setTimeout(() => {
      lightbox.classList.remove('active');
      document.body.style.overflow = ''; // Restaurer le scroll
    }, 300);
  }

  // Mettre à jour l'image affichée
  function updateLightbox() {
    if (images.length === 0) return;
    
    const currentImage = images[currentIndex];
    lightboxImage.src = currentImage.src;
    lightboxImage.alt = currentImage.alt;
    lightboxCaption.textContent = currentImage.description;
    
    // Précharger les images adjacentes
    preloadAdjacentImages();
  }

  // Précharger les images précédente et suivante
  function preloadAdjacentImages() {
    const prevIndex = currentIndex > 0 ? currentIndex - 1 : images.length - 1;
    const nextIndex = currentIndex < images.length - 1 ? currentIndex + 1 : 0;
    
    const prevImg = new Image();
    prevImg.src = images[prevIndex].src;
    
    const nextImg = new Image();
    nextImg.src = images[nextIndex].src;
  }

  // Image suivante
  function nextImage() {
    currentIndex = (currentIndex + 1) % images.length;
    updateLightbox();
  }

  // Image précédente
  function prevImage() {
    currentIndex = (currentIndex - 1 + images.length) % images.length;
    updateLightbox();
  }

  // Event Listeners sur les images de la galerie
  galleryItems.forEach((img, index) => {
    img.addEventListener('click', () => {
      openLightbox(index);
    });
    
    // Ajouter un curseur pointer
    img.style.cursor = 'pointer';
    
    // Lazy loading optionnel
    if ('loading' in HTMLImageElement.prototype) {
      img.loading = 'lazy';
    }
  });

  // Event Listeners sur les boutons de la lightbox
  if (lightboxClose) {
    lightboxClose.addEventListener('click', closeLightbox);
  }
  
  if (lightboxPrev) {
    lightboxPrev.addEventListener('click', (e) => {
      e.stopPropagation();
      prevImage();
    });
  }
  
  if (lightboxNext) {
    lightboxNext.addEventListener('click', (e) => {
      e.stopPropagation();
      nextImage();
    });
  }

  // Fermer en cliquant sur le fond
  if (lightbox) {
    lightbox.addEventListener('click', (e) => {
      if (e.target === lightbox) {
        closeLightbox();
      }
    });
  }

  // Navigation au clavier
  document.addEventListener('keydown', (e) => {
    if (!lightbox.classList.contains('active')) return;
    
    switch(e.key) {
      case 'Escape':
        closeLightbox();
        break;
      case 'ArrowLeft':
        prevImage();
        break;
      case 'ArrowRight':
        nextImage();
        break;
    }
  });

  // Initialisation
  initGallery();
})();
