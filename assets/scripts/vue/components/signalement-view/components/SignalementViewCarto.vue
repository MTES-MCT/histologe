<template>
  <div id="map-container-wrapper" class="map-wrapper">

    <div v-if="sharedState.loadingList" class="no-signalements-message fr-m-10w fr-text--center">
        <h2 class="fr-text--light" v-if="!sharedState.hasErrorLoading">Chargement des signalements...</h2>
        <h2 class="fr-text--light" v-if="sharedState.hasErrorLoading">Erreur lors du chargement des signalements.</h2>
    </div>
    <div v-if="sharedState.signalements.list.length === 0  && !sharedState.loadingList" class="no-signalements-message">
      <h2 class="fr-text--light">Pas de signalements pour cette recherche</h2>
    </div>
    <div id="map-signalements-view" class="map-container"></div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from '../store'
import * as L from 'leaflet'
import 'leaflet.markercluster'
import { SignalementMarkerOptions } from '../interfaces/signalementMarkerOptions'
// @ts-ignore
import { parse } from 'wellknown'

// Import des fichiers CSS nécessaires pour Leaflet
import 'leaflet/dist/leaflet.css'
import 'leaflet.markercluster/dist/MarkerCluster.css'
import 'leaflet.markercluster/dist/MarkerCluster.Default.css'

// Correction des chemins des icônes
delete (L.Icon.Default.prototype as any)._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: '/build/images/leaflet/marker-icon-2x.png',
  iconUrl: '/build/images/leaflet/marker-icon.png',
  shadowUrl: '/build/images/leaflet/marker-shadow.png'
})

export default defineComponent({
  name: 'SignalementViewCarto',
  props: {
    apiUrl: { type: String, required: true },
    token: { type: String, required: true },
    formSelector: { type: String, required: true }
  },
  data () {
    return {
      map: null as L.Map | null,
      markers: L.markerClusterGroup(),
      sharedState: store.state,
      sharedProps: store.props,
      offset: 0,
      bounds: L.latLngBounds(L.latLng(-90, -180), L.latLng(90, 180)),
      defaultCenter: [47.11, -0.01] as L.LatLngExpression,
      defaultZoom: 5,
      zonesLayer: L.layerGroup()
    }
  },
  mounted () {
    this.initializeMap()
  },
  watch: {
    'sharedState.signalements.list': function (newList) {
      this.addMarkers(newList)
      this.configurePopups()
      this.adjustMapBounds()
    },
    'sharedState.signalements.zoneAreas': function (newZones) {
      this.addZones(newZones)
    }
  },
  methods: {
    initializeMap () {
      this.map = L.map('map-signalements-view', {
        center: this.defaultCenter,
        maxBounds: this.bounds,
        minZoom: 2,
        maxZoom: 18,
        zoom: this.defaultZoom
      })
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { crossOrigin: true }).addTo(this.map as unknown as L.Map)
    },
    addMarkers (signalements: any[]) {
      this.markers.clearLayers()
      signalements.forEach(signalement => {
        if (!isNaN(parseFloat(signalement.geoloc?.lng)) && !isNaN(parseFloat(signalement.geoloc?.lat))) {
          const signalementOptions: SignalementMarkerOptions = {
            id: signalement.id,
            status: signalement.statut,
            address: signalement.adresseOccupant,
            zip: signalement.cpOccupant,
            city: signalement.villeOccupant,
            reference: signalement.reference,
            score: signalement.score,
            name: `${signalement.nomOccupant ? signalement.nomOccupant.toUpperCase() : ''} ${signalement.prenomOccupant}`,
            url: `/bo/signalements/${signalement.uuid}`,
            details: signalement.details
          }
          const marker = L.marker([signalement.geoloc.lat, signalement.geoloc.lng], {
            ...signalementOptions,
            icon: this.createCustomIcon(signalement.statut)
          })
          this.markers.addLayer(marker)
        }
      })
      this.map?.addLayer(this.markers as unknown as L.Layer)
    },
    addZones(zones: string[]) {
      this.zonesLayer.clearLayers()
      zones.forEach((wkt: string, index: number) => {
        const color = this.getZoneColor(index)
        // @ts-ignore
        const zone = L.geoJson(parse(wkt))
        zone.setStyle({
          color: color,
          fillColor: color,
          fillOpacity: 0.2,
          weight: 2
        })
        this.zonesLayer.addLayer(zone)
      })
      this.map?.addLayer(this.zonesLayer as unknown as L.Layer)
    },
    getZoneColor(index: number): string {
      const colors = ['#3388ff', '#ff3333', '#33cc33', '#ff9900', '#9966ff', '#ff33cc', '#00cccc', '#ffcc00', '#cc6600', '#999999']
      return colors[index % colors.length]
    },
    getMarkerColor(status: string): string {
      switch (status) {
        case 'NEED_VALIDATION':
          return '#b34000' // orange
        case 'ACTIVE':
          return '#18753c' // vert
        case 'CLOSED':
          return '#3a3a3a' // gris foncé
        default:
          return '#DDDDDD' // blanc
      }
    },
    getIconImage(status: string): string {
      switch (status) {
        case 'ACTIVE':
          return '/build/dsfr/icons/system/check-line.svg'
        case 'NEED_VALIDATION':
          return '/build/dsfr/icons/system/fr--warning-line.svg'
        case 'CLOSED':
          return '/build/dsfr/icons/system/close-line.svg'
        default:
          return '' // Pas d'icône par défaut
      }
    },
    createCustomIcon(status: string): L.DivIcon {
      const color = this.getMarkerColor(status)
      const iconImage = this.getIconImage(status)
      const iconHtml = iconImage
        ? `<img src="${iconImage}" alt="" style="position: absolute; top: 7px; left: 7px; width: 11px; height: 11px; filter: brightness(0) invert(1);" />`
        : `<circle fill="white" cx="12.5" cy="12.5" r="5"/>`

      return L.divIcon({
        className: 'custom-marker',
        html: `<svg width="25" height="41" viewBox="0 0 25 41" xmlns="http://www.w3.org/2000/svg">
          <path fill="${color}" stroke="white" stroke-width="1.5" d="M12.5 0C5.6 0 0 5.6 0 12.5c0 8.4 12.5 28.5 12.5 28.5S25 20.9 25 12.5C25 5.6 19.4 0 12.5 0z"/>
          ${iconImage ? '' : iconHtml}
        </svg>${iconImage ? iconHtml : ''}`,
        iconSize: [25, 41],
        iconAnchor: [12.5, 41],
        popupAnchor: [0, -41]
      })
    },
    configurePopups () {
      this.markers.getLayers().forEach((layer: any) => {
        const markerLayer = layer as L.Marker
        const markerOptions = markerLayer.options as L.MarkerOptions as SignalementMarkerOptions
        const type = this.getBadgeType(markerOptions.status)
        const popupContent = this.getPopupTemplate(markerLayer.options, type)
        markerLayer.bindPopup(popupContent, { maxWidth: 500 }).on('popupopen', (event: any) => {
          const px = this.map?.project(event.target._popup._latlng)
          if (px && this.map) {
            px.y -= event.target._popup._container.clientHeight / 2
            this.map.panTo(this.map.unproject(px), { animate: true })
          }
        })
      })
    },
    adjustMapBounds () {
      this.map?.invalidateSize();
      if (this.sharedState.signalements.list.length === 0){
        this.map?.setView(this.defaultCenter, this.defaultZoom)
      }else{
        const bounds = this.markers.getBounds()
        if (Object.keys(bounds).length !== 0) {
          this.map?.fitBounds(bounds)
        }
      }
    },
    getBadgeType (status: string) {
      switch (status) {
        case 'NEED_VALIDATION':
          return 'warning'
        case 'ACTIVE':
          return 'success'
        case 'CLOSED':
          return 'error'
        default:
          return 'neutral'
      }
    },
    getPopupTemplate (options: any, type: string) {
      return `
        <div class="fr-grid-row" style="width: 500px">
        <div class="fr-col-8">
            <a href="${options.url}" class="fr-badge fr-badge--${type} fr-mt-1v fr-mb-0">#${options.reference}</a>
            <p><strong>${options.name}</strong><br>
            <small>
                ${options.address} <br>
                ${options.zip} ${options.city}
            </small>
            </p>
        </div>
        <div class="fr-col-4 fr-mt-1v fr-mb-0 fr-text--center">
            <span class="fr-badge fr-badge--info fr-m-0">${parseFloat(options.score).toFixed(2)}%</span>
        </div>
        <div class="fr-p-3v fr-rounded fr-background-alt--blue-france fr-col-12">${options.details}</div>
        </div>`
    }
  }
})
</script>

<style scoped>
  .map-container {
    position: relative;
    width: 100%;
    height: 100%;
  }
  .map-wrapper {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .no-signalements-message {
    position: absolute;
    z-index: 1000;
    background-color: rgba(255, 255, 255, 0.9);
    color: #000;
    padding: 1rem 2rem;
    border-radius: 8px;
    text-align: center;
  }
</style>

<style>
  .custom-marker {
    background: none;
    border: none;
  }
</style>
