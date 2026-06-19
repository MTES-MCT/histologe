import L from 'leaflet';
import 'leaflet.vectorgrid';

// Patch pour rendre les couches vectorielles interactives : https://github.com/Leaflet/Leaflet.VectorGrid/issues/274#issuecomment-1371640331
// Appliqué une seule fois à l'import du module.
if (L.Canvas && L.Canvas.Tile) {
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
}
// Fin du patch

export const buildingStyles = {
  initial: {
    radius: 5,
    fillColor: '#000091',
    color: '#ffffff',
    weight: 2,
    fill: true,
    fillOpacity: 1,
    opacity: 1,
  },
  clicked: {
    radius: 5,
    fillColor: '#18753c',
    color: '#ffffff',
    weight: 2,
    fill: true,
    fillOpacity: 1,
    opacity: 1,
  },
  focused: {
    radius: 7,
    fillColor: '#000091',
    color: '#ffffff',
    weight: 2,
    fill: true,
    fillOpacity: 1,
    opacity: 1,
  },
};

/**
 * Initialise la navigation clavier, les marqueurs numérotés et le fetch des bâtiments RNB.
 *
 * 
 * @param {object} options
 * @param {HTMLElement} options.mapContainer - Élément DOM de la carte (recevra les keydown)
 * @param {L.Map} options.map
 * @param {object} options.vectorTileLayer
 * @param {string|undefined} options.previousRnbId - RNB ID précédemment sélectionné (restauration)
 * @param {(rnbId: string) => void} options.onSelect - Appelé à chaque sélection (clic ou clavier)
 * @param {(text: string) => void} [options.onAnnounce] - Appelé pour les annonces aria-live
 * @param {() => void} [options.onFocusSubmit] - Appelé après sélection au clavier
 * @returns {{ destroy: () => void }}
 */
export function createRnbMapController ({
  mapContainer,
  map,
  vectorTileLayer,
  previousRnbId,
  onSelect,
  onAnnounce,
  onFocusSubmit,
}) {
  let currentBuildings = [];
  let buildingMarkers = [];
  let focusedIndex = -1;
  let keyboardPanning = false;
  let activePreviousRnbId = previousRnbId;

  function markerHtml (index, state) {
    if (state === 'focused') {
      return `<div class="rnb-marker rnb-marker--focused"></div>`;
    }
    if (state === 'selected') {
      return `<div class="rnb-marker rnb-marker--selected"><div class="rnb-marker__dot"></div></div>`;
    }
    return `<div class="rnb-marker rnb-marker--default"></div>`;
  }

  function updateMarker (index, state) {
    const el = buildingMarkers[index]?.getElement();
    if (el) el.innerHTML = markerHtml(index, state);
  }

  function refreshBuildings (buildings) {
    // Réinitialiser le style du bâtiment en cours de focus avant de tout effacer
    if (focusedIndex >= 0 && focusedIndex < currentBuildings.length) {
      const prev = currentBuildings[focusedIndex];
      vectorTileLayer.setFeatureStyle(
        prev.rnb_id,
        prev.rnb_id === activePreviousRnbId ? buildingStyles.clicked : buildingStyles.initial
      );
    }
    buildingMarkers.forEach((m) => map.removeLayer(m));
    buildingMarkers = [];
    focusedIndex = -1;
    currentBuildings = buildings;

    buildings.forEach((building, index) => {
      const [bLng, bLat] = building.point.coordinates;
      const state = building.rnb_id === activePreviousRnbId ? 'selected' : 'default';
      const marker = L.marker([bLat, bLng], {
        icon: L.divIcon({
          html: markerHtml(index, state),
          className: '',
          iconSize: [12, 12],   // doit correspondre à la taille CSS de .rnb-marker
          iconAnchor: [6, 6],   // centre du cercle
        }),
        interactive: false,
        zIndexOffset: 1000,
      });
      marker.addTo(map);
      buildingMarkers.push(marker);
    });

    if (buildings.length > 0) {
      mapContainer.setAttribute(
        'aria-label',
        `Carte de sélection du bâtiment. ${buildings.length} bâtiment(s) numéroté(s) sur la carte. Touches fléchées ou Tab pour naviguer, Entrée pour sélectionner.`
      );
    }
  }

  function fetchBuildings () {
    const bounds = map.getBounds();
    const center = map.getCenter();
    const bbox = `${bounds.getWest()},${bounds.getSouth()},${bounds.getEast()},${bounds.getNorth()}`;
    fetch(`https://rnb-api.beta.gouv.fr/api/alpha/buildings/?bbox=${bbox}&limit=100`)
      .then((res) => res.json())
      .then((data) => {
        const sorted = (data.results || [])
          .filter((b) => b.point && b.point.coordinates)
          .sort((a, b) => {
            const da = Math.hypot(a.point.coordinates[1] - center.lat, a.point.coordinates[0] - center.lng);
            const db = Math.hypot(b.point.coordinates[1] - center.lat, b.point.coordinates[0] - center.lng);
            return da - db;
          });
        refreshBuildings(sorted);
      })
      .catch(() => {});
  }

  function focusBuilding (index) {
    if (focusedIndex >= 0 && focusedIndex < currentBuildings.length) {
      const prev = currentBuildings[focusedIndex];
      const prevState = prev.rnb_id === activePreviousRnbId ? 'selected' : 'default';
      vectorTileLayer.setFeatureStyle(prev.rnb_id, prevState === 'selected' ? buildingStyles.clicked : buildingStyles.initial);
      updateMarker(focusedIndex, prevState);
    }
    focusedIndex = index;
    const building = currentBuildings[index];
    vectorTileLayer.setFeatureStyle(building.rnb_id, buildingStyles.focused);
    updateMarker(index, 'focused');
    const [bLng, bLat] = building.point.coordinates;
    if (!map.getBounds().contains([bLat, bLng])) {
      keyboardPanning = true;
      map.panTo([bLat, bLng]);
    }
    if (onAnnounce) {
      onAnnounce(`Bâtiment ${index + 1} sur ${currentBuildings.length}, identifiant ${building.rnb_id}`);
    }
  }

  function selectBuilding () {
    if (focusedIndex < 0) return;
    const building = currentBuildings[focusedIndex];
    const rnbId = building.rnb_id;
    if (activePreviousRnbId !== undefined) {
      vectorTileLayer.setFeatureStyle(activePreviousRnbId, buildingStyles.initial);
      const oldIdx = currentBuildings.findIndex((b) => b.rnb_id === activePreviousRnbId);
      if (oldIdx >= 0) updateMarker(oldIdx, 'default');
    }
    vectorTileLayer.setFeatureStyle(rnbId, buildingStyles.clicked);
    updateMarker(focusedIndex, 'selected');
    activePreviousRnbId = rnbId;
    onSelect(rnbId);
    if (onAnnounce) {
      onAnnounce(`Bâtiment ${focusedIndex + 1} sélectionné, identifiant ${rnbId}`);
    }
    if (onFocusSubmit) {
      onFocusSubmit();
    }
  }

  // Clic souris sur un bâtiment vectoriel
  vectorTileLayer.on('click', (e) => {
    const rnbId = e.layer.properties.rnb_id;
    if (activePreviousRnbId !== undefined) {
      vectorTileLayer.setFeatureStyle(activePreviousRnbId, buildingStyles.initial);
      const oldIdx = currentBuildings.findIndex((b) => b.rnb_id === activePreviousRnbId);
      if (oldIdx >= 0) updateMarker(oldIdx, 'default');
    }
    vectorTileLayer.setFeatureStyle(rnbId, buildingStyles.clicked);
    activePreviousRnbId = rnbId;
    const selIdx = currentBuildings.findIndex((b) => b.rnb_id === rnbId);
    if (selIdx >= 0) updateMarker(selIdx, 'selected');
    onSelect(rnbId);
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

  // Navigation clavier : Tab/Shift+Tab, flèches, Entrée/Espace
  const keyboardHandler = (e) => {
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
        break;
    }
  };

  mapContainer.addEventListener('keydown', keyboardHandler);

  // Restaurer le style du bâtiment précédemment sélectionné
  if (activePreviousRnbId) {
    vectorTileLayer.setFeatureStyle(activePreviousRnbId, buildingStyles.clicked);
  }

  // Fetch initial des bâtiments visibles
  fetchBuildings();

  return {
    destroy () {
      mapContainer.removeEventListener('keydown', keyboardHandler);
      buildingMarkers.forEach((m) => map.removeLayer(m));
      buildingMarkers = [];
      currentBuildings = [];
    },
  };
}
