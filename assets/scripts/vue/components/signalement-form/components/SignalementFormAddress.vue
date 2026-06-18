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

    <div v-if="displayPickLocationButton" class="pick-location-button-container">
      <button
        class="fr-btn fr-btn--icon-left fr-btn--secondary fr-icon-map-pin-2-line"
        @click="togglePickLocation">
          Sélectionner le bâtiment sur la carte
      </button>
      <span v-if="formStore.data[id + '_detail_rnb_id']" class="pick-location-success fr-ml-2v">
        <span class="fr-icon-check-line" aria-hidden="true"></span>
        Bâtiment sélectionné
      </span>
    </div>

    <div v-if="showPickLocation" class="pick-location-container fr-mt-3v">
      <div class="pick-location-header">
        <h3>Sélectionner le bâtiment correspondant au logement</h3>
        <button type="button" class="fr-btn fr-btn--tertiary-no-outline fr-icon-close-line" @click="closePickLocation">
          Fermer
        </button>
      </div>
      <div ref="pickLocationMessage" class="fr-hidden fr-mb-2v">Chargement en cours</div>
      <div :key="mapKey" ref="pickLocationMapContainer" :id="idPickLocationMap" class="pick-location-map"></div>
      <div ref="pickLocationAnnouncement" aria-live="polite" aria-atomic="true" class="fr-sr-only"></div>
      <div class="pick-location-footer fr-mt-3v">
        <button
          ref="pickLocationSubmit"
          class="fr-btn fr-icon-check-line"
          :id="id + '_pick_location_submit'"
          :disabled="!selectedRnbId"
          :title="selectedRnbId ? 'Valider la sélection du bâtiment' : 'Veuillez sélectionner un bâtiment sur la carte'"
          @click="handleSubmitPickLocation"
          type="button">
          Valider la sélection
        </button>
        <button
          type="button"
          class="fr-btn fr-btn--secondary fr-icon-close-line"
          title="Fermer sans enregistrer"
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
import { buildingStyles, createRnbMapController } from '../../../../vanilla/services/component/rnb-map-controller.js'

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
      rnbMapController: null as any,
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
        if (this.isSearchSkipped) {
          return
        }
        this.handleAddressFieldsEdited(false)
      }
    )
    watch(
      () => this.formStore.data[this.id + '_detail_code_postal'],
      async () => {
        if (this.isSearchSkipped) {
          return
        }
        this.handleAddressFieldsEdited(true)
      }
    )
    watch(
      () => this.formStore.data[this.id + '_detail_commune'],
      async () => {
        if (this.isSearchSkipped) {
          return
        }
        this.handleAddressFieldsEdited(true)
      }
    )
  },
  beforeUnmount () {
    if (this.rnbMapController) {
      this.rnbMapController.destroy()
    }
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
        // Conserver previousRnbId pour restaurer la sélection à la réouverture
        this.selectedRnbId = this.previousRnbId || null
        delete this.formStore.data[this.id + '_detail_rnb_id']

        // Incrémenter la clé pour forcer Vue à recréer l'élément
        this.mapKey++

        this.$nextTick(() => {
          this.initMap()
        })
      }
    },
    closePickLocation () {
      if (this.rnbMapController) {
        this.rnbMapController.destroy()
        this.rnbMapController = null
      }
      this.showPickLocation = false
    },
    initMap () {
      if (this.map) {
        this.map.remove()
        this.map = null
        this.vectorTileLayer = null
      }

      // Géocoder l'adresse pour centrer la carte
      const apiAdresse = 'https://data.geopf.fr/geocodage/search/?q='
      const address = this.formStore.data[this.id + '_detail_numero'] + ' ' + this.formStore.data[this.id + '_detail_commune']
      const postCode = this.formStore.data[this.id + '_detail_code_postal']

      fetch(apiAdresse + address + '&postcode=' + postCode)
        .then((response) => response.json())
        .then((json) => {
          if (json.features && json.features.length > 0) {
            this.setupMap([json.features[0].geometry.coordinates[1], json.features[0].geometry.coordinates[0]], 18)
          } else {
            this.setupMap([48.8566, 2.3522], 13)
          }
        })
        .catch(() => {
          this.setupMap([48.8566, 2.3522], 13)
        })
    },
    setupMap (center: [number, number], zoom: number) {
      this.$nextTick(() => {
        const container = this.$refs.pickLocationMapContainer as HTMLElement
        if (!container) return

        // Supprimer l'attribut _leaflet_id du conteneur si présent
        if ((container as any)._leaflet_id) {
          delete (container as any)._leaflet_id
        }

        // keyboard: false désactive la navigation clavier native de Leaflet
        // pour laisser le contrôleur gérer la navigation entre bâtiments
        this.map = L.map(container, {
          center,
          zoom,
          scrollWheelZoom: true,
          zoomControl: true,
          keyboard: false,
        })

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
          referrerPolicy: 'origin',
        }).addTo(this.map)

        // @ts-ignore - Extension Leaflet.VectorGrid
        this.vectorTileLayer = (L as any).vectorGrid.protobuf(
          'https://rnb-api.beta.gouv.fr/api/alpha/tiles/{x}/{y}/{z}.pbf',
          {
            // @ts-ignore - Propriété spécifique à VectorGrid
            rendererFactory: (L.canvas as any).tile,
            vectorTileLayerStyles: { default: buildingStyles.initial },
            interactive: true,
            getFeatureId: (f: any) => f.properties.rnb_id,
          }
        )
        this.vectorTileLayer.addTo(this.map)

        // Forcer la carte à recalculer ses dimensions, puis initialiser le contrôleur
        setTimeout(() => {
          this.map.invalidateSize()

          const mapContainer = this.$refs.pickLocationMapContainer as HTMLElement
          const announcement = this.$refs.pickLocationAnnouncement as HTMLElement

          // Restaurer la sélection précédente si elle existe
          if (this.previousRnbId) {
            this.selectedRnbId = this.previousRnbId
          }

          this.rnbMapController = createRnbMapController({
            mapContainer,
            map: this.map,
            vectorTileLayer: this.vectorTileLayer,
            previousRnbId: this.previousRnbId,
            onSelect: (rnbId: string) => {
              this.selectedRnbId = rnbId
              this.previousRnbId = rnbId
            },
            onAnnounce: (text: string) => {
              if (announcement) announcement.textContent = text
            },
            onFocusSubmit: () => {
              const btn = this.$refs.pickLocationSubmit as HTMLElement
              if (btn) btn.focus()
            },
          })
        }, 100)
      })
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

.pick-location-button-container {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.pick-location-success {
  color: #18753c;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
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
