import { jsonResponseHandler } from '../../services/component/component_json_response_handler';

const modalSearchAddress = document.getElementById('fr-modal-search-address');
const modalChangeTerritory = document.getElementById('fr-modal-change-territory');

if (modalSearchAddress) {
  const resultsContainer = document.getElementById('fr-modal-search-address-results');
  const referenceField = document.getElementById('fr-modal-search-address-reference');
  const adresseField = document.getElementById('fr-modal-search-address-adresse');
  const tokenField = document.getElementById('fr-modal-search-address-token');
  const linkUrlField = document.getElementById('fr-modal-search-address-link-url');
  const searchParamsField = document.getElementById('fr-modal-search-address-search-params');

  document.addEventListener('click', (event) => {
    const button = event.target.closest('.btn-search-address');
    if (!button) {
      return;
    }

    referenceField.textContent = button.getAttribute('data-signalement-reference');
    tokenField.value = button.getAttribute('data-csrf-token');
    linkUrlField.value = button.getAttribute('data-link-url');
    adresseField.textContent = '';
    resultsContainer.innerHTML = '<p class="fr-text--sm">Recherche en cours…</p>';

    fetch(button.getAttribute('data-search-url'))
      .then((response) => response.json())
      .then((json) => {
        adresseField.textContent = json.query || '';
        renderResults(json.results || []);
      })
      .catch(() => {
        resultsContainer.innerHTML =
          '<p class="fr-text--sm fr-text-default--error">Erreur lors de la recherche d\'adresse.</p>';
      });
  });

  function renderResults(features) {
    if (features.length === 0) {
      resultsContainer.innerHTML =
        '<p class="fr-text--sm">Aucun résultat trouvé pour le territoire de ce signalement.</p>';
      return;
    }

    const list = document.createElement('ul');
    list.classList.add('fr-btns-group', 'fr-mt-2v');
    features.forEach((feature) => {
      const item = document.createElement('li');
      const resultButton = document.createElement('button');
      resultButton.type = 'button';
      resultButton.classList.add('fr-btn', 'fr-btn--secondary', 'fr-btn--sm', 'fr-width-full');
      resultButton.textContent = feature.properties.label + ' ( insee : ' + feature.properties.citycode + ')';
      resultButton.addEventListener('click', () => selectResult(feature));
      item.appendChild(resultButton);
      list.appendChild(item);
    });

    resultsContainer.innerHTML = '';
    resultsContainer.appendChild(list);
  }

  function selectResult(feature) {
    resultsContainer.innerHTML = '<p class="fr-text--sm">Enregistrement…</p>';

    const formData = new FormData();
    formData.append('_token', tokenField.value);
    formData.append('feature', JSON.stringify(feature));
    formData.append('search_params', searchParamsField.value);

    fetch(linkUrlField.value, { method: 'POST', body: formData }).then((response) => {
      if (response.ok) {
        jsonResponseHandler(response);
      }
    });
  }
}
if (modalChangeTerritory) {
  const referenceField = document.getElementById('fr-modal-change-territory-reference');
  const zipAndNameField = document.getElementById('fr-modal-change-territory-zipAndName');
  const tokenField = document.getElementById('fr-modal-change-territory-token');
  const urlField = document.getElementById('fr-modal-change-territory-url');
  const confirmButton = document.getElementById('fr-modal-change-territory-confirm');

  document.addEventListener('click', (event) => {
    const button = event.target.closest('.btn-change-territory');
    if (!button) {
      return;
    }

    referenceField.textContent = button.getAttribute('data-signalement-reference');
    zipAndNameField.textContent = button.getAttribute('data-territory-zipAndName');
    tokenField.value = button.getAttribute('data-csrf-token');
    urlField.value = button.getAttribute('data-change-territory-url');
  });

  confirmButton.addEventListener('click', () => {
    const formData = new FormData();
    formData.append('_token', tokenField.value);

    fetch(urlField.value, { method: 'POST', body: formData }).then((response) => {
      if (response.ok) {
        jsonResponseHandler(response);
      }
    });
  });
}
