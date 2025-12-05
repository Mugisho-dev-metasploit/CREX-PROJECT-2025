/**
 * Contact Page - Google Maps Integration - CREx
 * Initialise la carte Google Maps avec les coordonnées du centre
 */

// Fonction d'initialisation de la carte (appelée par Google Maps API)
function initMap() {
    // Coordonnées du Centre CREx
    const crexLocation = { lat: -3.404847, lng: 29.349306 };
    
    const mapElement = document.getElementById('google-map');
    if (!mapElement) {
        console.error('Element #google-map not found');
        return;
    }
    
    // Créer la carte
    const map = new google.maps.Map(mapElement, {
        zoom: 16,
        center: crexLocation,
        mapTypeId: 'roadmap',
        styles: [
            {
                featureType: 'poi',
                elementType: 'labels',
                stylers: [{ visibility: 'on' }]
            }
        ]
    });
    
    // Créer le marqueur
    const marker = new google.maps.Marker({
        position: crexLocation,
        map: map,
        title: 'Centre CREx - Kinindo Ouest, Avenue Beraka N°30',
        animation: google.maps.Animation.DROP,
        icon: {
            url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png'
        }
    });
    
    // Contenu de l'info window (utiliser createElement pour éviter les styles inline)
    const infoWindowDiv = document.createElement('div');
    infoWindowDiv.className = 'map-info-window';
    infoWindowDiv.innerHTML = `
        <h3>Centre CREx</h3>
        <p><strong>📍 Adresse :</strong><br>Kinindo Ouest, Avenue Beraka N°30<br>Bujumbura, Burundi</p>
        <p><strong>📞 Téléphone :</strong><br>+257 77 510 647 / +257 61 343 682</p>
        <p><strong>✉️ Email :</strong><br>crex.bdi@gmail.com</p>
    `;
    
    const infoWindowContent = infoWindowDiv;
    
    // Info window
    const infoWindow = new google.maps.InfoWindow({
        content: infoWindowContent
    });
    
    // Ouvrir l'info window au clic sur le marqueur
    marker.addListener('click', () => {
        infoWindow.open(map, marker);
    });
    
    // Ouvrir automatiquement l'info window au chargement
    infoWindow.open(map, marker);
}

// Gestion des erreurs de chargement de l'API Google Maps
window.gm_authFailure = function() {
    const mapElement = document.getElementById('google-map');
    if (mapElement) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'map-error-message';
        errorDiv.innerHTML = `
            <h3>⚠️ Erreur de chargement de la carte</h3>
            <p>Impossible de charger la carte Google Maps. Veuillez vérifier votre connexion internet ou contacter l'administrateur.</p>
            <p><strong>📍 Adresse :</strong> Kinindo Ouest, Avenue Beraka N°30 — Bujumbura, Burundi</p>
        `;
        mapElement.innerHTML = '';
        mapElement.appendChild(errorDiv);
    }
};

