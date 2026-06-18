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
  let mapKeyboardHandler = null;

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

    // Nettoyer le handler clavier et l'annonce du tour précédent
    const mapContainer = document.getElementById('fr-modal-pick-localisation-map');
    if (mapKeyboardHandler && mapContainer) {
      mapContainer.removeEventListener('keydown', mapKeyboardHandler);
      mapKeyboardHandler = null;
    }
    const announcementEl = document.getElementById('fr-modal-pick-localisation-announcement');
    if (announcementEl) announcementEl.textContent = '';

    // Nettoyer l'attribut _leaflet_id du conteneur
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

    // Récupérer les conteneurs de boutons
    const defaultButtonsContainer = document.querySelector(
      '#fr-modal-pick-localisation .fr-modal__footer .fr-btns-group'
    );

    // Réinitialiser l'affichage des boutons (afficher tous les boutons par défaut)
    if (defaultButtonsContainer) {
      const allButtons = defaultButtonsContainer.querySelectorAll('li');
      allButtons.forEach((btn) => btn.classList.remove('fr-hidden'));
    }

    // Attendre que le DOM soit complètement rendu avant d'initialiser la carte
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        // keyboard: false désactive la navigation clavier native de Leaflet (flèches/zoom)
        // pour laisser notre propre handler gérer la navigation entre bâtiments
        map = L.map('fr-modal-pick-localisation-map', { keyboard: false });
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

              // Masquer les boutons Valider et Annuler du footer
              if (defaultButtonsContainer) {
                const allButtons = defaultButtonsContainer.querySelectorAll('li');
                allButtons.forEach((btn) => btn.classList.add('fr-hidden'));
              }

              return;
            }
            const lat = json.features[0].geometry.coordinates[1];
            const lng = json.features[0].geometry.coordinates[0];
            map.setView([lat, lng], 18);
            mapContainer.classList.remove('fr-hidden');
            modalPickLocalisationMessage.classList.add('fr-hidden');
            // Forcer la carte à recalculer ses dimensions, puis fetcher les bâtiments visibles
            setTimeout(() => {
              map.invalidateSize();
              fetchBuildings();
              setupKeyboardNavigation();
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

        var focusedStyle = {
          radius: 7,
          fillColor: '#f95c00',
          color: '#ffffff',
          weight: 3,
          fill: true,
          fillOpacity: 1,
          opacity: 1,
        };

        // État partagé entre navigation clavier et rafraîchissement des bâtiments
        let currentBuildings = [];
        let buildingMarkers = [];
        let focusedIndex = -1;
        let keyboardPanning = false;

        vectorTileLayer = L.vectorGrid.protobuf(
          'https://rnb-api.beta.gouv.fr/api/alpha/tiles/{x}/{y}/{z}.pbf',
          {
            rendererFactory: L.canvas.tile,
            vectorTileLayerStyles: { default: initialStyle },
            interactive: true,
            getFeatureId: function (f) {
              return f.properties.rnb_id;
            },
          }
        );
        vectorTileLayer.addTo(map);

        // Restaurer la sélection précédente si l'utilisateur rouvre la modale
        if (previousId) {
          rnbIdField.value = previousId;
          submitButton.disabled = false;
          vectorTileLayer.setFeatureStyle(previousId, clickedStyle);
        }

        vectorTileLayer.on('click', async function (e) {
          var properties = e.layer.properties;
          var rnb_id = properties.rnb_id;
          if (previousId !== undefined) {
            vectorTileLayer.setFeatureStyle(previousId, initialStyle);
            const oldIdx = currentBuildings.findIndex((b) => b.rnb_id === previousId);
            if (oldIdx >= 0) updateMarker(oldIdx, 'default');
          }
          vectorTileLayer.setFeatureStyle(rnb_id, clickedStyle);
          previousId = rnb_id;
          document.getElementById('fr-modal-pick-localisation-rnb-id').value = rnb_id;
          document.getElementById('fr-modal-pick-localisation-submit').disabled = false;
          const selIdx = currentBuildings.findIndex((b) => b.rnb_id === rnb_id);
          if (selIdx >= 0) updateMarker(selIdx, 'selected');
        });

        // Re-fetch des bâtiments à chaque fin de déplacement initié par l'utilisateur.
        // keyboardPanning est positionné à true avant tout panTo ou touche fléchée
        // pour éviter que moveend ne réinitialise focusedIndex pendant la navigation clavier.
        map.on('moveend', () => {
          if (keyboardPanning) {
            keyboardPanning = false;
            return;
          }
          fetchBuildings();
        });

        function markerHtml(index, state) {
          const bg = state === 'focused' ? '#f95c00' : state === 'selected' ? '#31e060' : '#1452e3';
          return `<div style="background:${bg};color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:bold;border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,.4)">${index + 1}</div>`;
        }

        function updateMarker(index, state) {
          const el = buildingMarkers[index]?.getElement();
          if (el) el.innerHTML = markerHtml(index, state);
        }

        function refreshBuildings(buildings) {
          // Réinitialiser le style du bâtiment en cours de focus avant de tout effacer
          if (focusedIndex >= 0 && focusedIndex < currentBuildings.length) {
            const prev = currentBuildings[focusedIndex];
            vectorTileLayer.setFeatureStyle(
              prev.rnb_id,
              prev.rnb_id === previousId ? clickedStyle : initialStyle
            );
          }
          buildingMarkers.forEach((m) => map.removeLayer(m));
          buildingMarkers = [];
          focusedIndex = -1;
          currentBuildings = buildings;

          buildings.forEach((building, index) => {
            const [bLng, bLat] = building.point.coordinates;
            const state = building.rnb_id === previousId ? 'selected' : 'default';
            const marker = L.marker([bLat, bLng], {
              icon: L.divIcon({
                html: markerHtml(index, state),
                className: '',
                iconSize: [24, 24],
                iconAnchor: [12, 12],
              }),
              interactive: false,
              zIndexOffset: 1000,
            });
            marker.addTo(map);
            buildingMarkers.push(marker);
          });

          if (mapContainer && buildings.length > 0) {
            mapContainer.setAttribute(
              'aria-label',
              `Carte de sélection du bâtiment. ${buildings.length} bâtiment(s) numéroté(s) sur la carte. Touches fléchées ou Tab pour naviguer, Entrée pour sélectionner.`
            );
          }
        }

        function fetchBuildings() {
          const bounds = map.getBounds();
          const center = map.getCenter();
          const bbox = `${bounds.getWest()},${bounds.getSouth()},${bounds.getEast()},${bounds.getNorth()}`;
          fetch(`https://rnb-api.beta.gouv.fr/api/alpha/buildings/?bbox=${bbox}&limit=100`)
            .then((res) => res.json())
            .then((data) => {
              const sorted = (data.results || [])
                .filter((b) => b.point && b.point.coordinates)
                .sort((a, b) => {
                  const da = Math.hypot(
                    a.point.coordinates[1] - center.lat,
                    a.point.coordinates[0] - center.lng
                  );
                  const db = Math.hypot(
                    b.point.coordinates[1] - center.lat,
                    b.point.coordinates[0] - center.lng
                  );
                  return da - db;
                });
              refreshBuildings(sorted);
            })
            .catch(() => {});
        }

        function setupKeyboardNavigation() {
          const submitBtn = document.getElementById('fr-modal-pick-localisation-submit');
          const rnbInput = document.getElementById('fr-modal-pick-localisation-rnb-id');
          const announcement = document.getElementById('fr-modal-pick-localisation-announcement');

          function focusBuilding(index) {
            if (focusedIndex >= 0 && focusedIndex < currentBuildings.length) {
              const prev = currentBuildings[focusedIndex];
              const prevState = prev.rnb_id === previousId ? 'selected' : 'default';
              vectorTileLayer.setFeatureStyle(
                prev.rnb_id,
                prevState === 'selected' ? clickedStyle : initialStyle
              );
              updateMarker(focusedIndex, prevState);
            }
            focusedIndex = index;
            const building = currentBuildings[index];
            vectorTileLayer.setFeatureStyle(building.rnb_id, focusedStyle);
            updateMarker(index, 'focused');
            const [bLng, bLat] = building.point.coordinates;
            if (!map.getBounds().contains([bLat, bLng])) {
              keyboardPanning = true;
              map.panTo([bLat, bLng]);
            }
            if (announcement) {
              announcement.textContent = `Bâtiment ${index + 1} sur ${currentBuildings.length}, identifiant ${building.rnb_id}`;
            }
          }

          function selectBuilding() {
            if (focusedIndex < 0) return;
            const building = currentBuildings[focusedIndex];
            const rnbId = building.rnb_id;
            if (previousId !== undefined) {
              vectorTileLayer.setFeatureStyle(previousId, initialStyle);
              const oldIdx = currentBuildings.findIndex((b) => b.rnb_id === previousId);
              if (oldIdx >= 0) updateMarker(oldIdx, 'default');
            }
            vectorTileLayer.setFeatureStyle(rnbId, clickedStyle);
            updateMarker(focusedIndex, 'selected');
            previousId = rnbId;
            rnbInput.value = rnbId;
            submitBtn.disabled = false;
            if (announcement) {
              announcement.textContent = `Bâtiment ${focusedIndex + 1} sélectionné, identifiant ${rnbId}`;
            }
          }

          mapKeyboardHandler = (e) => {
            if (!currentBuildings.length) return;

            // Tab / Shift+Tab : navigation séquentielle avec sortie naturelle aux limites
            if (e.key === 'Tab') {
              if (!e.shiftKey) {
                if (focusedIndex < currentBuildings.length - 1) {
                  e.preventDefault();
                  keyboardPanning = true;
                  focusBuilding(focusedIndex < 0 ? 0 : focusedIndex + 1);
                }
                // dernier bâtiment : Tab propagé vers le prochain focusable (Valider / Annuler)
              } else {
                if (focusedIndex > 0) {
                  e.preventDefault();
                  keyboardPanning = true;
                  focusBuilding(focusedIndex - 1);
                }
                // premier bâtiment : Shift+Tab propagé vers l'élément précédent
              }
              return;
            }

            if (!['ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp', 'Enter', ' '].includes(e.key)) return;
            e.preventDefault();
            switch (e.key) {
              case 'ArrowRight':
              case 'ArrowDown':
                keyboardPanning = true;
                focusBuilding(focusedIndex < 0 ? 0 : (focusedIndex + 1) % currentBuildings.length);
                break;
              case 'ArrowLeft':
              case 'ArrowUp':
                keyboardPanning = true;
                focusBuilding(
                  focusedIndex < 0
                    ? currentBuildings.length - 1
                    : (focusedIndex - 1 + currentBuildings.length) % currentBuildings.length
                );
                break;
              case 'Enter':
              case ' ':
                selectBuilding();
                submitBtn.focus();
                break;
            }
          };

          mapContainer.addEventListener('keydown', mapKeyboardHandler);
        }
      });
    });
  });
}
