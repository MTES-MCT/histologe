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
import omnivore from '@mapbox/leaflet-omnivore'

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
          const marker = L.marker([signalement.geoloc.lat, signalement.geoloc.lng], signalementOptions)
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
        const zone = omnivore.wkt.parse(wkt)
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
        case '1':
          return 'info'
        case '2':
          return 'success'
        case '6':
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
