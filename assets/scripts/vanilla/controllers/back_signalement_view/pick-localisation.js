import L from 'leaflet';
import 'leaflet.vectorgrid';

const modalLocalisation = document.getElementById('fr-modal-localisation');
const modalPickLocalisation = document.getElementById('fr-modal-pick-localisation');
const modalPickLocalisationMessage = document.getElementById('fr-modal-pick-localisation-message');

if (modalLocalisation) {
  modalLocalisation.addEventListener('dsfr.disclose', () => {
    if (modalLocalisation.dataset.loaded == 'false') {
      modalLocalisation.dataset.loaded = 'true';
      const map = L.map('fr-modal-localisation-map');
      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      }).addTo(map);
      let lat = modalLocalisation.dataset.lat;
      let lng = modalLocalisation.dataset.lng;
      map.setView([lat, lng], 18);
      L.marker([lat, lng]).addTo(map);
    }
  });
} else if (modalPickLocalisation) {
  const map = L.map('fr-modal-pick-localisation-map');
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  }).addTo(map);

  modalPickLocalisation.addEventListener('dsfr.disclose', () => {
    if (modalPickLocalisation.dataset.loaded == 'false') {
      modalPickLocalisation.dataset.loaded = 'true';
      const apiAdresse = 'https://data.geopf.fr/geocodage/search/?q=';
      let address = modalPickLocalisation.dataset.address;
      let postCode = modalPickLocalisation.dataset.postcode;
      fetch(apiAdresse + address + '&postcode=' + postCode)
        .then((response) => response.json())
        .then((json) => {
          // If no result, display error message
          if (!json.features || json.features.length === 0) {
            modalPickLocalisationMessage.innerText =
              "Adresse introuvable, merci de préciser l'adresse grâce au formulaire.";
            modalPickLocalisationMessage.classList.remove('fr-hidden');
            const modalPickLocalisationMap = document.getElementById('fr-modal-pick-localisation-map');
            modalPickLocalisationMap.classList.add('fr-hidden');
            return;
          }
          map.setView(
            [json.features[0].geometry.coordinates[1], json.features[0].geometry.coordinates[0]],
            18
          );
          modalPickLocalisationMessage.classList.add('fr-hidden');
        });
    }
  });

  // Patch pour rendre les couches vectorielles interactives : https://github.com/Leaflet/Leaflet.VectorGrid/issues/274#issuecomment-1371640331
  L.Canvas.Tile.include({
    _onClick: function (e) {
      var point = this._map.mouseEventToLayerPoint(e).subtract(this.getOffset());
      var layer = L.Layer;
      var clickedLayer = L.Layer;

      for (var id in this._layers) {
        layer = this._layers[id];
        if (
          layer.options.interactive &&
          layer._containsPoint(point) &&
          !this._map._draggableMoved(layer)
        ) {
          clickedLayer = layer;
        }
      }
      if (clickedLayer) {
        if (typeof clickedLayer === 'object' && clickedLayer !== null) {
          clickedLayer.fireEvent(e.type, undefined, true);
        }
      }
    },
  });
  // Fin du patch
  var clickedStyle = {
    radius: 5,
    fillColor: '#31e060',
    color: '#ffffff',
    weight: 3,
    fill: true,
    fillOpacity: 1,
    opacity: 1,
  };

  var initialStyle = {
    radius: 5,
    fillColor: '#1452e3',
    color: '#ffffff',
    weight: 3,
    fill: true,
    fillOpacity: 1,
    opacity: 1,
  };

  var vectorTileOptions = {
    rendererFactory: L.canvas.tile,
    vectorTileLayerStyles: {
      default: initialStyle,
    },
    interactive: true,
    getFeatureId: function (f) {
      return f.properties.rnb_id;
    },
  };

  var vectorTileLayer = L.vectorGrid.protobuf(
    'https://rnb-api.beta.gouv.fr/api/alpha/tiles/{x}/{y}/{z}.pbf',
    vectorTileOptions
  );
  vectorTileLayer.addTo(map);
  var previousId;
  vectorTileLayer.on('click', async function (e) {
    var properties = e.layer.properties;
    var rnb_id = properties.rnb_id;
    if (previousId !== undefined) {
      vectorTileLayer.setFeatureStyle(previousId, initialStyle);
    }
    vectorTileLayer.setFeatureStyle(rnb_id, clickedStyle);
    previousId = rnb_id;
    document.getElementById('fr-modal-pick-localisation-rnb-id').value = rnb_id;
    document.getElementById('fr-modal-pick-localisation-submit').disabled = false;
  });
}
