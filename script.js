// Responsive menu mobile - right drawer
const burger = document.querySelector('.burger');
const navList = document.querySelector('.main-nav ul');

function ensureOverlay() {
  let overlay = document.querySelector('.nav-overlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.className = 'nav-overlay';
    document.body.appendChild(overlay);
  }
  return overlay;
}

function openNav() {
  document.body.classList.add('nav-open');
  ensureOverlay().addEventListener('click', closeNav, { once: true });
}

function closeNav() {
  document.body.classList.remove('nav-open');
}

if (burger && navList) {
  burger.addEventListener('click', () => {
    if (document.body.classList.contains('nav-open')) closeNav();
    else openNav();
  });
  // Close when a link is clicked
  navList.querySelectorAll('a').forEach((a) => a.addEventListener('click', closeNav));
  // Close on Escape
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeNav(); });
}
// (Bonus: pour une vraie app, ajouter gestion du focus trap)

// Scroll reveal with IntersectionObserver
const toReveal = document.querySelectorAll('[data-scroll]');
if ('IntersectionObserver' in window && toReveal.length) {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('in-view');
        observer.unobserve(entry.target);
      }
    });
  }, { root: null, rootMargin: '0px', threshold: 0.18 });

  toReveal.forEach((el) => observer.observe(el));
} else {
  // Fallback: show all if IntersectionObserver not supported
  toReveal.forEach((el) => el.classList.add('in-view'));
}

// Hero Slider (Universal - for both home and about pages)
function initHeroSlider(sliderSelector) {
  const heroSlider = document.querySelector(sliderSelector);
  if (!heroSlider) return;

  const slides = heroSlider.querySelectorAll('.hero-slide');
  const indicators = heroSlider.querySelectorAll('.indicator');
  if (slides.length === 0) return;

  let currentSlide = 0;
  const slideInterval = 3000; // 3 seconds per slide

  function showSlide(index) {
    // Remove active class from all
    slides.forEach(slide => slide.classList.remove('active'));
    if (indicators.length > 0) {
      indicators.forEach(ind => ind.classList.remove('active'));
    }
    
    // Add active class to current
    slides[index].classList.add('active');
    if (indicators[index]) {
      indicators[index].classList.add('active');
    }
  }

  function nextSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
    showSlide(currentSlide);
  }

  // Auto-advance slides
  let sliderTimer = setInterval(nextSlide, slideInterval);

  // Manual navigation via indicators
  if (indicators.length > 0) {
    indicators.forEach((indicator, index) => {
      indicator.addEventListener('click', () => {
        currentSlide = index;
        showSlide(currentSlide);
        // Reset timer
        clearInterval(sliderTimer);
        sliderTimer = setInterval(nextSlide, slideInterval);
      });
    });
  }

  // Pause on hover
  heroSlider.addEventListener('mouseenter', () => {
    clearInterval(sliderTimer);
  });

  heroSlider.addEventListener('mouseleave', () => {
    sliderTimer = setInterval(nextSlide, slideInterval);
  });
}

// Initialize sliders
initHeroSlider('.hero-slider'); // Home page
initHeroSlider('.about-hero-slider'); // About page

// Animated Counters
function animateCounter(element) {
  const target = parseInt(element.getAttribute('data-target'));
  const suffix = element.getAttribute('data-suffix') || '';
  const increment = element.getAttribute('data-increment') === 'true';
  const duration = 2000; // 2 seconds
  const stepTime = 20; // Update every 20ms
  const steps = duration / stepTime;
  const stepValue = target / steps;
  let current = 0;

  const timer = setInterval(() => {
    current += stepValue;
    if (current >= target) {
      current = target;
      clearInterval(timer);
      
      // If increment is true, keep incrementing slowly
      if (increment) {
        setInterval(() => {
          const currentValue = parseInt(element.textContent);
          element.textContent = (currentValue + 1) + suffix;
        }, 5000); // Increment every 5 seconds
      }
    }
    element.textContent = Math.floor(current) + suffix;
  }, stepTime);
}

// Observe counters and animate when visible
const counters = document.querySelectorAll('.counter-value');
if (counters.length > 0 && 'IntersectionObserver' in window) {
  const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting && entry.target.textContent === '0') {
        animateCounter(entry.target);
        counterObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.5 });

  counters.forEach((counter) => counterObserver.observe(counter));
}