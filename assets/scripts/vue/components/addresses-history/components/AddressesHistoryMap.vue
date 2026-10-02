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
import { getArretePictoClassFromId } from '../utils/displayHelpers'
// @ts-ignore
import { parse } from 'wellknown'

// State
const sharedState = store.state

// Refs
const mapContainer = ref<HTMLElement | null>(null)
let map: maplibregl.Map | null = null
let currentPopup: maplibregl.Popup | null = null
const SOURCE_ID = 'addresses-history'
const ZONES_SOURCE_ID = 'zones-territory'
const ZONES_LAYER_ID = 'zones-territory-layer'
const ZONES_OUTLINE_LAYER_ID = 'zones-territory-outline'

// Couleurs des clusters selon leur taille (valeurs DSFR, les paints MapLibre
// étant rendus sur un canvas WebGL, on ne peut pas y utiliser de var() CSS)
const CLUSTER_COLOR_SMALL = '#efcb3a' // $yellow-tournesol-850, moins de 10 adresses
const CLUSTER_COLOR_MEDIUM = '#fbb8f6' // $purple-glycine-850, de 10 à 99 adresses
const CLUSTER_COLOR_LARGE = '#fcc0b0' // $orange-terre-battue-850, 100 adresses et plus
const MARKER_DOSSIERS_MULTIPLES = '#298641' // jaune pour adresse avec dossiers multiples
const MARKER_MULTIPLE_EVENTS = '#E91719' // rouge pour adresse avec événements multiples
const MARKER_FALLBACK = '#000091' // bleu

// Constaté sur les fixtures, mais fallback pour les cas réels, au cas où... :
// Rayon en mètres sur lequel on répartit les adresses partageant exactement les mêmes lat / lng
const OVERLAPPING_ADDRESSES_OFFSET_METERS = 8
const METERS_PER_DEGREE_LATITUDE = 111320

// Icônes des points isolés avec picto d'arrêtés : générées en canvas (sinon on n'a que des cercles)
const MARKER_ICON_SIZE = 24
const MARKER_SHAPE_SIZE = 20
const MARKER_ICON_DOSSIERS_MULTIPLES = 'marker-dossiers-multiples'
const MARKER_ICON_MULTIPLE_EVENTS = 'marker-multiple-events'
const MARKER_ICON_DEFAULT = 'marker-adresse'
const MARKER_ICON_ARRETE_PREFIX = 'marker-arrete-'

// Triangle pointe en haut, comme le `fr-icon-triangle-fill` de la légende
const MULTIPLE_EVENTS_MARKER_POINTS: Array<[number, number]> = [[0.5, 0], [1, 1], [0, 1]]

/**
 * Formes des pictos d'arrêtés en coordonnées relatives (0 → 1), reprises des
 * `clip-path` de `.fr-picto-arrete`
 */
const ARRETE_MARKER_SHAPES: Record<string, { color: string, points: Array<[number, number]> }> = {
  'purple-hexagon': {
    color: '#6c63ff',
    points: [[1, 0.3], [1, 0.7], [0.5, 1], [0, 0.7], [0, 0.3], [0.5, 0]]
  },
  'blue-square': {
    color: '#2196f3',
    points: [[0, 0], [1, 0], [1, 1], [0, 1]]
  },
  'purple-diamond': {
    color: '#9c27b0',
    points: [[0.5, 0], [1, 0.5], [0.5, 1], [0, 0.5]]
  }
}

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

// Fermeture de popup quand filtre change
watch([() => sharedState.input.filters, () => sharedState.input.params], () => {
  closePopup()
}, { deep: true })

function closePopup() {
  if (currentPopup) {
    currentPopup.remove()
    currentPopup = null
  }
}

/**
 * Répartit en cercle les adresses situées à des coordonnées identiques.
 */
function spreadOverlappingCoordinates(
  lng: number,
  lat: number,
  positionInGroup: number,
  groupSize: number
): [number, number] {
  if (groupSize < 2) {
    return [lng, lat]
  }

  const angle = (2 * Math.PI * positionInGroup) / groupSize
  const offsetLat = (OVERLAPPING_ADDRESSES_OFFSET_METERS * Math.sin(angle)) / METERS_PER_DEGREE_LATITUDE
  const offsetLng = (OVERLAPPING_ADDRESSES_OFFSET_METERS * Math.cos(angle))
    / (METERS_PER_DEGREE_LATITUDE * Math.cos((lat * Math.PI) / 180))

  return [lng + offsetLng, lat + offsetLat]
}

/**
 * Types de données présents à l'adresse, dans l'ordre de la légende : dossiers
 * multiples, puis un type par groupe d'arrêtés rencontré (sans doublon).
 */
function getMarkerTypes(address: any, nbSignalements: number): string[] {
  const types: string[] = []

  if (nbSignalements > 1) {
    types.push(MARKER_ICON_DOSSIERS_MULTIPLES)
  }

  const arretePictos = new Set<string>()
  address.arretes?.forEach((arrete: any) => {
    if (arrete.arreteType) {
      arretePictos.add(getArretePictoClassFromId(arrete.arreteType, sharedState.arreteTypesGroups))
    }
  })
  arretePictos.forEach((picto) => types.push(MARKER_ICON_ARRETE_PREFIX + picto))

  return types
}

/**
 * Icône du point isolé :
 * - triangle si plusieurs types de données coexistent à l'adresse
 * - sinon le picto de l'unique type présent (rond dossiers multiples, ou picto d'arrêté)
 */
function getMarkerIcon(address: any, nbSignalements: number): string {
  const types = getMarkerTypes(address, nbSignalements)

  if (types.length > 1) {
    return MARKER_ICON_MULTIPLE_EVENTS
  }

  return types[0] ?? MARKER_ICON_DEFAULT
}

/**
 * Dessine une icône sur un canvas et l'enregistre auprès de la carte.
 * Les images sont perdues à chaque changement de style, d'où le garde `hasImage`.
 */
function registerMarkerImage(id: string, paint: (context: CanvasRenderingContext2D) => void) {
  if (!map || map.hasImage(id)) return

  const pixelRatio = Math.max(1, Math.round(window.devicePixelRatio || 1))
  const canvas = document.createElement('canvas')
  canvas.width = MARKER_ICON_SIZE * pixelRatio
  canvas.height = MARKER_ICON_SIZE * pixelRatio

  const context = canvas.getContext('2d')
  if (!context) return

  context.scale(pixelRatio, pixelRatio)
  paint(context)

  map.addImage(id, context.getImageData(0, 0, canvas.width, canvas.height), { pixelRatio })
}

function drawMarkerCircle(context: CanvasRenderingContext2D, fillColor: string, strokeColor: string) {
  const center = MARKER_ICON_SIZE / 2

  context.beginPath()
  context.arc(center, center, 10, 0, 2 * Math.PI)
  context.fillStyle = fillColor
  context.fill()
  context.lineWidth = 2
  context.strokeStyle = strokeColor
  context.stroke()
}

function drawMarkerShape(context: CanvasRenderingContext2D, color: string, points: Array<[number, number]>) {
  const padding = (MARKER_ICON_SIZE - MARKER_SHAPE_SIZE) / 2

  context.beginPath()
  points.forEach(([x, y], index) => {
    const pointX = padding + x * MARKER_SHAPE_SIZE
    const pointY = padding + y * MARKER_SHAPE_SIZE
    if (0 === index) {
      context.moveTo(pointX, pointY)
    } else {
      context.lineTo(pointX, pointY)
    }
  })
  context.closePath()
  context.fillStyle = color
  context.fill()
}

function addMarkerIcons() {
  // Rond jaune plein : plusieurs dossiers à l'adresse
  registerMarkerImage(MARKER_ICON_DOSSIERS_MULTIPLES, (context) => {
    drawMarkerCircle(context, MARKER_DOSSIERS_MULTIPLES, MARKER_DOSSIERS_MULTIPLES)
  })

  // Triangle : plusieurs types de données à l'adresse
  registerMarkerImage(MARKER_ICON_MULTIPLE_EVENTS, (context) => {
    drawMarkerShape(context, MARKER_MULTIPLE_EVENTS, MULTIPLE_EVENTS_MARKER_POINTS)
  })

  // Rond bleu : ni dossiers multiples, ni arrêté (fallback - ne devrait pas arriver)
  registerMarkerImage(MARKER_ICON_DEFAULT, (context) => {
    drawMarkerCircle(context, MARKER_FALLBACK, MARKER_FALLBACK)
  })

  // Un picto par groupe de types d'arrêtés
  Object.entries(ARRETE_MARKER_SHAPES).forEach(([picto, shape]) => {
    registerMarkerImage(MARKER_ICON_ARRETE_PREFIX + picto, (context) => {
      drawMarkerShape(context, shape.color, shape.points)
    })
  })
}

function buildGeoJson(): GeoJSON.FeatureCollection<GeoJSON.Point> {
  const features: GeoJSON.Feature<GeoJSON.Point>[] = []

  // Regroupement préalable par coordonnée, pour repérer les adresses superposées
  const addressesByCoordinates = new Map<string, Array<{ address: any, index: number }>>()
  filteredAddresses.value.forEach((address: any, index: number) => {
    if (address.lat && address.lng) {
      const coordinatesKey = `${address.lng}|${address.lat}`
      const group = addressesByCoordinates.get(coordinatesKey)
      if (group) {
        group.push({ address, index })
      } else {
        addressesByCoordinates.set(coordinatesKey, [{ address, index }])
      }
    }
  })

  addressesByCoordinates.forEach((group) => {
    group.forEach(({ address, index }, positionInGroup) => {
      const nbSignalements = address.signalements?.length || 0
      const hasMultipleSignalements = nbSignalements > 1

      features.push({
        type: 'Feature',
        geometry: {
          type: 'Point',
          coordinates: spreadOverlappingCoordinates(
            Number(address.lng),
            Number(address.lat),
            positionInGroup,
            group.length
          )
        },
        properties: {
          addressId: index,
          addressForHuman: address.addressForHuman,
          communeForHuman: address.communeForHuman,
          nbSignalements: nbSignalements,
          nbArretes: address.arretes?.length || 0,
          hasMultipleSignalements: hasMultipleSignalements,
          markerIcon: getMarkerIcon(address, nbSignalements)
        }
      })
    })
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

  addMarkerIcons()

  // Clusters
  map.addLayer({
    id: 'clusters',
    type: 'circle',
    source: SOURCE_ID,
    filter: ['has', 'point_count'],
    paint: {
      'circle-radius': 14,
      'circle-color': [
        'step',
        ['get', 'point_count'],
        CLUSTER_COLOR_SMALL,
        10, CLUSTER_COLOR_MEDIUM,
        100, CLUSTER_COLOR_LARGE
      ],
      'circle-opacity': 0.85
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
      'text-color': '#000000'
    }
  })

  // Points isolés : icône générée via getMarkerIcon
  map.addLayer({
    id: 'unclustered-point',
    type: 'symbol',
    source: SOURCE_ID,
    filter: ['!', ['has', 'point_count']],
    layout: {
      'icon-image': ['get', 'markerIcon'],
      // Sans ça, MapLibre masque les icônes qui se chevauchent
      'icon-allow-overlap': true,
      'icon-ignore-placement': true
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
