// ============================================
// Scripts JavaScript pour l'administration CREx
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips et autres fonctionnalités
    initAdminFeatures();
});

function initAdminFeatures() {
    // Confirmation avant suppression
    const deleteButtons = document.querySelectorAll('[data-delete]');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.admin-alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Toggle sidebar on mobile
    const sidebarToggle = document.querySelector('.admin-sidebar-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }
}

// Fonction pour afficher des notifications
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `admin-notification admin-notification-${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Fonction pour charger du contenu via AJAX
function loadContent(url, container) {
    fetch(url)
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
            initAdminFeatures(); // Réinitialiser les fonctionnalités
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur lors du chargement', 'error');
        });
}

