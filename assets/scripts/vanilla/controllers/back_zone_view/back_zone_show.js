import L from 'leaflet';
import { parse } from 'wellknown';

const zoneMapElement = document.getElementById('zone_map');
const infoZoneMapElement = document.getElementById('info_zone_map');

if (zoneMapElement && infoZoneMapElement) {
  const map = L.map(zoneMapElement).setView([47, 2], 6);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    referrerPolicy: 'origin',
  }).addTo(map);

  // Correction des chemins des icônes
  delete L.Icon.Default.prototype._getIconUrl;
  L.Icon.Default.mergeOptions({
    iconRetinaUrl: '/build/images/leaflet/marker-icon-2x.png',
    iconUrl: '/build/images/leaflet/marker-icon.png',
    shadowUrl: '/build/images/leaflet/marker-shadow.png',
  });

  const zoneGeoJson = L.geoJson(parse(infoZoneMapElement.dataset.zone));
  map.fitBounds(zoneGeoJson.getBounds());
  zoneGeoJson.addTo(map);

  for (const locationElement of document.getElementsByClassName('location')) {
    const { lat, lng, ref, link, address } = locationElement.dataset;
    const marker = L.marker([lat, lng]);
    marker.bindPopup('<a href="' + link + '">' + ref + '</a> : ' + address);
    marker.addTo(map);
  }
}
