let mapInstance = null;
let markers = [];

function initMap(lat, lng) {
  if (!mapInstance) {
    mapInstance = L.map('map').setView([lat, lng], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '&copy; OpenStreetMap'
    }).addTo(mapInstance);
  } else {
    mapInstance.setView([lat, lng], 11);
  }
  L.circleMarker([lat, lng], { radius: 8, color: '#0b5ed7', fillOpacity: 0.8 })
    .addTo(mapInstance).bindPopup('You');
}

function showFacilitiesOnMap(facilities, userLat, userLng) {
  markers.forEach(m => mapInstance.removeLayer(m));
  markers = [];
  initMap(userLat, userLng);
  facilities.forEach(f => {
    const m = L.marker([f.lat, f.lng]).addTo(mapInstance);
    m.bindPopup(`<strong>${f.name}</strong><br>${f.distance_km} ${t('km_away')}`);
    markers.push(m);
  });
}