/**
 * Admin Panel JavaScript - CREx
 * Dark Mode, AJAX, Mobile Menu
 */

(function() {
    'use strict';

    // ============================================
    // Dark Mode Toggle with Smooth Animation
    // Use the centralized DarkMode API if available
    // ============================================
    const darkModeToggle = document.getElementById('darkModeToggle');
    const html = document.documentElement;
    const darkModeIcon = document.getElementById('darkModeIcon');
    
    // Use centralized DarkMode system if available, otherwise fallback
    function updateDarkModeIcon(theme) {
        if (darkModeIcon) {
            if (theme === 'dark') {
                darkModeIcon.className = 'fas fa-sun';
            } else {
                darkModeIcon.className = 'fas fa-moon';
            }
        }
    }
    
    // Wait for DarkMode system to initialize
    if (window.DarkMode) {
        // Use centralized system
        document.addEventListener('themechange', function(e) {
            updateDarkModeIcon(e.detail.theme);
        });
    } else {
        // Fallback: Load saved theme preference
        const savedTheme = localStorage.getItem('crex_theme_preference') || 
                          (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        html.setAttribute('data-theme', savedTheme);
        updateDarkModeIcon(savedTheme);
        
        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', function(e) {
                e.preventDefault();
                const currentTheme = html.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                // Add transition class for smooth animation
                document.body.classList.add('theme-transition-ready');
                
                // Update theme
                html.setAttribute('data-theme', newTheme);
                localStorage.setItem('crex_theme_preference', newTheme);
                updateDarkModeIcon(newTheme);
                
                // Add ripple effect
                const ripple = document.createElement('span');
                ripple.style.position = 'absolute';
                ripple.style.borderRadius = '50%';
                ripple.style.background = 'rgba(255, 255, 255, 0.5)';
                ripple.style.width = '0';
                ripple.style.height = '0';
                ripple.style.left = '50%';
                ripple.style.top = '50%';
                ripple.style.transform = 'translate(-50%, -50%)';
                ripple.style.animation = 'ripple 0.6s ease-out';
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        }
    }
    
    function updateDarkModeIcon(theme) {
        if (darkModeIcon) {
            darkModeIcon.style.transition = 'transform 0.3s ease';
            darkModeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            darkModeIcon.style.transform = 'rotate(360deg)';
            setTimeout(() => {
                darkModeIcon.style.transform = 'rotate(0deg)';
            }, 300);
        }
    }
    
    // Add ripple animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                width: 200px;
                height: 200px;
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

    // ============================================
    // Mobile Sidebar Toggle with Smooth Animation
    // ============================================
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    function toggleSidebar() {
        if (sidebar && sidebarOverlay) {
            const isOpen = sidebar.classList.contains('show');
            
            if (isOpen) {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
                document.body.style.overflow = '';
            } else {
                sidebar.classList.add('show');
                sidebarOverlay.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }
    }
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            toggleSidebar();
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('show')) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                toggleSidebar();
            }
        }
    });
    
    // Close sidebar on window resize if window becomes larger
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && sidebar && sidebar.classList.contains('show')) {
            toggleSidebar();
        }
    });

    // ============================================
    // AJAX Functions
    // ============================================
    function getCSRFToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.getAttribute('content') : '';
    }

    function showAlert(message, type = 'success') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        alertDiv.style.position = 'fixed';
        alertDiv.style.top = '70px';
        alertDiv.style.right = '20px';
        alertDiv.style.zIndex = '9999';
        alertDiv.style.minWidth = '300px';
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.style.opacity = '0';
            alertDiv.style.transition = 'opacity 0.3s';
            setTimeout(() => alertDiv.remove(), 300);
        }, 3000);
    }

    function showLoading(element) {
        if (element) {
            element.disabled = true;
            element.innerHTML = '<span class="loading-spinner"></span> Chargement...';
        }
    }

    function hideLoading(element, originalText) {
        if (element) {
            element.disabled = false;
            element.innerHTML = originalText;
        }
    }

    // ============================================
    // Messages Management AJAX
    // ============================================
    
    // Mark message as read/unread
    window.markMessageRead = function(messageId, action) {
        const formData = new FormData();
        formData.append('message_id', messageId);
        formData.append('action', action);
        formData.append('csrf_token', getCSRFToken());
        
        fetch('admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                // Reload messages list
                loadMessages();
            } else {
                showAlert(data.message || 'Erreur', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Erreur de connexion', 'danger');
        });
    };

    // Delete message
    window.deleteMessage = function(messageId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce message ?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('message_id', messageId);
        formData.append('action', 'delete');
        formData.append('csrf_token', getCSRFToken());
        
        fetch('admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                // Remove message from DOM
                const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
                if (messageElement) {
                    messageElement.style.opacity = '0';
                    messageElement.style.transition = 'opacity 0.3s';
                    setTimeout(() => messageElement.remove(), 300);
                }
            } else {
                showAlert(data.message || 'Erreur', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Erreur de connexion', 'danger');
        });
    };

    // Load messages via AJAX
    window.loadMessages = function(page = 1, filter = 'all', search = '') {
        const messagesContainer = document.getElementById('messagesContainer');
        if (!messagesContainer) return;
        
        showLoading(messagesContainer);
        messagesContainer.innerHTML = '<div class="text-center p-4"><div class="loading-spinner"></div></div>';
        
        const url = new URL('admin-ajax.php', window.location.origin);
        url.searchParams.append('action', 'get_messages');
        url.searchParams.append('page', page);
        url.searchParams.append('filter', filter);
        url.searchParams.append('search', search);
        
        fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messagesContainer.innerHTML = data.html;
                updatePagination(data.pagination);
            } else {
                messagesContainer.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Erreur') + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            messagesContainer.innerHTML = '<div class="alert alert-danger">Erreur de connexion</div>';
        });
    };

    // View message in modal
    window.viewMessage = function(messageId) {
        const url = new URL('admin-ajax.php', window.location.origin);
        url.searchParams.append('action', 'get_message');
        url.searchParams.append('message_id', messageId);
        
        fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = new bootstrap.Modal(document.getElementById('messageModal'));
                document.getElementById('messageModalTitle').textContent = 'Message de ' + data.message.nom;
                document.getElementById('messageModalBody').innerHTML = formatMessage(data.message);
                modal.show();
            } else {
                showAlert(data.message || 'Erreur', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Erreur de connexion', 'danger');
        });
    };

    function formatMessage(message) {
        return `
            <div class="mb-3">
                <strong>Nom:</strong> ${escapeHtml(message.nom)}<br>
                <strong>Email:</strong> <a href="mailto:${escapeHtml(message.email)}">${escapeHtml(message.email)}</a><br>
                ${message.telephone ? `<strong>Téléphone:</strong> <a href="tel:${escapeHtml(message.telephone)}">${escapeHtml(message.telephone)}</a><br>` : ''}
                ${message.whatsapp ? `<strong>WhatsApp:</strong> ${escapeHtml(message.whatsapp)}<br>` : ''}
                ${message.sujet ? `<strong>Sujet:</strong> ${escapeHtml(message.sujet)}<br>` : ''}
                <strong>Date:</strong> ${formatDate(message.date_creation)}<br>
            </div>
            <div class="border-top pt-3">
                <strong>Message:</strong>
                <p class="mt-2">${escapeHtml(message.message).replace(/\n/g, '<br>')}</p>
            </div>
        `;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('fr-FR');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ============================================
    // Search with Debounce
    // ============================================
    const searchInput = document.getElementById('messageSearch');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchValue = this.value;
            searchTimeout = setTimeout(() => {
                if (searchValue.length >= 3 || searchValue.length === 0) {
                    loadMessages(1, getCurrentFilter(), searchValue);
                }
            }, 500);
        });
    }

    function getCurrentFilter() {
        const activeFilter = document.querySelector('.filter-btn.active');
        return activeFilter ? activeFilter.dataset.filter || 'all' : 'all';
    }

    function updatePagination(pagination) {
        const paginationContainer = document.getElementById('paginationContainer');
        if (!paginationContainer || !pagination) return;
        
        let html = '';
        if (pagination.currentPage > 1) {
            html += `<a href="#" onclick="loadMessages(${pagination.currentPage - 1}); return false;">« Précédent</a>`;
        }
        
        for (let i = pagination.startPage; i <= pagination.endPage; i++) {
            if (i === pagination.currentPage) {
                html += `<span class="current">${i}</span>`;
            } else {
                html += `<a href="#" onclick="loadMessages(${i}); return false;">${i}</a>`;
            }
        }
        
        if (pagination.currentPage < pagination.totalPages) {
            html += `<a href="#" onclick="loadMessages(${pagination.currentPage + 1}); return false;">Suivant »</a>`;
        }
        
        paginationContainer.innerHTML = html;
    }

    // ============================================
    // Initialize on page load
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    });

})();

// ============================================
// Fonctions pour le modal de réponse aux rendez-vous
// ============================================

function openResponseModal(appointmentId, action, clientName, clientEmail, clientPhone, service, date, time) {
    document.getElementById('responseAppointmentId').value = appointmentId;
    document.getElementById('responseAction').value = action;
    document.getElementById('responseClientName').textContent = clientName;
    document.getElementById('responseClientEmail').textContent = clientEmail;
    document.getElementById('responseClientPhone').textContent = clientPhone;
    document.getElementById('responseService').textContent = service;
    document.getElementById('responseDate').textContent = date;
    document.getElementById('responseTime').textContent = time;
    document.getElementById('responseMessage').value = '';
    document.getElementById('responseAlert').classList.add('d-none');
    
    // Changer le titre selon l'action
    const modalTitle = document.getElementById('responseModalLabel');
    const sendBtn = document.getElementById('sendResponseBtn');
    
    if (action === 'confirm') {
        modalTitle.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i>Confirmer le rendez-vous';
        sendBtn.innerHTML = '<i class="fas fa-check me-2"></i>Confirmer et envoyer';
        sendBtn.className = 'btn btn-success';
    } else if (action === 'cancel') {
        modalTitle.innerHTML = '<i class="fas fa-times-circle text-warning me-2"></i>Annuler le rendez-vous';
        sendBtn.innerHTML = '<i class="fas fa-times me-2"></i>Annuler et envoyer';
        sendBtn.className = 'btn btn-warning';
    } else if (action === 'complete') {
        modalTitle.innerHTML = '<i class="fas fa-check-double text-info me-2"></i>Marquer comme terminé';
        sendBtn.innerHTML = '<i class="fas fa-check-double me-2"></i>Terminer et envoyer';
        sendBtn.className = 'btn btn-info';
    } else {
        modalTitle.innerHTML = '<i class="fas fa-reply me-2"></i>Répondre au rendez-vous';
        sendBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Envoyer';
        sendBtn.className = 'btn btn-primary';
    }
}

function sendResponse() {
    const form = document.getElementById('responseForm');
    const formData = new FormData(form);
    const sendBtn = document.getElementById('sendResponseBtn');
    const alertDiv = document.getElementById('responseAlert');
    
    // Désactiver le bouton pendant l'envoi
    sendBtn.disabled = true;
    const originalBtnText = sendBtn.innerHTML;
    sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Envoi...';
    
    // Masquer l'alerte précédente
    alertDiv.classList.add('d-none');
    
    fetch('send-appointment-response.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alertDiv.className = 'alert alert-success';
            alertDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + (data.message || 'Message envoyé avec succès');
            alertDiv.classList.remove('d-none');
            
            // Si WhatsApp est demandé, ouvrir le lien
            if (data.whatsapp_link) {
                const sendMethod = formData.get('send_method');
                if (sendMethod === 'whatsapp' || sendMethod === 'both') {
                    setTimeout(() => {
                        window.open(data.whatsapp_link, '_blank');
                    }, 500);
                }
            }
            
            // Rediriger après 1.5 secondes
            setTimeout(() => {
                window.location.href = 'admin.php?section=appointments&success=' + encodeURIComponent((data.status || 'Rendez-vous') + ' - Réponse envoyée avec succès');
            }, 1500);
        } else {
            alertDiv.className = 'alert alert-danger';
            alertDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + (data.error || 'Erreur lors de l\'envoi');
            alertDiv.classList.remove('d-none');
            sendBtn.disabled = false;
            sendBtn.innerHTML = originalBtnText;
        }
    })
    .catch(error => {
        alertDiv.className = 'alert alert-danger';
        alertDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Erreur: ' + error.message;
        alertDiv.classList.remove('d-none');
        sendBtn.disabled = false;
        sendBtn.innerHTML = originalBtnText;
    });
}

// ============================================
// Fonctions pour les Services
// ============================================

function openServiceModal(serviceId = null) {
    const modal = document.getElementById('serviceModal');
    const form = document.getElementById('serviceForm');
    const title = document.getElementById('serviceModalTitle');
    const actionInput = document.getElementById('serviceAction');
    const serviceIdInput = document.getElementById('serviceId');
    
    // Réinitialiser le formulaire
    form.reset();
    
    if (serviceId) {
        // Mode édition - charger les données
        title.textContent = 'Modifier le service';
        actionInput.value = 'update_service';
        serviceIdInput.value = serviceId;
        
        // Charger les données via AJAX
        fetch(`admin-ajax.php?action=get_service&id=${serviceId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const service = data.service;
                    document.getElementById('service_title').value = service.title || '';
                    document.getElementById('service_description').value = service.description || '';
                    document.getElementById('service_full_description').value = service.full_description || '';
                    document.getElementById('service_icon').value = service.icon || '';
                    document.getElementById('service_image_url').value = service.image_url || '';
                    document.getElementById('service_price').value = service.price || '';
                    document.getElementById('service_duration').value = service.duration || '';
                    document.getElementById('service_order').value = service.order_index || 0;
                    document.getElementById('service_active').checked = service.active == 1;
                    document.getElementById('service_featured').checked = service.featured == 1;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
    } else {
        // Mode création
        title.textContent = 'Ajouter un service';
        actionInput.value = 'add_service';
        serviceIdInput.value = '';
    }
}

// ============================================
// Fonctions pour le Blog
// ============================================

function openBlogModal(blogId = null) {
    const modal = document.getElementById('blogModal');
    const form = document.getElementById('blogForm');
    const title = document.getElementById('blogModalTitle');
    const actionInput = document.getElementById('blogAction');
    const blogIdInput = document.getElementById('blogId');
    
    // Réinitialiser le formulaire
    form.reset();
    
    if (blogId) {
        // Mode édition - charger les données
        title.textContent = 'Modifier l\'article';
        actionInput.value = 'update_blog';
        blogIdInput.value = blogId;
        
        // Charger les données via AJAX
        fetch(`admin-ajax.php?action=get_blog&id=${blogId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const post = data.post;
                    document.getElementById('blog_title').value = post.title || '';
                    document.getElementById('blog_description').value = post.description || '';
                    document.getElementById('blog_content').value = post.content || '';
                    document.getElementById('blog_image').value = post.image || '';
                    document.getElementById('blog_category').value = post.category_id || '';
                    document.getElementById('blog_status').value = post.status || 'draft';
                    document.getElementById('blog_meta_title').value = post.meta_title || '';
                    document.getElementById('blog_meta_description').value = post.meta_description || '';
                    document.getElementById('blog_featured').checked = post.featured == 1;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
    } else {
        // Mode création
        title.textContent = 'Nouvel article';
        actionInput.value = 'add_blog';
        blogIdInput.value = '';
    }
}

function openCategoryModal() {
    // Pour l'instant, juste ouvrir le modal
    // La gestion complète des catégories sera implémentée plus tard
}

function editGalleryItem(galleryId) {
    // Pour l'instant, juste afficher un message
    alert('La fonction d\'édition de la galerie sera disponible prochainement.');
}


