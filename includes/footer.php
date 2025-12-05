    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Dark Mode Manager (loaded early for theme detection) -->
    <script src="assets/js/dark-mode.js"></script>
    
    <!-- Custom Admin JS -->
    <script src="assets/js/admin.js"></script>
    
    <!-- Initialize AOS with Enhanced Settings -->
    <script>
        // Check for reduced motion preference
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        
        AOS.init({
            duration: prefersReducedMotion ? 0 : 800,
            easing: 'ease-in-out-cubic',
            once: true,
            offset: 50,
            delay: 0,
            disable: prefersReducedMotion ? true : false,
            startEvent: 'DOMContentLoaded',
            animatedClassName: 'aos-animate',
            useClassNames: false,
            disableMutationObserver: false,
            debounceDelay: 50,
            throttleDelay: 99
        });
        
        // Add smooth scroll behavior
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href !== '#' && href !== '#!') {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            });
        });
        
        // Add loading state to buttons
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    submitBtn.disabled = true;
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<span class="loading-spinner"></span> En cours...';
                    
                    // Re-enable after 10 seconds as fallback
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }, 10000);
                }
            });
        });
        
        // Add hover effect to cards
        document.querySelectorAll('.admin-card, .admin-stat-card, .message-item').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            });
        });
        
        // Add focus visible polyfill for better accessibility
        if (!CSS.supports('selector(:focus-visible)')) {
            document.querySelectorAll('a, button, input, select, textarea').forEach(el => {
                el.addEventListener('focus', function() {
                    this.classList.add('focus-visible');
                });
                el.addEventListener('blur', function() {
                    this.classList.remove('focus-visible');
                });
            });
        }
    </script>
</body>
</html>

