const greenIcon = new L.Icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});
const redIcon = new L.Icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});
const blueIcon = new L.Icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});
const sudOuest = L.latLng(58, -2);
const nordEst = L.latLng(41, 9);
const bounds = L.latLngBounds(sudOuest, nordEst);
const markers = L.markerClusterGroup();
let map = L.map('map-signalements-view', {
    center: [47.11, -0.01],
    maxBounds: bounds,
    minZoom: 6,
    maxZoom: 18,
    zoom: 7
});
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {}).addTo(map);
let offset = 0;

const popupTemplate = (options) => {
    let TEMPLATE = `<div class="fr-grid-row" style="width: 500px">
                        <div class="fr-col-8">
                            <a href="${options.url}" class="fr-badge fr-badge--${options.type} fr-mt-1v fr-mb-0">#${options.reference}</a>
                            <p><strong>${options.name}</strong><br>
                            <small>
                            ${options.address} <br>
                            ${options.zip} ${options.city}</small></p>
                        </div>
                        <div class="fr-col-4 fr-col--top fr-text--center">
                        <canvas class="fr-col-12" id="gauge-signalement-${options.id}"></canvas>`;
    TEMPLATE += `<span class="fr-badge fr-badge--info fr-m-0">${parseInt(options.score).toFixed(2)}%</span></div>`;
    TEMPLATE += `<div class="fr-p-3v fr-rounded fr-background-alt--blue-france fr-col-12">${options.details}</div>`;
    TEMPLATE += `<ul class="fr-mt-5v">`;
    options.criteres.forEach(critere => {
        TEMPLATE += `<li class="">${critere}</li></ul>`
    })
    TEMPLATE += `</div>`;

    return TEMPLATE;
}
const MAP_MARKERS_PAGE_SIZE = 300;

async function getMarkers(offset) {
    await fetch('?load_markers=true&offset=' + offset, {
        headers: {
            'X-TOKEN': document.querySelector('#carto__js').getAttribute('data-token')
        },
        method: 'POST',
        body: new FormData(document.querySelector('form#bo_filters_form'))
    }).then(r => r.json().then(res => {
        let marker;
        if (res.signalements) {
            res.signalements.forEach(signalement => {
                let crit = [];
                if (signalement.criteres instanceof Array) {
                    signalement?.criteres?.map(c => {
                        crit.push(c?.label);
                    })
                }
                marker = L.marker([signalement.geoloc.lng, signalement.geoloc.lat], {
                    id: signalement.id,
                    status: signalement.statut,
                    address: signalement.adresseOccupant,
                    zip: signalement.cpOccupant,
                    city: signalement.villeOccupant,
                    reference: signalement.reference,
                    score: signalement.scoreCreation,
                    name: signalement.nomOccupant.toUpperCase() +' '+ signalement.prenomOccupant,
                    danger: signalement.scoreCreation > 66 ? 1 : 0,
                    url: `/bo/s/${signalement.uuid}`,
                    criteres: crit,
                    details: `${signalement.details}`
                })
                markers.addLayer(marker);
            })
            map.addLayer(markers);
            // console.log(offset,res.signalements.length)
            if (res.signalements.length === MAP_MARKERS_PAGE_SIZE)
                getMarkers(offset + MAP_MARKERS_PAGE_SIZE)
            else {
                markers.getLayers().forEach((layer, index) => {
                    let type;
                    switch (layer.options.status) {
                        case "1":
                            type = 'info';
                            break;
                        case "2":
                            type = 'success';
                            break;
                        case "6":
                            type = 'error';
                            break;
                    }
                    let HTML = popupTemplate(layer.options);
                    layer.bindPopup(HTML, {
                        maxWidth: 500,
                    }).on('popupopen', (event) => {
                        var px = map.project(event.target._popup._latlng); // find the pixel location on the map where the popup anchor is
                        px.y -= event.target._popup._container.clientHeight / 2; // find the height of the popup container, divide by 2, subtract from the Y axis of marker location
                        map.panTo(map.unproject(px), {animate: true}); // pan to new center
                        gauge = new Gauge(event.popup._wrapper.querySelector(`#gauge-signalement-${layer.options.id}`)).setOptions(opts);
                        gauge.maxValue = 100;
                        gauge.set(layer.options.score);
                        gauge.setMinValue(0);
                        gauge.animationSpeed = 32;
                    });
                })
            }
            let bound = markers.getBounds();
            map.fitBounds([
                [bound._northEast.lat, bound._northEast.lng],
                [bound._southWest.lat, bound._southWest.lng]
            ]);
            document?.querySelector('#container.signalement-invalid')?.classList?.remove('signalement-invalid')
        } else {
            alert('Erreur lors du chargement des signalements...')
        }
    }))
}