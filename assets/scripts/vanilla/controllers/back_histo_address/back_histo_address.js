async function initHistoAddress() {
  const [{ mapStyles }, { default: maplibregl }] = await Promise.all([
    import('carte-facile'),
    import('maplibre-gl'),
    import('maplibre-gl/dist/maplibre-gl.css'),
    import('carte-facile/carte-facile.css'),
  ]);

  const ITEMS_PER_PAGE = 5;
  const searchForm = document.getElementById('search-histo-address-form');
  const toggleMap = document.getElementById('toggle-map');
  const listContainer = document.querySelector('.container-histo-address-list');
  const mapContainer = document.querySelector('.container-histo-address-map');

  const markersByTarget = new Map();
  const SOURCE_ID = 'same-address';
  let map = null;
  let mapLoaded = false;
  let currentPopup = null;

  function buildGeoJson() {
    markersByTarget.clear();

    const allItems = Array.from(document.querySelectorAll('.same-address-item'));
    const features = [];

    document.querySelectorAll('.same-address-item.is-active-filters').forEach(function (item) {
      const lat = Number.parseFloat(item.dataset.lat);
      const lng = Number.parseFloat(item.dataset.lng);

      if (Number.isNaN(lat) || Number.isNaN(lng)) {
        return;
      }

      const index = allItems.indexOf(item);
      const targetId = '#same-address-details-' + (index + 1);
      const detailsId = 'same-address-details-' + (index + 1);

      features.push({
        type: 'Feature',
        geometry: { type: 'Point', coordinates: [lng, lat] },
        properties: { detailsId: detailsId },
      });
      markersByTarget.set(targetId, { lngLat: [lng, lat], detailsId: detailsId });
    });

    return { type: 'FeatureCollection', features: features };
  }

  function openPopup(lngLat, detailsId) {
    if (currentPopup) {
      currentPopup.remove();
    }
    const detailsEl = document.getElementById(detailsId);
    currentPopup = new maplibregl.Popup({ offset: 12 })
      .setLngLat(lngLat)
      .setHTML(detailsEl ? detailsEl.innerHTML : '')
      .addTo(map);
  }

  function fitMapToMarkers() {
    if (!map || markersByTarget.size === 0) {
      return;
    }
    const bounds = new maplibregl.LngLatBounds();
    markersByTarget.forEach(function (info) {
      bounds.extend(info.lngLat);
    });
    map.fitBounds(bounds, { padding: 20, maxZoom: 16, duration: 1000 });
  }

  function initMap() {
    map = new maplibregl.Map({
      container: 'map-histo-address',
      style: mapStyles.simple,
      center: [2, 47],
      zoom: 6,
    });
    map.addControl(new maplibregl.NavigationControl({ showCompass: false }));

    map.on('load', function () {
      map.addSource(SOURCE_ID, {
        type: 'geojson',
        data: buildGeoJson(),
        cluster: true,
        clusterMaxZoom: 17,
        clusterRadius: 50,
      });

      // Cluster : même visuel que .marker-cluster-custom (Leaflet)
      map.addLayer({
        id: 'clusters',
        type: 'circle',
        source: SOURCE_ID,
        filter: ['has', 'point_count'],
        paint: {
          'circle-radius': 14,
          'circle-color': '#a9bfff',
          'circle-stroke-width': 2,
          'circle-stroke-color': '#0063cb',
        },
      });
      map.addLayer({
        id: 'cluster-count',
        type: 'symbol',
        source: SOURCE_ID,
        filter: ['has', 'point_count'],
        layout: {
          'text-field': '{point_count_abbreviated}',
          'text-font': ['Noto Sans Bold'],
          'text-size': 11,
        },
        paint: {
          'text-color': '#0063cb',
        },
      });

      // Point isolé : même visuel que le circleMarker Leaflet
      map.addLayer({
        id: 'unclustered-point',
        type: 'circle',
        source: SOURCE_ID,
        filter: ['!', ['has', 'point_count']],
        paint: {
          'circle-radius': 10,
          'circle-color': '#FFF',
          'circle-opacity': 0.8,
          'circle-stroke-width': 2,
          'circle-stroke-color': '#000091',
        },
      });

      map.on('click', 'clusters', function (e) {
        const features = map.queryRenderedFeatures(e.point, { layers: ['clusters'] });
        const clusterId = features[0].properties.cluster_id;
        map
          .getSource(SOURCE_ID)
          .getClusterExpansionZoom(clusterId)
          .then(function (zoom) {
            map.easeTo({ center: features[0].geometry.coordinates, zoom: zoom + 0.5 });
          });
      });

      map.on('click', 'unclustered-point', function (e) {
        const feature = e.features[0];
        openPopup(feature.geometry.coordinates.slice(), feature.properties.detailsId);
      });

      ['clusters', 'unclustered-point'].forEach(function (layerId) {
        map.on('mouseenter', layerId, function () {
          map.getCanvas().style.cursor = 'pointer';
        });
        map.on('mouseleave', layerId, function () {
          map.getCanvas().style.cursor = '';
        });
      });

      mapLoaded = true;
      fitMapToMarkers();
    });
  }

  function initMarkers() {
    const geojson = buildGeoJson();

    if (currentPopup) {
      currentPopup.remove();
      currentPopup = null;
    }

    if (map && mapLoaded) {
      map.getSource(SOURCE_ID).setData(geojson);
      fitMapToMarkers();
    }
  }

  function initCounters() {
    let nbAddresses = 0;
    const nbDossiers = document.querySelectorAll('.same-address-item.is-active-filters').length;
    document.querySelectorAll('.same-address-item.is-active-filters').forEach(function (item) {
      nbAddresses += Number.parseInt(item.dataset.nb);
    });
    const counterEl = document.querySelector('.title-counters-same-address');
    counterEl.textContent =
      nbAddresses > 0
        ? nbAddresses +
          ' dossiers trouvés sur ' +
          nbDossiers +
          ' adresse' +
          (nbDossiers > 1 ? 's' : '')
        : 'Aucun dossier trouvé';
  }

  function normalizeStr(str) {
    //Attention toute modification de cette fonction doit être répercutée dans le SignalementSameAddressController.php (voir fonction "normalizeStr")
    return (str || '')
      .normalize('NFD')
      .replaceAll(/[\u0300-\u036f]/gu, '')
      .replaceAll('-', ' ')
      .toLowerCase();
  }

  function getSuggestions(field, query) {
    const seen = new Set();
    const results = [];
    const normalizedQuery = normalizeStr(query);

    const territoryId = searchForm.querySelector('[name="territoryId"]')?.value;
    const otherFilters = {
      address: searchForm.querySelector('[name="address"]').value,
      commune: searchForm.querySelector('[name="commune"]').value,
      bailleur: searchForm.querySelector('[name="bailleur"]').value,
    };

    const items = document.querySelectorAll('.same-address-item');
    for (const item of items) {
      if (territoryId && item.dataset.territoryId !== territoryId) continue;
      let excluded = false;
      for (const [filterField, filterValue] of Object.entries(otherFilters)) {
        if (filterField === field) continue; // ne pas appliquer le filtre du champ courant
        if (filterValue) {
          if (filterField === 'bailleur') {
            const hasBailleur = Array.from(item.querySelectorAll('[data-bailleur]')).some(
              (el) => normalizeStr(el.dataset.bailleur) === normalizeStr(filterValue)
            );
            if (!hasBailleur) {
              excluded = true;
              break;
            }
          } else if (normalizeStr(item.dataset[filterField]) !== normalizeStr(filterValue)) {
            excluded = true;
            break;
          }
        }
      }
      if (excluded) continue;

      if (field === 'bailleur') {
        item.querySelectorAll('[data-bailleur]').forEach(function (el) {
          const value = el.dataset.bailleur;
          if (value && normalizeStr(value).includes(normalizedQuery) && !seen.has(value)) {
            seen.add(value);
            results.push(value);
          }
        });
      } else {
        const value = item.dataset[field];
        if (value && normalizeStr(value).includes(normalizedQuery) && !seen.has(value)) {
          seen.add(value);
          results.push(value);
        }
      }
    }
    results.sort(function (a, b) {
      return normalizeStr(a).localeCompare(normalizeStr(b));
    });
    return results.slice(0, 10);
  }

  function showSuggestions(field, query) {
    const listEl = searchForm.querySelector('[name="' + field + '"] + .fr-autocomplete-list');
    listEl.innerHTML = '';
    if (!query) return;
    getSuggestions(field, query).forEach(function (suggestion) {
      const suggestionEl = document.createElement('div');
      suggestionEl.classList.add(
        'fr-col-12',
        'fr-p-3v',
        'fr-text-label--blue-france',
        'fr-autocomplete-suggestion'
      );
      suggestionEl.textContent = suggestion;
      suggestionEl.addEventListener('click', function () {
        searchForm.querySelector('[name="' + field + '"]').value = suggestion;
        listEl.innerHTML = '';
        applyFilters();
      });
      listEl.appendChild(suggestionEl);
    });
  }

  function applyFilters() {
    const territoryId = searchForm.querySelector('[name="territoryId"]')?.value;
    const addressFilter = searchForm.querySelector('[name="address"]').value;
    const communeFilter = searchForm.querySelector('[name="commune"]').value;
    const bailleurFilter = searchForm.querySelector('[name="bailleur"]').value;
    document.querySelectorAll('.same-address-item').forEach(function (item) {
      item.classList.add('is-active-filters');
      if (territoryId && item.dataset.territoryId !== territoryId) {
        item.classList.remove('is-active-filters');
      }
      if (addressFilter && normalizeStr(item.dataset.address) !== normalizeStr(addressFilter)) {
        item.classList.remove('is-active-filters');
      }
      if (communeFilter && normalizeStr(item.dataset.commune) !== normalizeStr(communeFilter)) {
        item.classList.remove('is-active-filters');
      }
      if (bailleurFilter) {
        const hasBailleur = Array.from(item.querySelectorAll('[data-bailleur]')).some(
          (el) => normalizeStr(el.dataset.bailleur) === normalizeStr(bailleurFilter)
        );
        if (!hasBailleur) {
          item.classList.remove('is-active-filters');
        }
      }
    });
    initCounters();
    map ? initMarkers() : initMap();
    showPage(1);
  }

  function initFiltersFromQueryParams() {
    const params = new URLSearchParams(window.location.search);

    ['territoryId', 'address', 'commune', 'bailleur'].forEach(function (field) {
      const inputEl = searchForm.querySelector('[name="' + field + '"]');
      if (!inputEl) {
        return;
      }

      const value = params.get(field);
      if (value !== null) {
        inputEl.value = value;
      }
    });
  }

  function showPage(page) {
    document.querySelectorAll('.same-address-item').forEach(function (item) {
      if (!item.classList.contains('is-active-filters') && !item.classList.contains('fr-hidden')) {
        item.classList.add('fr-hidden');
      }
    });
    const allItems = Array.from(document.querySelectorAll('.same-address-item.is-active-filters'));
    const totalPages = Math.ceil(allItems.length / ITEMS_PER_PAGE);
    const currentPage = page;
    allItems.forEach(function (item, i) {
      const onCurrentPage = i >= (page - 1) * ITEMS_PER_PAGE && i < page * ITEMS_PER_PAGE;
      item.classList.toggle('fr-hidden', !onCurrentPage);
    });
    renderPagination(totalPages, currentPage);
  }

  function pageLink(page, label, extraClass) {
    return (
      '<li><a class="fr-pagination__link' +
      (extraClass ? ' ' + extraClass : '') +
      '" href="#" data-page="' +
      page +
      '">' +
      label +
      '</a></li>'
    );
  }

  function disabledLink(label, extraClass) {
    return (
      '<li><a class="fr-pagination__link' +
      (extraClass ? ' ' + extraClass : '') +
      '" aria-disabled="true">' +
      label +
      '</a></li>'
    );
  }

  function renderPagination(totalPages, currentPage) {
    const nav = document.querySelector('.pagination-full-js');

    if (totalPages <= 1) {
      nav.innerHTML = '';
      return;
    }

    let html = '<ul class="fr-pagination__list">';

    html +=
      currentPage > 1
        ? pageLink(1, 'Première page', 'fr-pagination__link--first')
        : disabledLink('Première page', 'fr-pagination__link--first');

    html +=
      currentPage > 1
        ? pageLink(currentPage - 1, '', 'fr-pagination__link--prev fr-pagination__link--lg-label')
        : disabledLink('', 'fr-pagination__link--prev fr-pagination__link--lg-label');

    html += '<li style="flex: 1"></li>';

    if (currentPage > 2) {
      html += pageLink(1, '1');
    }
    if (currentPage > 3) {
      html += '<li><a class="fr-pagination__link" title="placeholder">...</a></li>';
    }

    const start = Math.max(1, currentPage - 1);
    const end = Math.min(totalPages, currentPage + 1);
    for (let i = start; i <= end; i++) {
      html +=
        '<li><a class="fr-pagination__link" href="#" data-page="' +
        i +
        '" title="Page ' +
        i +
        '"' +
        (currentPage === i ? ' aria-current="page"' : '') +
        '>' +
        i +
        '</a></li>';
    }

    if (currentPage < totalPages - 2) {
      html += '<li><a class="fr-pagination__link" title="placeholder">...</a></li>';
    }
    if (currentPage < totalPages - 1) {
      html += pageLink(totalPages, String(totalPages));
    }

    html += '<li style="flex: 1"></li>';

    html +=
      currentPage < totalPages
        ? pageLink(currentPage + 1, '', 'fr-pagination__link--next fr-pagination__link--lg-label')
        : disabledLink('', 'fr-pagination__link--next fr-pagination__link--lg-label');

    html +=
      currentPage < totalPages
        ? pageLink(totalPages, 'Dernière page', 'fr-pagination__link--last')
        : disabledLink('Dernière page', 'fr-pagination__link--last');

    html += '</ul>';
    nav.innerHTML = html;

    nav.querySelectorAll('[data-page]').forEach(function (link) {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        showPage(Number.parseInt(link.dataset.page));
      });
    });
  }

  function applyToggleMap() {
    if (toggleMap.checked) {
      listContainer.classList.add('fr-hidden');
      mapContainer.classList.remove('fr-hidden');
      searchForm.classList.remove('fr-container-sml');
      searchForm.classList.add('on-map');
      if (map) {
        map.resize();
        fitMapToMarkers();
      } else {
        initMap();
      }
    } else {
      listContainer.classList.remove('fr-hidden');
      mapContainer.classList.add('fr-hidden');
      searchForm.classList.remove('on-map');
      searchForm.classList.add('fr-container-sml');
    }
  }

  searchForm.addEventListener('change', function (e) {
    if (e.target.name === 'territoryId') {
      applyFilters();
    }
  });

  ['address', 'commune', 'bailleur'].forEach(function (field) {
    const inputEl = searchForm.querySelector('[name="' + field + '"]');
    let selectedSuggestionIndex = -1;

    function getListEl() {
      return searchForm.querySelector('[name="' + field + '"] + .fr-autocomplete-list');
    }

    function updateSelectedSuggestion() {
      const listEl = getListEl();
      listEl
        .querySelector('.fr-autocomplete-suggestion-highlighted')
        ?.classList.remove('fr-autocomplete-suggestion-highlighted');
      const suggestions = listEl.querySelectorAll('.fr-autocomplete-suggestion');
      suggestions[selectedSuggestionIndex]?.classList.add('fr-autocomplete-suggestion-highlighted');
    }

    function handleDown() {
      const suggestions = getListEl().querySelectorAll('.fr-autocomplete-suggestion');
      if (selectedSuggestionIndex < suggestions.length - 1) {
        selectedSuggestionIndex++;
        updateSelectedSuggestion();
      }
    }

    function handleUp() {
      if (selectedSuggestionIndex > 0) {
        selectedSuggestionIndex--;
        updateSelectedSuggestion();
      }
    }

    function handleEnter() {
      const suggestions = getListEl().querySelectorAll('.fr-autocomplete-suggestion');
      if (selectedSuggestionIndex !== -1 && suggestions[selectedSuggestionIndex]) {
        inputEl.value = suggestions[selectedSuggestionIndex].textContent;
        getListEl().innerHTML = '';
        selectedSuggestionIndex = -1;
        applyFilters();
      }
    }

    inputEl.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        handleEnter();
      } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        handleDown();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        handleUp();
      }
    });
    inputEl.addEventListener('focus', function () {
      selectedSuggestionIndex = -1;
      showSuggestions(field, inputEl.value);
    });
    inputEl.addEventListener('input', function () {
      selectedSuggestionIndex = -1;
      showSuggestions(field, inputEl.value);
      if (!inputEl.value) {
        applyFilters();
      }
    });
  });

  document.addEventListener('click', function (e) {
    if (!e.target.closest('.fr-input-wrap')) {
      searchForm.querySelectorAll('.fr-autocomplete-list').forEach(function (listEl) {
        listEl.innerHTML = '';
      });
    }
  });

  initFiltersFromQueryParams();
  applyFilters();
  toggleMap.addEventListener('change', applyToggleMap);
  applyToggleMap();
}

if (document.getElementById('map-histo-address')) {
  initHistoAddress();
}
