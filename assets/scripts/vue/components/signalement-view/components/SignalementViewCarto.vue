<template>
    <div id="map-signalements-view" class="map-container"></div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from '../store'
import * as L from 'leaflet'
import 'leaflet.markercluster'
import { SignalementMarkerOptions } from '../interfaces/signalementMarkerOptions'

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
      offset: 0,
      bounds: L.latLngBounds(L.latLng(8, -80), L.latLng(70, 20))
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
    }
  },
  methods: {
    initializeMap () {
      console.log('initializeMap')
      this.map = L.map('map-signalements-view', {
        center: [47.11, -0.01],
        maxBounds: this.bounds,
        minZoom: 5,
        maxZoom: 18,
        zoom: 5
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
          console.log(signalement.id+' : '+signalement.geoloc.lat+'/'+signalement.geoloc.lng)
          this.markers.addLayer(marker)
        }
      })
      this.map?.addLayer(this.markers as unknown as L.Layer)
      console.log('addMarkers')
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
      console.log('adjustMapBounds')
      const bounds = this.markers.getBounds()
      console.log(bounds)
      if (Object.keys(bounds).length !== 0) {
        this.map?.fitBounds([
          [bounds.getNorthEast().lat, bounds.getNorthEast().lng],
          [bounds.getSouthWest().lat, bounds.getSouthWest().lng]
        ])
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
    width: 100%;
    height: 100vh;
  }
</style>
