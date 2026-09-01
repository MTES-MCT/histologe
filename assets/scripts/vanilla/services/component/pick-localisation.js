import L from 'leaflet';
import 'leaflet.vectorgrid';
import { buildingStyles, createRnbMapController } from './rnb-map-controller.js';

const modalLocalisation = document.getElementById('fr-modal-localisation');
const buttonPanelContainerPickLocalisation = document.getElementById(
  'button-panel-container-pick-localisation'
);
const modalPickLocalisation = document.getElementById('container-pick-localisation');
const modalPickLocalisationMessage = document.getElementById('container-pick-localisation-message');

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

function formatBuildingAddress(building) {
  if (!building || !Array.isArray(building.addresses) || building.addresses.length === 0) {
    return '';
  }
  const addr = building.addresses[0];
  const streetNum = [addr.street_number, addr.street_rep].filter(Boolean).join(' ').trim();
  const streetPart = [streetNum, addr.street].filter(Boolean).join(' ').trim();
  const cityPart = [addr.city_zipcode, addr.city_name].filter(Boolean).join(' ').trim();
  return [streetPart, cityPart].filter(Boolean).join(' ').trim();
}

if (modalPickLocalisation) {
  let map;
  let vectorTileLayer;
  let rnbMapController = null;
  let initRafId = null;

  function destroyMap() {
    if (initRafId) {
      cancelAnimationFrame(initRafId);
      initRafId = null;
    }
    if (rnbMapController) {
      rnbMapController.destroy();
      rnbMapController = null;
    }
    if (map) {
      map.remove();
      map = null;
      vectorTileLayer = null;
    }
    const mapContainer = document.getElementById('container-pick-localisation-map');
    if (mapContainer && mapContainer._leaflet_id) {
      delete mapContainer._leaflet_id;
    }
  }

  if (buttonPanelContainerPickLocalisation) {
    buttonPanelContainerPickLocalisation.addEventListener('click', () => {
      initializePickLocalisationContainer();
    });
  }

  modalPickLocalisation.addEventListener('dsfr.disclose', () => {
    initializePickLocalisationContainer();
  });

  modalPickLocalisation.addEventListener('panel:open', () => {
    initializePickLocalisationContainer();
  });

  modalPickLocalisation.addEventListener('panel:close', () => {
    destroyMap();
  });

  modalPickLocalisation.addEventListener('close', () => {
    destroyMap();
  });

  function initializePickLocalisationContainer() {
    destroyMap();

    let previousId = modalPickLocalisation.dataset.previousRnbId || null;
    delete modalPickLocalisation.dataset.selectedBuilding;

    // Chercher les champs d'adresse dans la page pour mettre à jour les data-attributes
    // Essayer plusieurs sélecteurs pour différents contextes si data-address n'est pas déjà défini
    if (!modalPickLocalisation.dataset.address) {
      const addressInputSelectors = [
        '#form-edit-address-adresse', // Back office - edit address modal
        '#service_secours_step2_addressAddress', // Service secours
        'input[name="adresse"]', // Générique
      ];

      const postcodeInputSelectors = [
        '#form-edit-address-codepostal', // Back office - edit address modal
        '#service_secours_step2_addressPostcode', // Service secours
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
        modalPickLocalisation.dataset.address = addressInput.value.trim();
      }
      if (postcodeInput && postcodeInput.value.trim()) {
        modalPickLocalisation.dataset.postcode = postcodeInput.value.trim();
      }
    }

    // Réinitialiser la sélection du bâtiment
    const rnbIdField = document.getElementById('container-pick-localisation-rnb-id');
    const submitButton = document.getElementById('container-pick-localisation-submit');
    const noAddressAlert = document.getElementById('container-pick-localisation-no-address-alert');
    const addressInfoAlert = document.getElementById('container-pick-localisation-address-info');
    const selectedAddressEl = document.getElementById(
      'container-pick-localisation-selected-address'
    );
    if (noAddressAlert) {
      noAddressAlert.classList.add('fr-hidden');
    }
    if (addressInfoAlert) {
      addressInfoAlert.classList.add('fr-hidden');
    }
    if (selectedAddressEl) {
      selectedAddressEl.textContent = '';
    }
    if (rnbIdField) {
      rnbIdField.value = previousId || '';
    }
    if (submitButton) {
      submitButton.disabled = !previousId;
    }

    // Nettoyer l'annonce du tour précédent
    const mapContainer = document.getElementById('container-pick-localisation-map');
    const announcementEl = document.getElementById('container-pick-localisation-announcement');
    if (announcementEl) announcementEl.textContent = '';

    // Réinitialiser l'affichage de la carte et du message
    if (mapContainer) {
      mapContainer.classList.remove('fr-hidden');
    }
    if (modalPickLocalisationMessage) {
      modalPickLocalisationMessage.classList.add('fr-hidden');
    }

    // Récupérer les conteneurs de boutons
    const defaultButtonsContainer = document.querySelector(
      '#container-pick-localisation .fr-modal__footer .fr-btns-group, #container-pick-localisation .side-panel__footer .fr-btns-group'
    );

    // Réinitialiser l'affichage des boutons (afficher tous les boutons par défaut)
    if (defaultButtonsContainer) {
      const allButtons = defaultButtonsContainer.querySelectorAll('li');
      allButtons.forEach((btn) => btn.classList.remove('fr-hidden'));
    }

    // Attendre que le DOM soit complètement rendu avant d'initialiser la carte
    initRafId = requestAnimationFrame(() => {
      initRafId = requestAnimationFrame(() => {
        initRafId = null;
        if (!mapContainer) return;

        if (map) {
          map.remove();
          map = null;
        }
        if (mapContainer._leaflet_id) {
          delete mapContainer._leaflet_id;
        }

        // keyboard: false désactive la navigation clavier native de Leaflet (flèches/zoom)
        // pour laisser notre propre handler gérer la navigation entre bâtiments
        map = L.map('container-pick-localisation-map', { keyboard: false });
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
          referrerPolicy: 'origin',
        }).addTo(map);

        vectorTileLayer = L.vectorGrid.protobuf(
          'https://rnb-api.beta.gouv.fr/api/alpha/tiles/{x}/{y}/{z}.pbf',
          {
            rendererFactory: L.canvas.tile,
            vectorTileLayerStyles: { default: buildingStyles.initial },
            interactive: true,
            getFeatureId: function (f) {
              return f.properties.rnb_id;
            },
          }
        );
        vectorTileLayer.addTo(map);

        // Géocoder l'adresse à chaque ouverture
        const apiAdresse = 'https://data.geopf.fr/geocodage/search/?q=';
        const address = modalPickLocalisation.dataset.address || '';
        const postCode = modalPickLocalisation.dataset.postcode || '';
        const queryUrl = postCode
          ? `${apiAdresse}${encodeURIComponent(address)}&postcode=${encodeURIComponent(postCode)}`
          : `${apiAdresse}${encodeURIComponent(address)}`;

        fetch(queryUrl)
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

            // Forcer la carte à recalculer ses dimensions, puis initialiser le contrôleur
            setTimeout(() => {
              if (!map) return;
              map.invalidateSize();

              // Restaurer la sélection précédente si l'utilisateur rouvre la modale
              if (previousId) {
                rnbIdField.value = previousId;
                submitButton.disabled = false;
              }

              rnbMapController = createRnbMapController({
                mapContainer,
                map,
                vectorTileLayer,
                previousRnbId: previousId,
                onSelect: (rnbId, building) => {
                  previousId = rnbId;
                  rnbIdField.value = rnbId;

                  const requireAddress = modalPickLocalisation.dataset.requireAddress === 'true';
                  const formattedAddress = formatBuildingAddress(building);
                  const hasAddresses = Boolean(formattedAddress);

                  if (requireAddress && !hasAddresses) {
                    if (noAddressAlert) {
                      noAddressAlert.classList.remove('fr-hidden');
                    }
                    if (addressInfoAlert) {
                      addressInfoAlert.classList.add('fr-hidden');
                    }
                    if (selectedAddressEl) {
                      selectedAddressEl.textContent = '';
                    }
                    submitButton.disabled = true;
                    if (announcementEl) {
                      announcementEl.textContent =
                        "Ce bâtiment n'a aucune adresse associée. Veuillez sélectionner un autre bâtiment.";
                    }
                  } else {
                    if (noAddressAlert) {
                      noAddressAlert.classList.add('fr-hidden');
                    }
                    if (hasAddresses) {
                      if (selectedAddressEl) {
                        selectedAddressEl.textContent = formattedAddress;
                      }
                      if (addressInfoAlert) {
                        addressInfoAlert.classList.remove('fr-hidden');
                      }
                    } else {
                      if (addressInfoAlert) {
                        addressInfoAlert.classList.add('fr-hidden');
                      }
                      if (selectedAddressEl) {
                        selectedAddressEl.textContent = '';
                      }
                    }
                    submitButton.disabled = false;
                  }

                  if (building) {
                    modalPickLocalisation.dataset.selectedBuilding = JSON.stringify(building);
                  }
                },
                onAnnounce: (text) => {
                  if (announcementEl) announcementEl.textContent = text;
                },
                onFocusSubmit: () => {
                  document.getElementById('container-pick-localisation-submit')?.focus();
                },
              });
            }, 100);
          });
      });
    });
  }
}
