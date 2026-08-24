import { jsonResponseHandler } from '../../services/component/component_json_response_handler';

const panelSearchAddress = document.getElementById('fr-panel-search-address');
const panelChangeTerritory = document.getElementById('fr-panel-change-territory');
const panelBulkLinkAddress = document.getElementById('fr-panel-bulk-link-address');

if (panelSearchAddress) {
  const resultsContainer = document.getElementById('fr-panel-search-address-results');
  const referenceField = document.getElementById('fr-panel-search-address-reference');
  const adresseField = document.getElementById('fr-panel-search-address-adresse');
  const tokenField = document.getElementById('fr-panel-search-address-token');
  const linkUrlField = document.getElementById('fr-panel-search-address-link-url');
  const searchParamsField = document.getElementById('fr-panel-search-address-search-params');

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
if (panelChangeTerritory) {
  const referenceField = document.getElementById('fr-panel-change-territory-reference');
  const actualZipAndNameField = document.getElementById('fr-panel-change-territory-actual-zipAndName');
  const zipAndNameField = document.getElementById('fr-panel-change-territory-zipAndName');
  const adresseField = document.getElementById('fr-panel-change-territory-adresse');
  const seeSignalementButton = document.getElementById('fr-panel-change-territory-see-signalement');
  const nbAffectationsField = document.getElementById('fr-panel-change-territory-nb-affectations');
  const nbSuivisField = document.getElementById('fr-panel-change-territory-nb-suivis');
  const tokenField = document.getElementById('fr-panel-change-territory-token');
  const urlField = document.getElementById('fr-panel-change-territory-url');
  const searchParamsField = document.getElementById('fr-panel-change-territory-search-params');
  const confirmButton = document.getElementById('fr-panel-change-territory-confirm');

  document.addEventListener('click', (event) => {
    const button = event.target.closest('.btn-change-territory');
    if (!button) {
      return;
    }

    referenceField.textContent = button.getAttribute('data-signalement-reference');
    actualZipAndNameField.textContent = button.getAttribute('data-territory-actual-zipAndName');
    zipAndNameField.textContent = button.getAttribute('data-territory-zipAndName');
    nbAffectationsField.textContent = button.getAttribute('data-affectations-length');
    nbSuivisField.textContent = button.getAttribute('data-suivis-length');
    adresseField.textContent = button.getAttribute('data-adresse');
    seeSignalementButton.href = button.getAttribute('data-signalement-link-url');
    tokenField.value = button.getAttribute('data-csrf-token');
    urlField.value = button.getAttribute('data-change-territory-url');
  });

  confirmButton.addEventListener('click', () => {
    const formData = new FormData();
    formData.append('_token', tokenField.value);
    formData.append('search_params', searchParamsField.value);

    fetch(urlField.value, { method: 'POST', body: formData }).then((response) => {
      if (response.ok) {
        jsonResponseHandler(response);
      }
    });
  });
}
if (panelBulkLinkAddress) {
  const resultsContainer = document.getElementById('fr-panel-bulk-link-address-results');
  const tokenField = document.getElementById('fr-panel-bulk-link-address-token');
  const linkUrlField = document.getElementById('fr-panel-bulk-link-address-link-url');
  const searchParamsField = document.getElementById('fr-panel-bulk-link-address-search-params');
  const confirmButton = document.getElementById('fr-panel-bulk-link-address-confirm');

  document.addEventListener('click', (event) => {
    const button = event.target.closest('.btn-bulk-link-address');
    if (!button) {
      return;
    }

    tokenField.value = button.getAttribute('data-csrf-token');
    linkUrlField.value = button.getAttribute('data-link-url');
    confirmButton.disabled = true;
    confirmButton.textContent = 'Oui, lier ces signalements aux adresses envoyées par la BAN';
    resultsContainer.innerHTML = '<p class="fr-text--sm">Recherche en cours…</p>';

    fetch(button.getAttribute('data-preview-url'))
      .then((response) => response.json())
      .then((json) => {
        resultsContainer.innerHTML = json.html;
        confirmButton.disabled = json.count === 0;
        confirmButton.textContent =
          'Oui, lier ces '
          + json.count
          + ' signalement'
          + (json.count > 1 ? 's' : '')
          + ' aux adresses envoyées par la BAN';
      })
      .catch(() => {
        resultsContainer.innerHTML =
          '<p class="fr-text--sm fr-text-default--error">Erreur lors de la recherche des adresses.</p>';
      });
  });

  confirmButton.addEventListener('click', () => {
    const candidates = Array.from(document.querySelectorAll('.bulk-link-candidate')).map((row) => ({
      uuid: row.getAttribute('data-uuid'),
      feature: JSON.parse(row.getAttribute('data-feature')),
    }));

    if (candidates.length === 0) {
      return;
    }

    resultsContainer.innerHTML = '<p class="fr-text--sm">Enregistrement…</p>';

    const formData = new FormData();
    formData.append('_token', tokenField.value);
    formData.append('candidates', JSON.stringify(candidates));
    formData.append('search_params', searchParamsField.value);

    fetch(linkUrlField.value, { method: 'POST', body: formData }).then((response) => {
      if (response.ok) {
        jsonResponseHandler(response);
      }
    });
  });
}
