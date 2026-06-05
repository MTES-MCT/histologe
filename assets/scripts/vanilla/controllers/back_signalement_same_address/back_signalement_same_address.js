import 'leaflet/dist/leaflet.css';

if (document.getElementById('map-same-address')) {
  const ITEMS_PER_PAGE = 5;
  const searchForm = document.getElementById('search-same-address-form');
  const toggleMap = document.getElementById('toggle-map');
  const listContainer = document.querySelector('.container-same-address-list');
  const mapContainer = document.querySelector('.container-same-address-map');

  const markersByTarget = new Map();
  const renderer = L.canvas({ padding: 0.5 });
  const markersCluster = new L.MarkerClusterGroup({
    iconCreateFunction: function (cluster) {
      return L.divIcon({
        html:
          '<div class="marker-cluster-custom"><span>' + cluster.getChildCount() + '</span></div>',
        className: '',
        iconSize: L.point(28, 28),
      });
    },
  });
  let map = null;

  function initMap() {
    map = L.map('map-same-address').setView([47, 2], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      referrerPolicy: 'origin',
    }).addTo(map);

    initMarkers();
  }

  function initMarkers() {
    markersByTarget.clear();
    markersCluster.clearLayers();

    const allItems = Array.from(document.querySelectorAll('.same-address-item'));

    document.querySelectorAll('.same-address-item.is-active-filters').forEach(function (item) {
      const lat = Number.parseFloat(item.dataset.lat);
      const lng = Number.parseFloat(item.dataset.lng);

      if (Number.isNaN(lat) || Number.isNaN(lng)) {
        return;
      }

      const index = allItems.indexOf(item);
      const targetId = '#same-address-details-' + (index + 1);

      const marker = L.circleMarker([lat, lng], {
        renderer: renderer,
        radius: 10,
        fillColor: '#FFF',
        color: '#000091',
        weight: 2,
        opacity: 1,
        fillOpacity: 0.8,
      }).bindPopup(function () {
        const detailsEl = document.getElementById('same-address-details-' + (index + 1));
        return detailsEl ? detailsEl.innerHTML : '';
      });

      markersCluster.addLayer(marker);
      markersByTarget.set(targetId, marker);
    });

    markersCluster.addTo(map);

    if (markersByTarget.size > 0) {
      map.fitBounds(markersCluster.getBounds());
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
    const items = document.querySelectorAll('.same-address-item.is-active-filters');
    for (const item of items) {
      const value = item.dataset[field];
      if (value && normalizeStr(value).includes(normalizedQuery) && !seen.has(value)) {
        seen.add(value);
        results.push(value);
        if (results.length === 10) break;
      }
    }
    return results;
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
      if (bailleurFilter && normalizeStr(item.dataset.bailleur) !== normalizeStr(bailleurFilter)) {
        item.classList.remove('is-active-filters');
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
      listContainer.classList.remove('fr-col-12');
      listContainer.classList.add('fr-col-sm-5');
      mapContainer.classList.remove('fr-hidden');
      if (map) {
        map.invalidateSize();
        if (markersCluster.getLayers().length > 0) {
          map.fitBounds(markersCluster.getBounds().pad(0.01));
        }
      } else {
        initMap();
      }
    } else {
      listContainer.classList.remove('fr-col-sm-5');
      listContainer.classList.add('fr-col-12');
      mapContainer.classList.add('fr-hidden');
    }
  }

  document.querySelectorAll('.show-on-map').forEach(function (btn) {
    btn.addEventListener('click', function () {
      toggleMap.checked = true;
      applyToggleMap();
      const marker = markersByTarget.get(btn.dataset.target);
      if (marker) {
        markersCluster.zoomToShowLayer(marker, function () {
          marker.openPopup();
        });
      }
    });
  });

  document.querySelectorAll('.show-details').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const target = document.querySelector(btn.dataset.target);
      if (target) {
        target.classList.toggle('fr-hidden');
      }
    });
  });

  searchForm.addEventListener('change', function (e) {
    if (e.target.name === 'territoryId') {
      applyFilters();
    }
  });

  searchForm.querySelector('button[type="reset"]').addEventListener('click', function () {
    document.querySelectorAll('.same-address-item').forEach(function (item) {
      item.classList.add('is-active-filters');
    });
    initCounters();
    map ? initMarkers() : initMap();
    showPage(1);
  });

  ['address', 'commune', 'bailleur'].forEach(function (field) {
    const inputEl = searchForm.querySelector('[name="' + field + '"]');
    inputEl.addEventListener('focus', function () {
      showSuggestions(field, inputEl.value);
    });
    inputEl.addEventListener('input', function () {
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
