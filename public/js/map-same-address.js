var map = null;
var markersByTarget = new Map();

function initMap() {
    if (map) {
        map.remove();
    }

    map = L.map('map-same-address').setView([47, 2], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        referrerPolicy: "origin"
    }).addTo(map);

    initMarkers();
}

function initMarkers() {
    markersByTarget.clear();

    var allItems = Array.from(document.querySelectorAll('.same-address-item'));

    document.querySelectorAll('.same-address-item.is-active-filters').forEach(function (item) {
        var lat = parseFloat(item.dataset.lat);
        var lng = parseFloat(item.dataset.lng);

        if (isNaN(lat) || isNaN(lng)) {
            return;
        }

        var index = allItems.indexOf(item);
        var targetId = '#same-address-details-' + (index + 1);
        var detailsEl = document.getElementById('same-address-details-' + (index + 1));
        var popupContent = detailsEl ? detailsEl.innerHTML : '';

        var marker = L.circleMarker([lat, lng], {
            radius: 5,
            fillColor: '#6a6af4',
            color: '#000000',
            weight: 1,
            opacity: 1,
            fillOpacity: 0.8,
        })
        .bindPopup(popupContent)
        .addTo(map);

        markersByTarget.set(targetId, marker);
    });

    if (markersByTarget.size > 0) {
        var group = L.featureGroup(Array.from(markersByTarget.values()));
        map.fitBounds(group.getBounds().pad(0.01));
    }
}

document.querySelectorAll('.show-on-map').forEach(function (btn) {
    btn.addEventListener('click', function () {
        toggle.checked = true;
        applyMapToggle();
        var marker = markersByTarget.get(btn.dataset.target);
        if (marker) {
            map.setView(marker.getLatLng());
            marker.openPopup();
        }
    });
});

//mise a jour des éléments à la modification d'un filtre du form #search-signalement-form
const searchForm = document.getElementById('search-same-address-form');
searchForm.addEventListener('change', function () {
    const territoryId = searchForm.querySelector('[name="territoryId"]')?.value;
    const addressFilter = searchForm.querySelector('[name="address"]').value;
    const communeFilter = searchForm.querySelector('[name="commune"]').value;
    const bailleurFilter = searchForm.querySelector('[name="bailleur"]').value;
    document.querySelectorAll('.same-address-item').forEach(function (item) {
        item.classList.add('is-active-filters');
        if (territoryId && item.dataset.territoryId !== territoryId) {
            item.classList.remove('is-active-filters');
        }
        if (addressFilter && item.dataset.address !== addressFilter) {
            item.classList.remove('is-active-filters');
        }
        if (communeFilter && item.dataset.commune !== communeFilter) {
            item.classList.remove('is-active-filters');
        }
        if (bailleurFilter && item.dataset.bailleur !== bailleurFilter) {
            item.classList.remove('is-active-filters');
        }
    });
    initCounters();
    initMap();
    showPage(1);
});

// Déclenchement immédiat lors de la sélection d'une suggestion dans un datalist
searchForm.querySelectorAll('input[list]').forEach(function (input) {
    input.addEventListener('input', function () {
        var list = document.getElementById(input.getAttribute('list'));
        if (!list) return;
        var options = Array.from(list.querySelectorAll('option')).map(function (o) { return o.value; });
        if (input.value === '' || options.includes(input.value)) {
            searchForm.dispatchEvent(new Event('change'));
        }
    });
});
//reset des filtres
searchForm.querySelector('button[type="reset"]').addEventListener('click', function () {
    document.querySelectorAll('.same-address-item').forEach(function (item) {
        item.classList.add('is-active-filters');
    });
    initCounters();
    initMap();
    showPage(1);
});


//affichage des détails pour les adresses sans coordonnées
document.querySelectorAll('.show-details').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var target = document.querySelector(btn.dataset.target);
        if (target) {
            target.classList.toggle('fr-hidden');
        }
    });
});

function initCounters() {
    var nbAddresses = 0;
    var nbDossiers = document.querySelectorAll('.same-address-item.is-active-filters').length;
    document.querySelectorAll('.same-address-item.is-active-filters').forEach(function (item) {
        nbAddresses += parseInt(item.dataset.nb) || 0;
    });
    var counterEl = document.querySelector('.title-counters-same-address');
    if (counterEl) {
        counterEl.textContent = nbAddresses > 0 ? nbAddresses + ' dossiers trouvés sur ' + nbDossiers + ' adresse' + (nbDossiers > 1 ? 's' : '') : 'Aucun dossier trouvé';
    }
}

initCounters();

// Pagination JS
var ITEMS_PER_PAGE = 5;

function showPage(page) {
    document.querySelectorAll('.same-address-item').forEach(function (item) {
        if (!item.classList.contains('is-active-filters') && !item.classList.contains('fr-hidden')) {
            item.classList.add('fr-hidden');
        }
    });
    var allItems = Array.from(document.querySelectorAll('.same-address-item.is-active-filters'));
    var totalPages = Math.ceil(allItems.length / ITEMS_PER_PAGE);
    var currentPage = 1;
    currentPage = page;
    allItems.forEach(function (item, i) {
        var onCurrentPage = i >= (page - 1) * ITEMS_PER_PAGE && i < page * ITEMS_PER_PAGE;
        item.classList.toggle('fr-hidden', !onCurrentPage);
    });
    renderPagination(totalPages, currentPage);
}

function renderPagination(totalPages, currentPage) {
    var nav = document.querySelector('.pagination-full-js');
    if (!nav) return;

    if (totalPages <= 1) {
        nav.innerHTML = '';
        return;
    }

    function pageLink(page, label, extraClass) {
        return '<li><a class="fr-pagination__link' + (extraClass ? ' ' + extraClass : '') + '" href="#" data-page="' + page + '">' + label + '</a></li>';
    }

    function disabledLink(label, extraClass) {
        return '<li><a class="fr-pagination__link' + (extraClass ? ' ' + extraClass : '') + '" aria-disabled="true">' + label + '</a></li>';
    }

    var html = '<ul class="fr-pagination__list">';

    html += currentPage > 1
        ? pageLink(1, 'Première page', 'fr-pagination__link--first')
        : disabledLink('Première page', 'fr-pagination__link--first');

    html += currentPage > 1
        ? pageLink(currentPage - 1, '', 'fr-pagination__link--prev fr-pagination__link--lg-label')
        : disabledLink('', 'fr-pagination__link--prev fr-pagination__link--lg-label');

    html += '<li style="flex: 1"></li>';

    if (currentPage > 2) {
        html += pageLink(1, '1');
    }
    if (currentPage > 3) {
        html += '<li><a class="fr-pagination__link" title="placeholder">...</a></li>';
    }

    var start = Math.max(1, currentPage - 1);
    var end = Math.min(totalPages, currentPage + 1);
    for (var i = start; i <= end; i++) {
        html += '<li><a class="fr-pagination__link" href="#" data-page="' + i + '" title="Page ' + i + '"'
            + (currentPage === i ? ' aria-current="page"' : '') + '>' + i + '</a></li>';
    }

    if (currentPage < totalPages - 2) {
        html += '<li><a class="fr-pagination__link" title="placeholder">...</a></li>';
    }
    if (currentPage < totalPages - 1) {
        html += pageLink(totalPages, String(totalPages));
    }

    html += '<li style="flex: 1"></li>';

    html += currentPage < totalPages
        ? pageLink(currentPage + 1, '', 'fr-pagination__link--next fr-pagination__link--lg-label')
        : disabledLink('', 'fr-pagination__link--next fr-pagination__link--lg-label');

    html += currentPage < totalPages
        ? pageLink(totalPages, 'Dernière page', 'fr-pagination__link--last')
        : disabledLink('Dernière page', 'fr-pagination__link--last');

    html += '</ul>';
    nav.innerHTML = html;

    nav.querySelectorAll('[data-page]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            showPage(parseInt(link.dataset.page));
        });
    });
}

showPage(1);

// Appliquer les filtres au chargement (au cas où le navigateur pré-remplit les champs)
searchForm.dispatchEvent(new Event('change'));

// Gestion du toggle
var toggle = document.getElementById('toggle-map');
var listContainer = document.querySelector('.container-same-address-list');
var mapContainer = document.querySelector('.container-same-address-map');

function applyMapToggle() {
    if (toggle.checked) {
        listContainer.classList.remove('fr-col-12');
        listContainer.classList.add('fr-col-md-5');
        mapContainer.classList.remove('fr-hidden');
        if (map) {
            map.invalidateSize();
            if (markersByTarget.size > 0) {
                var group = L.featureGroup(Array.from(markersByTarget.values()));
                map.fitBounds(group.getBounds().pad(0.01));
            }
        } else {
            initMap();
        }
    } else {
        listContainer.classList.remove('fr-col-md-5');
        listContainer.classList.add('fr-col-12');
        mapContainer.classList.add('fr-hidden');
    }
}

toggle.addEventListener('change', applyMapToggle);
applyMapToggle();