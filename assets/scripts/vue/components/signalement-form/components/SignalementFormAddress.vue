<template>
  <div :class="[customCss, 'signalement-form-address']" :id="id">
    <SignalementFormTextfield
      :key="idAddress"
      :id="idAddress"
      :label="label"
      :description="description"
      placeholder="Taper l'adresse ici"
      :validate="validateWithMaxLength"
      v-model="formStore.data[idAddress]"
      :hasError="hasError"
      :error="error"
      access_name="address"
      access_autocomplete="address-line1"
      @keydown.down.prevent="handleDownSuggestion"
      @keydown.up.prevent="handleUpSuggestion"
      @keydown.enter.prevent="handleEnterSuggestion"
      @keydown.escape.prevent="handleTabSuggestion"
      @keydown.tab="handleTabSuggestion"
    />

    <div class="fr-grid-row fr-background-alt--blue-france fr-text-label--blue-france fr-address-group">
      <div
        v-for="(suggestion, index) in suggestions"
        :key="index"
        :class="['fr-col-12 fr-p-3v fr-text-label--blue-france fr-address-suggestion', { 'fr-autocomplete-suggestion-highlighted': index === selectedSuggestionIndex }]"
        tabindex="0"
        @click="handleClickSuggestion(index)"
        >
        {{ suggestion.properties.label }}
      </div>
    </div>

    <SignalementFormButton
      :key="idShow"
      :id="idShow"
      label="Saisir une adresse manuellement"
      :customCss="buttonCss + ' btn-link fr-btn--icon-left fr-icon-edit-line'"
      :aria-hidden="buttonCss == 'fr-hidden' ? true : undefined"
      :hidden="buttonCss == 'fr-hidden' ? true : undefined"
      :action="actionShow"
      :clickEvent="handleClickButton"
    />

    <SignalementFormSubscreen
      :key="idSubscreen"
      :id="idSubscreen"
      label=""
      :customCss="subscreenCss + ' fr-mt-3v'"
      :aria-hidden="subscreenCss == 'fr-hidden' ? true : undefined"
      :hidden="subscreenCss == 'fr-hidden' ? true : undefined"
      :components="screens"
      v-model="formStore.data[idSubscreen]"
      :hasError="formStore.validationErrors[idSubscreen] !== undefined"
      :error="formStore.validationErrors[idSubscreen]"
      @update:modelValue="handleSubscreenModelUpdate"
    />

    <button
      class="fr-btn fr-btn--icon-left fr-btn--secondary fr-icon-map-pin-2-line"
      @click="togglePickLocation"
      v-if="displayPickLocationButton">
        Sélectionner le bâtiment sur la carte
    </button>

    <div v-if="showPickLocation" class="pick-location-container fr-mt-3v">
      <div class="pick-location-header">
        <h3>Sélectionner le bâtiment correspondant au logement</h3>
        <button type="button" class="fr-btn fr-btn--tertiary-no-outline fr-icon-close-line" @click="closePickLocation">
          Fermer
        </button>
      </div>
      <div ref="pickLocationMessage" class="fr-hidden fr-mb-2v">Chargement en cours</div>
      <div :key="mapKey" ref="pickLocationMapContainer" :id="idPickLocationMap" class="pick-location-map"></div>
      <div class="pick-location-footer fr-mt-3v">
        <button
          class="fr-btn fr-icon-check-line"
          id="fr-modal-pick-localisation-submit"
          :disabled="!selectedRnbId"
          @click="handleSubmitPickLocation"
          type="button">
          Valider
        </button>
        <button
          type="button"
          class="fr-btn fr-btn--secondary fr-icon-close-line"
          @click="closePickLocation">
          Annuler
        </button>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, watch, onMounted, onBeforeUnmount } from 'vue'
import formStore from './../store'
import { requests } from './../requests'
import { variableTester } from '../../../utils/variableTester'
import { subscreenManager } from './../services/subscreenManager'
import subscreenData from './../address_subscreen.json'
import SignalementFormTextfield from './SignalementFormTextfield.vue'
import SignalementFormButton from './SignalementFormButton.vue'
import SignalementFormSubscreen from './SignalementFormSubscreen.vue'
import L from 'leaflet'
import 'leaflet.vectorgrid'

// Import des fichiers CSS nécessaires pour Leaflet
import 'leaflet/dist/leaflet.css'
import 'leaflet.markercluster/dist/MarkerCluster.css'
import 'leaflet.markercluster/dist/MarkerCluster.Default.css'

export default defineComponent({
  name: 'SignalementFormAddress',
  components: {
    SignalementFormTextfield,
    SignalementFormButton,
    SignalementFormSubscreen
  },
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    description: { type: String, default: null },
    modelValue: { type: String, default: null },
    customCss: { type: String, default: '' },
    validate: { type: Object, default: null },
    hasError: { type: Boolean, default: false },
    error: { type: String, default: '' },
    clickEvent: Function,
    // les propriétés suivantes ne sont pas utilisées,
    // mais si on ne les met pas, elles apparaissent dans le DOM
    // et ça soulève des erreurs W3C
    components: Object,
    handleClickComponent: Function,
    access_name: { type: String, default: undefined },
    access_autocomplete: { type: String, default: undefined },
    access_focus: { type: Boolean, default: false }
  },
  data () {
    const updatedSubscreenData = subscreenManager.generateSubscreenData(this.id, subscreenData.body, this.validate)
    // on met à jour formStore en ajoutant les sous-composants du composant Address
    subscreenManager.addSubscreenData(this.id, updatedSubscreenData)
    return {
      idFetchTimeout: 0 as unknown as ReturnType<typeof setTimeout>,
      isTyping: false,
      idAddress: this.id + '_suggestion',
      idShow: this.id + '_afficher_les_champs',
      idSubscreen: this.id + '_detail',
      idPickLocationMap: this.id + '_pick_location_map',
      actionShow: 'show:' + this.id + '_detail',
      screens: { body: updatedSubscreenData },
      suggestions: [] as any[],
      canPickLocation: this.customCss.includes('can-pick-location'),
      displayPickLocationButton: false,
      showPickLocation: false,
      formStore,
      // Avoids searching when an option is selected in the list
      isSearchSkipped: false,
      selectedSuggestionIndex: -1,
      map: null as any,
      vectorTileLayer: null as any,
      previousRnbId: undefined as string | undefined,
      selectedRnbId: null as string | null,
      mapKey: 0
    }
  },
  created () {
    watch(
      () => this.formStore.data[this.idAddress],
      (newValue: any) => {
        if (this.isSearchSkipped) {
          return
        }
        clearTimeout(this.idFetchTimeout)
        this.isTyping = true
        this.idFetchTimeout = setTimeout(() => {
          this.isTyping = false
          this.selectedSuggestionIndex = -1
          if (newValue.length > 10) {
            const codePostal = this.idAddress === 'adresse_logement_adresse_suggestion'
              ? ' ' + this.getCodePostalFromQueryParam()
              : ''
            requests.validateAddress(newValue + codePostal, this.handleAddressFound)
          }
        }, 200)
      }
    )
    watch(
      () => this.formStore.data[this.id + '_detail_numero'],
      async () => {
        this.handleAddressFieldsEdited(false)
      }
    )
    watch(
      () => this.formStore.data[this.id + '_detail_code_postal'],
      async () => {
        this.handleAddressFieldsEdited(true)
      }
    )
    watch(
      () => this.formStore.data[this.id + '_detail_commune'],
      async () => {
        this.handleAddressFieldsEdited(true)
      }
    )
  },
  beforeUnmount () {
    if (this.map) {
      this.map.remove()
    }
  },
  computed: {
    buttonCss () {
      if (
        this.formStore.data[this.idShow] ||
          this.formStore.data[this.id + '_detail_numero'] ||
          this.formStore.data[this.id + '_detail_code_postal'] ||
          this.formStore.data[this.id + '_detail_commune']
      ) {
        return 'fr-hidden'
      }
      return ''
    },
    subscreenCss () {
      if (this.buttonCss === '' &&
        variableTester.isEmpty(this.formStore.validationErrors[this.id + '_detail_numero']) &&
        variableTester.isEmpty(this.formStore.validationErrors[this.id + '_detail_code_postal']) &&
        variableTester.isEmpty(this.formStore.validationErrors[this.id + '_detail_commune'])
      ) {
        return 'fr-hidden'
      }
      return ''
    },
    validateWithMaxLength () {
      return {
        ...this.validate,
        maxLength: 200
      }
    }
  },
  methods: {
    updateValue (value: any) {
      this.$emit('update:modelValue', value)
    },
    handleAddressFieldsEdited (changeCommune:Boolean) {
      this.formStore.data[this.id + '_detail_manual'] = 1
      this.displayPickLocationButton = false
      if (changeCommune) {
        formStore.data[this.id + '_detail_need_refresh_insee'] = true
      }
      if (
        variableTester.isEmpty(this.formStore.data[this.id + '_detail_numero']) &&
        variableTester.isEmpty(this.formStore.data[this.id + '_detail_code_postal']) &&
        variableTester.isEmpty(this.formStore.data[this.id + '_detail_commune'])
      ) {
        if (this.validate !== null && this.validate.required === false) {
          this.formStore.data[this.id + '_detail_manual'] = 0
        }
      }
      if (
        !variableTester.isEmpty(this.formStore.data[this.id + '_detail_numero']) &&
        !variableTester.isEmpty(this.formStore.data[this.id + '_detail_code_postal']) &&
        !variableTester.isEmpty(this.formStore.data[this.id + '_detail_commune'])
      ) {
        this.displayPickLocationButton = this.canPickLocation
      }
    },
    handleClickButton (type:string, param:string, slugButton:string) {
      this.formStore.data[this.idShow] = 1
      if (this.clickEvent !== undefined) {
        this.clickEvent(type, param, slugButton)
      }
    },
    handleSubscreenModelUpdate (newValue: string) {
      // Mettre à jour la valeur dans formStore.data lorsque la valeur du sous-écran change
      this.formStore.data[this.idSubscreen] = newValue
    },
    handleClickSuggestion (index: number) {
      this.isSearchSkipped = true
      if (this.suggestions) {
        this.selectedSuggestionIndex = index
        this.formStore.data[this.id + '_detail_need_refresh_insee'] = false
        this.formStore.data[this.idAddress] = this.suggestions[index].properties.label
        this.formStore.data[this.id] = this.suggestions[index].properties.label
        this.formStore.data[this.id + '_detail_numero'] = this.suggestions[index].properties.name
        this.formStore.data[this.id + '_detail_code_postal'] = this.suggestions[index].properties.postcode
        this.formStore.data[this.id + '_detail_commune'] = this.suggestions[index].properties.city
        this.formStore.data[this.id + '_detail_insee'] = this.suggestions[index].properties.citycode
        this.formStore.data[this.id + '_detail_manual'] = 0
        this.suggestions.length = 0
        setTimeout(() => {
          this.isSearchSkipped = false
        }, 200)
      }
    },
    handleDownSuggestion () {
      if (this.selectedSuggestionIndex < this.suggestions.length - 1) {
        this.selectedSuggestionIndex++
      }
    },
    handleUpSuggestion () {
      if (this.selectedSuggestionIndex > 0) {
        this.selectedSuggestionIndex--
      }
    },
    handleEnterSuggestion () {
      if (this.selectedSuggestionIndex !== -1) {
        this.handleClickSuggestion(this.selectedSuggestionIndex)
        this.selectedSuggestionIndex = -1
      }
    },
    handleTabSuggestion () {
      this.suggestions.length = 0
    },
    handleAddressFound (requestResponse: any) {
      this.suggestions = requestResponse.features
    },
    getCodePostalFromQueryParam () {
      const queryString = window.location.search
      const urlParams = new URLSearchParams(queryString)
      return urlParams.get('cp') || ''
    },
    togglePickLocation () {
      this.showPickLocation = !this.showPickLocation

      if (this.showPickLocation) {
        // Incrémenter la clé pour forcer Vue à recréer l'élément
        this.mapKey++

        // Attendre que le DOM soit mis à jour
        this.$nextTick(() => {
          this.initMap()
        })
      } else {
        // Détruire la carte quand on ferme
        if (this.map) {
          this.map.remove()
          this.map = null
          this.vectorTileLayer = null
        }
      }
    },
    closePickLocation () {
      this.showPickLocation = false
      this.selectedRnbId = null
      // La carte sera détruite dans togglePickLocation
    },
    initMap () {
      // Détruire la carte existante si elle existe
      if (this.map) {
        this.map.remove()
        this.map = null
        this.vectorTileLayer = null
      }

      // Géocoder l'adresse d'abord pour obtenir les coordonnées
      const apiAdresse = 'https://data.geopf.fr/geocodage/search/?q='
      const address = this.formStore.data[this.id + '_detail_numero'] + ' ' + this.formStore.data[this.id + '_detail_commune']
      const postCode = this.formStore.data[this.id + '_detail_code_postal']

      fetch(apiAdresse + address + '&postcode=' + postCode)
        .then((response) => response.json())
        .then((json) => {
          let center: [number, number] = [48.8566, 2.3522] // Paris par défaut
          let zoom = 13

          if (json.features && json.features.length > 0) {
            center = [json.features[0].geometry.coordinates[1], json.features[0].geometry.coordinates[0]]
            zoom = 18
          }

          // Pas de setTimeout, initialisation directe
          this.$nextTick(() => {
            const container = this.$refs.pickLocationMapContainer as HTMLElement
            if (!container) {
              return
            }

            // Initialiser la carte
            this.map = L.map(container, {
              center: center,
              zoom: zoom,
              scrollWheelZoom: true,
              zoomControl: true
            })

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
              maxZoom: 19,
              attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(this.map)

            // Initialiser la couche vectorielle RNB
            this.map.whenReady(() => {
              this.initVectorTileLayer()
            })
          })
        })
        .catch((error) => {
          console.error('Geocoding error:', error)
          // Initialiser avec Paris en cas d'erreur
          this.$nextTick(() => {
            const container = this.$refs.pickLocationMapContainer as HTMLElement
            if (!container) {
              return
            }

            this.map = L.map(container, {
              center: [48.8566, 2.3522],
              zoom: 13,
              scrollWheelZoom: true,
              zoomControl: true
            })

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
              maxZoom: 19,
              attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(this.map)
          })
        })
    },
    initVectorTileLayer () {
      // Patch pour rendre les couches vectorielles interactives
      // @ts-ignore - Extension Leaflet pour VectorGrid
      if ((L as any).Canvas && (L as any).Canvas.Tile) {
        (L as any).Canvas.Tile.include({
          _onClick: function (e: any) {
            const point = this._map.mouseEventToLayerPoint(e).subtract(this.getOffset())
            let layer: any = null
            let clickedLayer: any = null

            for (const id in this._layers) {
              layer = this._layers[id]
              if (
                layer.options.interactive &&
                layer._containsPoint(point) &&
                !this._map._draggableMoved(layer)
              ) {
                clickedLayer = layer
              }
            }
            if (clickedLayer) {
              if (typeof clickedLayer === 'object' && clickedLayer !== null) {
                clickedLayer.fireEvent(e.type, undefined, true)
              }
            }
          }
        })
      }

      const vectorTileOptions = {
        // @ts-ignore - Propriété spécifique à VectorGrid
        rendererFactory: (L.canvas as any).tile,
        vectorTileLayerStyles: {
          default: this.getInitialBuildingStyle()
        },
        interactive: true,
        getFeatureId: function (f: any) {
          return f.properties.rnb_id
        }
      }

      // @ts-ignore - Extension Leaflet.VectorGrid
      this.vectorTileLayer = (L as any).vectorGrid.protobuf(
        'https://rnb-api.beta.gouv.fr/api/alpha/tiles/{x}/{y}/{z}.pbf',
        vectorTileOptions
      )

      this.vectorTileLayer.addTo(this.map)

      // Gérer les clics sur les bâtiments
      this.vectorTileLayer.on('click', (e: any) => {
        const properties = e.layer.properties
        const rnbId = properties.rnb_id

        if (this.previousRnbId !== undefined) {
          this.vectorTileLayer.setFeatureStyle(this.previousRnbId, this.getInitialBuildingStyle())
        }

        this.vectorTileLayer.setFeatureStyle(rnbId, this.getClickedBuildingStyle())
        this.previousRnbId = rnbId

        // Stocker le RNB ID
        this.selectedRnbId = rnbId
      })
    },
    getInitialBuildingStyle () {
      return {
        radius: 5,
        fillColor: '#1452e3',
        color: '#ffffff',
        weight: 3,
        fill: true,
        fillOpacity: 1,
        opacity: 1
      }
    },
    getClickedBuildingStyle () {
      return {
        radius: 5,
        fillColor: '#31e060',
        color: '#ffffff',
        weight: 3,
        fill: true,
        fillOpacity: 1,
        opacity: 1
      }
    },
    handleSubmitPickLocation () {
      if (this.selectedRnbId) {
        // Stocker le RNB ID dans formStore
        this.formStore.data[this.id + '_detail_rnb_id'] = this.selectedRnbId
        // Fermer le sélecteur
        this.closePickLocation()
      }
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
.fr-address-suggestion:hover {
  background-color: #417dc4;
  color: white !important;
}
.fr-address-group {
  margin-top: -1.5rem;
}
.signalement-form-address .signalement-form-button {
  width: 100%;
  text-align: center;
}

.pick-location-container {
  border: 1px solid #ddd;
  padding: 1.5rem;
  border-radius: 0.25rem;
  background-color: #f6f6f6;
}

.pick-location-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.pick-location-header h3 {
  margin: 0;
  font-size: 1.25rem;
}

.pick-location-map {
  height: 500px;
  width: 100%;
  overflow: hidden;
}

/* Forcer les styles Leaflet pour éviter les interférences CSS - spécificité max */
.pick-location-map.leaflet-container {
  position: relative !important;
}

.pick-location-map .leaflet-pane.leaflet-map-pane, .pick-location-map .leaflet-pane.leaflet-tile-pane {
  position: absolute !important;
  left: 0 !important;
  top: 0 !important;
}

.pick-location-map .leaflet-tile-container {
  position: absolute !important;
}

.pick-location-footer {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}
</style>
