<template>
  <AddressesHistoryMapFilters />
  <section class="container-addresses-history-map">
    <div id="map-addresses-history" ref="mapContainer" style="height: 600px; width: 100%;"></div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { store } from '../composables/useAddressesHistoryStore'
import AddressesHistoryMapFilters from './AddressesHistoryMapFilters.vue'
import maplibregl from 'maplibre-gl'
import type { GeoJSONSource } from 'maplibre-gl'
import 'maplibre-gl/dist/maplibre-gl.css'
import { mapStyles, Overlay, addOverlay, removeOverlay } from 'carte-facile'
import 'carte-facile/carte-facile.css'

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
  let addresses = sharedState.addresses.allAddresses.length > 0
    ? sharedState.addresses.allAddresses
    : sharedState.addresses.list

  // Filtre par territoire si sélectionné
  if (sharedState.input.filters.territoire) {
    addresses = addresses.filter((address: any) => {
      return address.territoryId?.toString() === sharedState.input.filters.territoire
    })
  }

  // Filtre par communes si sélectionnées
  const communes = sharedState.input.filters.communes
  if (communes && communes.length > 0) {
    // Normalise en tableau si c'est une string
    const communesArray = Array.isArray(communes) ? communes : [communes]

    addresses = addresses.filter((address: any) => {
      // Les communes peuvent être soit un nom de ville, soit un code postal
      return communesArray.some((commune: string) => {
        return address.ville?.toLowerCase().includes(commune.toLowerCase()) ||
               address.cp?.includes(commune)
      })
    })
  }

  return addresses
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

function buildGeoJson(): GeoJSON.FeatureCollection<GeoJSON.Point> {
  const features: GeoJSON.Feature<GeoJSON.Point>[] = []

  filteredAddresses.value.forEach((address: any, index: number) => {
    if (address.lat && address.lng) {
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
          nbSignalements: address.signalements?.length || 0,
          nbArretes: address.arretes?.length || 0
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
      const geometry = wktToGeoJSON(wkt)
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
 * Convertit un WKT en GeoJSON geometry
 */
function wktToGeoJSON(wkt: string): GeoJSON.Geometry | null {
  if (!wkt) return null

  // Supprime les espaces au début/fin
  wkt = wkt.trim()

  // POLYGON
  if (wkt.startsWith('POLYGON')) {
    const coords = wkt.match(/\(\(([^)]+)\)\)/)?.[1]
    if (!coords) return null

    const coordinates = coords.split(',').map(pair => {
      const [lng, lat] = pair.trim().split(' ').map(Number)
      return [lng, lat]
    })

    return {
      type: 'Polygon',
      coordinates: [coordinates]
    }
  }

  // MULTIPOLYGON
  if (wkt.startsWith('MULTIPOLYGON')) {
    const match = wkt.match(/MULTIPOLYGON\s*\(\(\((.+)\)\)\)/)
    if (!match) return null

    // Parse les polygones
    const polygons: number[][][][] = []
    let depth = 0
    let currentPolygon = ''

    for (let i = 13; i < wkt.length; i++) {
      const char = wkt[i]
      if (char === '(') depth++
      else if (char === ')') {
        depth--
        if (depth === 1) {
          // Fin d'un polygone
          const coordinates: number[][] = currentPolygon.trim().split(',').map(pair => {
            const [lng, lat] = pair.trim().split(' ').map(Number)
            return [lng, lat]
          })
          polygons.push([coordinates])
          currentPolygon = ''
        }
      } else if (depth === 2) {
        currentPolygon += char
      }
    }

    if (polygons.length > 0) {
      return {
        type: 'MultiPolygon',
        coordinates: polygons
      }
    }
  }

  return null
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
      'circle-color': '#FFF',
      'circle-opacity': 0.8,
      'circle-stroke-width': 2,
      'circle-stroke-color': '#000091'
    }
  })
}

function addMapEvents() {
  if (!map) return

  // Événements
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
    const { addressForHuman, communeForHuman, nbSignalements, nbArretes } = feature.properties || {}

    if (currentPopup) {
      currentPopup.remove()
    }

    const popupContent = `
      <div class="fr-p-2w">
        <strong>${addressForHuman || 'Adresse inconnue'}</strong>
        <p class="fr-text--sm fr-mb-1v">${communeForHuman || ''}</p>
        <p class="fr-text--sm fr-mb-0">
          ${nbSignalements || 0} signalement(s)<br/>
          ${nbArretes || 0} arrêté(s)
        </p>
      </div>
    `

    const geometry = feature.geometry
    if (geometry.type === 'Point') {
      currentPopup = new maplibregl.Popup({ offset: 12 })
        .setLngLat(geometry.coordinates as [number, number])
        .setHTML(popupContent)
        .addTo(map!)
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

    // Ajouter les zones si le toggle est activé
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
  if (map) {
    map.remove()
    map = null
  }
})
</script>

<style scoped>
.container-addresses-history-map {
  width: 100%;
  height: 600px;
}
</style>
