var map = L.map('zone_map').setView([43.316667, 3.466667], 15);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);
var zoneWkt = document.getElementById('infos').getAttribute('data-zone');
omnivore.wkt.parse(zoneWkt).addTo(map);
/*var locations = document.getElementsByClassName('location');
for (var i = 0; i < locations.length; i++) {
    var lat = locations[i].getAttribute('data-lat');
    var lng = locations[i].getAttribute('data-lng');
    L.marker([lat, lng]).addTo(map);
}*/