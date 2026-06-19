import L from 'leaflet';
import 'leaflet.vectorgrid';
import { buildingStyles, createRnbMapController } from './rnb-map-controller.js';

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
  let rnbMapController = null;

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

    // Détruire la carte et le contrôleur existants
    if (map) {
      map.remove();
      map = null;
      vectorTileLayer = null;
    }
    if (rnbMapController) {
      rnbMapController.destroy();
      rnbMapController = null;
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

    // Nettoyer l'annonce du tour précédent
    const mapContainer = document.getElementById('fr-modal-pick-localisation-map');
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
        // minZoom: 18 évite de dézoomer au point d'avoir trop de bâtiments dans la vue (car api RNB limite à 100 bâtiments par requête)
        map = L.map('fr-modal-pick-localisation-map', { keyboard: false, minZoom: 18 });
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

            // Forcer la carte à recalculer ses dimensions, puis initialiser le contrôleur
            setTimeout(() => {
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
                onSelect: (rnbId) => {
                  previousId = rnbId;
                  rnbIdField.value = rnbId;
                  submitButton.disabled = false;
                },
                onAnnounce: (text) => {
                  if (announcementEl) announcementEl.textContent = text;
                },
                onFocusSubmit: () => {
                  document.getElementById('fr-modal-pick-localisation-submit')?.focus();
                },
              });
            }, 100);
          });
      });
    });
  });
}
