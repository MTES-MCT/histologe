var map = L.map('zone_map').setView([47, 2], 6);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);

// Correction des chemins des icônes
delete (L.Icon.Default.prototype)._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: '/build/images/leaflet/marker-icon-2x.png',
  iconUrl: '/build/images/leaflet/marker-icon.png',
  shadowUrl: '/build/images/leaflet/marker-shadow.png'
})

var zoneWkt = document.getElementById('info_zone_map').getAttribute('data-zone');
const zoneGeoJson = L.geoJson(wellknown.parse(zoneWkt));
map.fitBounds(zoneGeoJson.getBounds());
zoneGeoJson.addTo(map);

var locations = document.getElementsByClassName('location');
for (var i = 0; i < locations.length; i++) {
    (function(locationElement) {
        var lat = locationElement.getAttribute('data-lat');
        var lng = locationElement.getAttribute('data-lng');
        var ref = locationElement.getAttribute('data-ref');
        var link = locationElement.getAttribute('data-link');
        var address = locationElement.getAttribute('data-address');
        var marker = L.marker([lat, lng]);
        marker.bindPopup('<a href="' + link + '">' + ref + '</a> : ' + address);
        marker.addTo(map);
    })(locations[i]);
}