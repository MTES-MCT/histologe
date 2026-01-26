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
      data-fr-opened="false" 
      :aria-controls="idModalPickLocation"
      v-if="displayPickLocationButton">
        Sélectionner le bâtiment sur la carte
    </button>

    <dialog
      aria-labelledby="fr-modal-pick-localisation-title"
      :id="idModalPickLocation"
      class="fr-modal"
      :data-address="formStore.data[id + '_detail_numero'] + ' ' + formStore.data[id + '_detail_commune']"
      :data-postcode="formStore.data[id + '_detail_code_postal']"
      data-loaded="false"
      >
      <div class="fr-container fr-container--fluid fr-container-md">
          <div class="fr-grid-row fr-grid-row--center">
              <div class="fr-col-12 fr-col-md-8 fr-col-lg-6">
                  <div class="fr-modal__body">
                      <div class="fr-modal__header">
                        <button type="button" class="fr-btn--close fr-btn" :aria-controls="idModalPickLocation">Fermer</button>
                      </div>
                      <div class="fr-modal__content">
                          <h1 class="fr-modal__title">Sélectionner le bâtiment correspondant au logement</h1>
                          <div id="fr-modal-pick-localisation-message" class="fr-hidden">Chargement en cours</div>
                          <div id="fr-modal-pick-localisation-map"></div>
                      </div>
                      <div class="fr-modal__footer">
                          <ul class="fr-btns-group fr-btns-group--right fr-btns-group--inline-reverse fr-btns-group--inline-lg fr-btns-group--icon-left">
                              <li>
                                  <button class="fr-btn fr-icon-check-line" id="fr-modal-pick-localisation-submit" disabled type="button">
                                      Valider
                                  </button>
                              </li>
                              <li>
                                  <button type="button" class="fr-btn fr-btn--secondary fr-icon-close-line" :aria-controls="idModalPickLocation">
                                      Annuler
                                  </button>
                              </li>
                          </ul>
                      </div>
                  </div>
              </div>
          </div>
      </div>
    </dialog>
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
      idModalPickLocation: this.id + '_modal_pick_localisation',
      actionShow: 'show:' + this.id + '_detail',
      screens: { body: updatedSubscreenData },
      suggestions: [] as any[],
      canPickLocation: this.customCss.includes('can-pick-location'),
      displayPickLocationButton: false,
      formStore,
      // Avoids searching when an option is selected in the list
      isSearchSkipped: false,
      selectedSuggestionIndex: -1,
      map: null as any,
      vectorTileLayer: null as any,
      previousRnbId: undefined as string | undefined,
      handleModalDisclose: null as any
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
  mounted () {
    this.initPickLocationModal()
  },
  beforeUnmount () {
    const modalElement = document.getElementById(this.idModalPickLocation)
    if (modalElement && this.handleModalDisclose) {
      modalElement.removeEventListener('dsfr.disclose', this.handleModalDisclose)
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
    initPickLocationModal () {
      if (!this.canPickLocation) {
        return
      }

      const modalElement = document.getElementById(this.idModalPickLocation)
      if (!modalElement) {
        return
      }

      // Gérer l'événement d'ouverture de la modale
      this.handleModalDisclose = () => {
        console.log('Modal disclose event triggered', modalElement.dataset.loaded)
        if (modalElement.dataset.loaded === 'false') {
          modalElement.dataset.loaded = 'true'

          // Attendre que la modale soit vraiment affichée
          setTimeout(() => {
            if (!this.map) {
              console.log('Initializing map...')

              // Initialiser la carte
              this.map = L.map('fr-modal-pick-localisation-map')
              L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
              }).addTo(this.map)

              console.log('Initializing vector tile layer...')
              // Initialiser la couche vectorielle
              this.initVectorTileLayer()
            }

            console.log('Geocoding address...')
            // Géocoder l'adresse
            this.geocodeAddress()
          }, 100)
        }
      }

      modalElement.addEventListener('dsfr.disclose', this.handleModalDisclose)

      // Gérer le bouton de validation
      const submitButton = document.getElementById('fr-modal-pick-localisation-submit')
      if (submitButton) {
        submitButton.addEventListener('click', () => {
          this.handleSubmitPickLocation()
        })
      }
    },
    initVectorTileLayer () {
      console.log('VectorGrid available?', (L as any).vectorGrid)
      console.log('Canvas.tile available?', (L.canvas as any).tile)

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

      console.log('Creating vector tile layer with options:', vectorTileOptions)

      // @ts-ignore - Extension Leaflet.VectorGrid
      this.vectorTileLayer = (L as any).vectorGrid.protobuf(
        'https://rnb-api.beta.gouv.fr/api/alpha/tiles/{x}/{y}/{z}.pbf',
        vectorTileOptions
      )

      console.log('Vector tile layer created:', this.vectorTileLayer)
      this.vectorTileLayer.addTo(this.map)
      console.log('Vector tile layer added to map')

      // Gérer les clics sur les bâtiments
      this.vectorTileLayer.on('click', (e: any) => {
        console.log('Building clicked:', e.layer.properties)
        const properties = e.layer.properties
        const rnbId = properties.rnb_id

        if (this.previousRnbId !== undefined) {
          this.vectorTileLayer.setFeatureStyle(this.previousRnbId, this.getInitialBuildingStyle())
        }

        this.vectorTileLayer.setFeatureStyle(rnbId, this.getClickedBuildingStyle())
        this.previousRnbId = rnbId

        // Stocker le RNB ID dans formStore
        this.formStore.data[this.id + '_detail_rnb_id'] = rnbId

        // Activer le bouton de validation
        const submitButton = document.getElementById('fr-modal-pick-localisation-submit') as HTMLButtonElement
        if (submitButton) {
          submitButton.disabled = false
        }
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
    geocodeAddress () {
      const apiAdresse = 'https://data.geopf.fr/geocodage/search/?q='
      const address = this.formStore.data[this.id + '_detail_numero'] + ' ' + this.formStore.data[this.id + '_detail_commune']
      const postCode = this.formStore.data[this.id + '_detail_code_postal']
      const messageElement = document.getElementById('fr-modal-pick-localisation-message')
      const mapElement = document.getElementById('fr-modal-pick-localisation-map')

      fetch(apiAdresse + address + '&postcode=' + postCode)
        .then((response) => response.json())
        .then((json) => {
          if (!json.features || json.features.length === 0) {
            if (messageElement) {
              messageElement.innerText = 'Adresse introuvable, merci de préciser l\'adresse grâce au formulaire.'
              messageElement.classList.remove('fr-hidden')
            }
            if (mapElement) {
              mapElement.classList.add('fr-hidden')
            }
            return
          }

          this.map.setView(
            [json.features[0].geometry.coordinates[1], json.features[0].geometry.coordinates[0]],
            18
          )

          if (messageElement) {
            messageElement.classList.add('fr-hidden')
          }
        })
    },
    handleSubmitPickLocation () {
      // Fermer la modale (le DSFR s'en occupe via le bouton)
      // La valeur RNB ID est déjà stockée dans formStore
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
#fr-modal-pick-localisation-map {
  height: 500px;
  width: 100%;
  position: relative;
  z-index: 0;
}
</style>
