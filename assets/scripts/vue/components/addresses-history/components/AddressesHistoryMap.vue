<template>
  <AddressesHistoryMapFilters />
  <section class="container-addresses-history-map">
    <div id="map-addresses-history" ref="mapContainer"></div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, createApp, h } from 'vue'
import { store } from '../composables/useAddressesHistoryStore'
import AddressesHistoryMapFilters from './AddressesHistoryMapFilters.vue'
import AddressMapPopupContent from './AddressMapPopupContent.vue'
import * as maplibregl from 'maplibre-gl'
import '../../../../vanilla/services/component/maplibre-worker.js'
import type { GeoJSONSource } from 'maplibre-gl'
import 'maplibre-gl/dist/maplibre-gl.css'
import { mapStyles, Overlay, addOverlay, removeOverlay } from 'carte-facile'
import 'carte-facile/carte-facile.css'
import { AddressFilterService } from '../services/AddressFilterService'
// @ts-ignore
import { parse } from 'wellknown'

// State
const sharedState = store.state
const sharedProps = store.props

// Refs
const mapContainer = ref<HTMLElement | null>(null)
let map: maplibregl.Map | null = null
let currentPopup: maplibregl.Popup | null = null
const SOURCE_ID = 'addresses-history'
const ZONES_SOURCE_ID = 'zones-territory'
const ZONES_LAYER_ID = 'zones-territory-layer'
const ZONES_OUTLINE_LAYER_ID = 'zones-territory-outline'

// Computed - Liste des adresses filtrées côté client
const filteredAddresses = computed(() => {
  const addresses = sharedState.addresses.allAddresses.length > 0
    ? sharedState.addresses.allAddresses
    : sharedState.addresses.list

  return AddressFilterService.filterAddresses(
    addresses,
    sharedState.input.filters,
    { mainLeveeUniquement: sharedState.input.params.mainLeveeUniquement }
  )
})

watch (() => sharedState.input.params.niveauxGris, (newValue) => {
  if (map) {
    const newStyle = newValue ? mapStyles.desaturated : mapStyles.simple

    map.once('styledata', () => {
      // Réajoute la source et les couches après le changement de style
      if (!map!.getSource(SOURCE_ID)) {
        addSourceToMap()
        addMapLayers()
        addMapEvents()
      }

      // Réajoute les zones si elles étaient affichées
      if (sharedState.input.params.zonesTerritoire) {
        addZonesToMap()
      }
    })

    map.setStyle(newStyle)
  }
})

watch (() => sharedState.input.params.limitesAdministratives, (newValue) => {
  if (map) {
    if (newValue) {
      addOverlay(map, Overlay.administrativeBoundaries)
    } else {
      removeOverlay(map, Overlay.administrativeBoundaries)
    }
  }
})

watch (() => sharedState.input.params.zonesTerritoire, (newValue) => {
  if (map) {
    if (newValue) {
      addZonesToMap()
    } else {
      removeZonesFromMap()
    }
  }
})

// Watch zoneAreas changes
watch(() => sharedState.addresses.zoneAreas, () => {
  if (map && sharedState.input.params.zonesTerritoire) {
    updateZonesOnMap()
  }
}, { deep: true })

// Watch filtered addresses
watch(filteredAddresses, () => {
  if (map) {
    updateMapData()
  }
}, { deep: true })

// La popup ouverte porte sur une adresse qui peut disparaître de la carte :
// on la ferme dès qu'un filtre ou un paramètre d'affichage change
watch([() => sharedState.input.filters, () => sharedState.input.params], () => {
  closePopup()
}, { deep: true })

function closePopup() {
  if (currentPopup) {
    currentPopup.remove()
    currentPopup = null
  }
}

function buildGeoJson(): GeoJSON.FeatureCollection<GeoJSON.Point> {
  const features: GeoJSON.Feature<GeoJSON.Point>[] = []

  filteredAddresses.value.forEach((address: any, index: number) => {
    if (address.lat && address.lng) {
      const nbSignalements = address.signalements?.length || 0
      const hasMultipleSignalements = nbSignalements > 1

      features.push({
        type: 'Feature',
        geometry: {
          type: 'Point',
          coordinates: [address.lng, address.lat]
        },
        properties: {
          addressId: index,
          addressForHuman: address.addressForHuman,
          communeForHuman: address.communeForHuman,
          nbSignalements: nbSignalements,
          nbArretes: address.arretes?.length || 0,
          hasMultipleSignalements: hasMultipleSignalements
        }
      })
    }
  })

  return {
    type: 'FeatureCollection',
    features
  }
}

/**
 * Convertit les WKT des zones en GeoJSON
 */
function buildZonesGeoJson(): GeoJSON.FeatureCollection {
  const features: GeoJSON.Feature[] = []

  sharedState.addresses.zoneAreas.forEach((wkt: string, index: number) => {
    try {
      const geometry = parse(wkt)
      if (geometry) {
        features.push({
          type: 'Feature',
          geometry: geometry,
          properties: {
            zoneId: index
          }
        })
      }
    } catch (error) {
      console.error('Error parsing WKT:', error)
    }
  })

  return {
    type: 'FeatureCollection',
    features
  }
}

/**
 * Ajoute les zones sur la carte
 */
function addZonesToMap() {
  if (!map) return

  // Si la source n'existe pas, la créer
  if (!map.getSource(ZONES_SOURCE_ID)) {
    map.addSource(ZONES_SOURCE_ID, {
      type: 'geojson',
      data: buildZonesGeoJson()
    })

    // Couche de remplissage
    map.addLayer({
      id: ZONES_LAYER_ID,
      type: 'fill',
      source: ZONES_SOURCE_ID,
      paint: {
        'fill-color': '#0063cb',
        'fill-opacity': 0.15
      }
    }, 'clusters') // Ajouter sous les clusters

    // Couche de contour
    map.addLayer({
      id: ZONES_OUTLINE_LAYER_ID,
      type: 'line',
      source: ZONES_SOURCE_ID,
      paint: {
        'line-color': '#0063cb',
        'line-width': 2
      }
    }, 'clusters')
  } else {
    // Si la source existe déjà, juste mettre à jour les données
    updateZonesOnMap()
    // Et afficher les couches
    map.setLayoutProperty(ZONES_LAYER_ID, 'visibility', 'visible')
    map.setLayoutProperty(ZONES_OUTLINE_LAYER_ID, 'visibility', 'visible')
  }
}

/**
 * Retire les zones de la carte
 */
function removeZonesFromMap() {
  if (!map) return

  if (map.getLayer(ZONES_LAYER_ID)) {
    map.setLayoutProperty(ZONES_LAYER_ID, 'visibility', 'none')
  }
  if (map.getLayer(ZONES_OUTLINE_LAYER_ID)) {
    map.setLayoutProperty(ZONES_OUTLINE_LAYER_ID, 'visibility', 'none')
  }
}

/**
 * Met à jour les données des zones
 */
function updateZonesOnMap() {
  if (!map) return

  const source = map.getSource(ZONES_SOURCE_ID) as GeoJSONSource
  if (source) {
    source.setData(buildZonesGeoJson())
  }
}

function addMapLayers() {
  if (!map) return

  // Clusters
  map.addLayer({
    id: 'clusters',
    type: 'circle',
    source: SOURCE_ID,
    filter: ['has', 'point_count'],
    paint: {
      'circle-radius': 14,
      'circle-color': '#a9bfff',
      'circle-stroke-width': 2,
      'circle-stroke-color': '#0063cb'
    }
  })

  map.addLayer({
    id: 'cluster-count',
    type: 'symbol',
    source: SOURCE_ID,
    filter: ['has', 'point_count'],
    layout: {
      'text-field': '{point_count_abbreviated}',
      'text-font': ['Noto Sans Bold'],
      'text-size': 11
    },
    paint: {
      'text-color': '#0063cb'
    }
  })

  // Points isolés
  map.addLayer({
    id: 'unclustered-point',
    type: 'circle',
    source: SOURCE_ID,
    filter: ['!', ['has', 'point_count']],
    paint: {
      'circle-radius': 10,
      'circle-color': [
        'case',
        ['get', 'hasMultipleSignalements'],
        '#EFB900', // Jaune si dossiers multiples
        '#FFF'     // Blanc sinon
      ],
      'circle-opacity': 0.8,
      'circle-stroke-width': 2,
      'circle-stroke-color': [
        'case',
        ['get', 'hasMultipleSignalements'],
        '#EFB900', // Jaune si dossiers multiples
        '#000091'  // Bleu sinon
      ]
    }
  })
}

function addMapEvents() {
  if (!map) return

  map.on('click', 'clusters', (e: maplibregl.MapMouseEvent) => {
    const features = map!.queryRenderedFeatures(e.point, { layers: ['clusters'] })
    if (features.length === 0) return

    const clusterId = features[0].properties?.cluster_id
    if (!clusterId) return

    const source = map!.getSource(SOURCE_ID) as GeoJSONSource
    source.getClusterExpansionZoom(clusterId).then((zoom: number) => {
      const geometry = features[0].geometry
      if (geometry.type === 'Point') {
        map!.easeTo({
          center: geometry.coordinates as [number, number],
          zoom: zoom + 0.5
        })
      }
    })
  })

  map.on('click', 'unclustered-point', (e: maplibregl.MapLayerMouseEvent) => {
    const features = e.features
    if (!features || features.length === 0) return

    const feature = features[0]
    const { addressId, addressForHuman } = feature.properties || {}

    closePopup()

    const address = filteredAddresses.value[addressId]
    const arretes = address?.arretes || []
    const signalements = address?.signalements || []

    // Conteneur pour monter le composant Vue dynamiquement
    const popupContainer = document.createElement('div')
    const app = createApp({
      render: () => h(AddressMapPopupContent, {
        addressForHuman,
        arretes,
        signalements
      })
    })
    app.mount(popupContainer)

    const geometry = feature.geometry
    if (geometry.type === 'Point') {
      currentPopup = new maplibregl.Popup({
        offset: 12,
        closeButton: true
      })
        .setLngLat(geometry.coordinates as [number, number])
        .setDOMContent(popupContainer)
        .addTo(map!)

      // Ajout artificiel du texte "Fermer" après la croix
      const closeButton = currentPopup.getElement()?.querySelector('.maplibregl-popup-close-button')
      if (closeButton) {
        closeButton.textContent = '× Fermer'
      }

      // Et on supprime le composant Vue quand la popup est fermée
      currentPopup.on('close', () => {
        app.unmount()
      })
    }
  })

  ;['clusters', 'unclustered-point'].forEach(layerId => {
    map!.on('mouseenter', layerId, () => {
      map!.getCanvas().style.cursor = 'pointer'
    })
    map!.on('mouseleave', layerId, () => {
      map!.getCanvas().style.cursor = ''
    })
  })
}

function initMap() {
  if (!mapContainer.value) return

  map = new maplibregl.Map({
    container: mapContainer.value,
    style: mapStyles.simple,
    center: [2, 47],
    zoom: 6
  })

  map.addControl(new maplibregl.NavigationControl({ showCompass: false }))

  map.on('load', () => {
    addSourceToMap()
    addMapLayers()
    addMapEvents()

    // Ajout des zones si le toggle est activé
    if (sharedState.input.params.zonesTerritoire) {
      addZonesToMap()
    }

    fitMapToMarkers()
  })
}

function addSourceToMap() {
  map!.addSource(SOURCE_ID, {
    type: 'geojson',
    data: buildGeoJson(),
    cluster: true,
    clusterMaxZoom: 17,
    clusterRadius: 50
  })
}

function updateMapData() {
  if (!map) return

  const source = map.getSource(SOURCE_ID) as GeoJSONSource
  if (source) {
    source.setData(buildGeoJson())
    fitMapToMarkers()
  }
}

function fitMapToMarkers() {
  if (!map || filteredAddresses.value.length === 0) return

  const validAddresses = filteredAddresses.value.filter((a: any) => a.lat && a.lng)
  if (validAddresses.length === 0) return

  const bounds = new maplibregl.LngLatBounds()
  validAddresses.forEach((address: any) => {
    bounds.extend([address.lng!, address.lat!])
  })

  map.fitBounds(bounds, { padding: 20, maxZoom: 16, duration: 1000 })
}


onMounted(() => {
  initMap()
})

onUnmounted(() => {
  closePopup()
  if (map) {
    map.remove()
    map = null
  }
})
</script>

<style scoped>
.container-addresses-history-map {
  width: 100%;
  height: 70vh;
}
</style>

<style>
/*
Styles pour la popup et son bouton de fermeture
Ils doivent être conservés ici plutôt que dans le composant créé dynamiquement
*/

/*
MapLibre applique `overflow: hidden` sur le conteneur de la carte, ce qui tronque
les popups ancrées près des bords. On autorise le débordement pour qu'une popup
puisse sortir de la carte plutôt que d'être masquée.
*/
#map-addresses-history.maplibregl-map {
  overflow: visible;
}

/* Au-dessus du panneau de filtres (z-index: 100) quand la popup déborde de la carte */
#map-addresses-history .maplibregl-popup {
  z-index: 200;
}

.maplibregl-popup-content {
  min-width: 280px;
  border-radius: 0.5rem;
  box-shadow: 0 8px 16px -1px rgba(0, 0, 0, 0.3), 0 8px 16px -1px rgba(0, 0, 0, 0.3);
}

/*
Le contenu monté dynamiquement défile au-delà d'une certaine hauteur : la popup
reste ainsi dans la fenêtre même avec de nombreux arrêtés ou dossiers.
*/
#map-addresses-history .maplibregl-popup-content > div {
  max-height: 60vh;
  overflow-y: auto;
}

.maplibregl-popup-close-button {
  color: var(--blue-france-sun-113-625);
  margin-top: 0.5rem;
  margin-right: 0.5rem;
  font-size: 0.8rem;
}
</style>
