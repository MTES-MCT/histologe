import L from 'leaflet';
import 'leaflet.vectorgrid';

const modalLocalisation = document.getElementById('fr-modal-localisation');
const modalPickLocalisation = document.getElementById('fr-modal-pick-localisation');
const modalPickLocalisationMessage = document.getElementById('fr-modal-pick-localisation-message');

if (modalLocalisation) {
  let map;
  modalLocalisation.addEventListener('dsfr.disclose', () => {
    if (!map) {
      map = L.map('fr-modal-localisation-map');
      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        referrerPolicy: 'origin',
      }).addTo(map);
    }
    let btn = document.getElementById('fr-modal-localisation-btn');
    let lat = btn.dataset.lat;
    let lng = btn.dataset.lng;
    map.setView([lat, lng], 18);
    L.marker([lat, lng]).addTo(map);
  });
}
if (modalPickLocalisation) {
  let map;
  let vectorTileLayer;
  let previousId;

  modalPickLocalisation.addEventListener('dsfr.disclose', () => {
    // Chercher les champs d'adresse dans la page pour mettre à jour les data-attributes
    // Essayer plusieurs sélecteurs pour différents contextes
    const addressInputSelectors = [
      '#form-edit-address-adresse', // Back office - edit address modal
      '#service_secours_step2_adresseOccupant', // Service secours
      'input[name="adresse"]', // Générique
    ];

    const postcodeInputSelectors = [
      '#form-edit-address-codepostal', // Back office - edit address modal
      '#service_secours_step2_cpOccupant', // Service secours
      'input[name="codePostal"]', // Générique
    ];

    let addressInput = null;
    let postcodeInput = null;

    // Trouver le champ adresse
    for (const selector of addressInputSelectors) {
      addressInput = document.querySelector(selector);
      if (addressInput && addressInput.value) break;
    }

    // Trouver le champ code postal
    for (const selector of postcodeInputSelectors) {
      postcodeInput = document.querySelector(selector);
      if (postcodeInput && postcodeInput.value) break;
    }

    // Mettre à jour les data-attributes si on a trouvé les champs
    if (addressInput && addressInput.value.trim()) {
      modalPickLocalisation.setAttribute('data-address', addressInput.value.trim());
    }
    if (postcodeInput && postcodeInput.value.trim()) {
      modalPickLocalisation.setAttribute('data-postcode', postcodeInput.value.trim());
    }

    // Détruire la carte existante si elle existe
    if (map) {
      map.remove();
      map = null;
      vectorTileLayer = null;
      previousId = undefined;
    }

    // Réinitialiser la sélection du bâtiment
    const rnbIdField = document.getElementById('fr-modal-pick-localisation-rnb-id');
    const submitButton = document.getElementById('fr-modal-pick-localisation-submit');
    if (rnbIdField) {
      rnbIdField.value = '';
    }
    if (submitButton) {
      submitButton.disabled = true;
    }

    // Nettoyer l'attribut _leaflet_id du conteneur
    const mapContainer = document.getElementById('fr-modal-pick-localisation-map');
    if (mapContainer && mapContainer._leaflet_id) {
      delete mapContainer._leaflet_id;
    }

    // Réinitialiser l'affichage de la carte et du message
    if (mapContainer) {
      mapContainer.classList.remove('fr-hidden');
    }
    if (modalPickLocalisationMessage) {
      modalPickLocalisationMessage.classList.add('fr-hidden');
    }

    // Attendre que le DOM soit complètement rendu avant d'initialiser la carte
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        // Réinitialiser la carte
        map = L.map('fr-modal-pick-localisation-map');
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
          referrerPolicy: 'origin',
        }).addTo(map);

        // Géocoder l'adresse à chaque ouverture
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
              mapContainer.classList.add('fr-hidden');
              return;
            }
            map.setView(
              [json.features[0].geometry.coordinates[1], json.features[0].geometry.coordinates[0]],
              18
            );
            mapContainer.classList.remove('fr-hidden');
            modalPickLocalisationMessage.classList.add('fr-hidden');
            // Forcer la carte à recalculer ses dimensions
            setTimeout(() => {
              map.invalidateSize();
            }, 100);
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

        vectorTileLayer = L.vectorGrid.protobuf(
          'https://rnb-api.beta.gouv.fr/api/alpha/tiles/{x}/{y}/{z}.pbf',
          vectorTileOptions
        );
        vectorTileLayer.addTo(map);

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
      });
    });
  });
}
