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
      @keydown.escape.prevent="handleEscapeSuggestion"
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

    <p v-if="formStore.addressCorrectionMessage" class="fr-text--sm" role="status">
      {{ formStore.addressCorrectionMessage }}
    </p>

    <div aria-live="polite" aria-atomic="true" class="fr-sr-only">{{ pickLocationAnnouncement }}</div>

    <div v-if="showPickLocation" class="pick-location-container fr-mt-3v">
      <div class="pick-location-header">
        <h3>{{ pickLocationHeading }}</h3>
      </div>
      <div>
        <button
          type="button"
          :class="['fr-btn fr-btn--icon-left fr-btn--tertiary-no-outline', { 'fr-icon-list-unordered': viewMode === 'map', 'fr-icon-map-pin-2-line': viewMode !== 'map' }]"
          @click="toggleViewMode">
          {{ viewMode === 'map' ? 'Afficher la liste' : 'Afficher la carte' }}
        </button>
      </div>

      <div v-show="viewMode === 'map'">
        <p>Cliquez sur un bâtiment pour le sélectionner, ou utilisez les touches fléchées puis Entrée.</p>
        <p v-if="isLoadingMap" class="fr-mb-2v" role="status">Chargement en cours…</p>
        <div :key="mapKey" ref="pickLocationMapContainer" :id="idPickLocationMap" class="pick-location-map"></div>
        <div ref="pickLocationAnnouncement" aria-live="polite" aria-atomic="true" class="fr-sr-only"></div>
      </div>

      <SignalementFormOnlyChoice
        v-show="viewMode === 'list'"
        :id="id + '_pick_location_list'"
        label="Choisissez le bâtiment correspondant au logement"
        :values="buildingChoices"
        v-model="selectedRnbIdModel"
      />
      <p v-if="viewMode === 'list' && buildingsList.length > maxListItems" class="fr-hint-text">
        Les {{ maxListItems }} bâtiments les plus proches sont affichés. Si le vôtre n'y figure pas, cochez "Je ne trouve pas mon bâtiment" ci-dessous.
      </p>

      <div class="fr-checkbox-group fr-checkbox-group--sm fr-mb-2v">
        <input
          type="checkbox"
          :id="id + '_no_building_found'"
          v-model="noBuildingFound"
        >
        <label class="fr-label" :for="id + '_no_building_found'">
          Je ne trouve pas mon bâtiment
        </label>
      </div>

      <div class="pick-location-footer fr-mt-3v">
        <span v-if="formStore.data[id + '_detail_rnb_id']" class="pick-location-success fr-ml-2v">
          <span class="fr-icon-check-line" aria-hidden="true"></span>
          Bâtiment sélectionné
        </span>
        <span v-else-if="formStore.data[id + '_detail_no_building_found']" class="pick-location-success fr-ml-2v">
          <span class="fr-icon-check-line" aria-hidden="true"></span>
          Bâtiment non trouvé, signalé
        </span>
        <button
          ref="pickLocationSubmit"
          class="fr-btn fr-icon-check-line"
          :id="id + '_pick_location_submit'"
          :disabled="!selectedRnbId && !noBuildingFound"
          :title="(selectedRnbId || noBuildingFound) ? 'Valider la sélection' : 'Veuillez sélectionner un bâtiment sur la carte'"
          @click="handleSubmitPickLocation"
          type="button">
          Valider la sélection
        </button>
      </div>
    </div>
    <div
      :id="id + '-_detail_rnb_id-error-desc-error'"
      class="fr-error-text"
      role="alert"
      v-if="formStore.validationErrors[id + '_detail_rnb_id'] !== undefined"
      >
      {{ formStore.validationErrors[id + '_detail_rnb_id'] }}
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, watch } from 'vue'
import formStore from './../store'
import { requests } from './../requests'
import { variableTester } from '../../common/utils/variableTester'
import { subscreenManager } from './../services/subscreenManager'
import subscreenData from './../address_subscreen.json'
import SignalementFormTextfield from './SignalementFormTextfield.vue'
import SignalementFormButton from './SignalementFormButton.vue'
import SignalementFormSubscreen from './SignalementFormSubscreen.vue'
import SignalementFormOnlyChoice from './SignalementFormOnlyChoice.vue'
import L from 'leaflet'
import 'leaflet.vectorgrid'
import { buildingStyles, createRnbMapController } from '../../../../vanilla/services/component/rnb-map-controller.js'
import { buildAddressCorrectionMessage } from '../services/addressCorrection'

// Import des fichiers CSS nécessaires pour Leaflet
import 'leaflet/dist/leaflet.css'
import 'leaflet.markercluster/dist/MarkerCluster.css'
import 'leaflet.markercluster/dist/MarkerCluster.Default.css'

export default defineComponent({
  name: 'SignalementFormAddress',
  components: {
    SignalementFormTextfield,
    SignalementFormButton,
    SignalementFormSubscreen,
    SignalementFormOnlyChoice
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
      showPickLocation: false,
      formStore,
      // Avoids searching when an option is selected in the list
      isSearchSkipped: false,
      selectedSuggestionIndex: -1,
      map: null as any,
      vectorTileLayer: null as any,
      previousRnbId: undefined as string | undefined,
      selectedRnbId: null as string | null,
      selectedBuilding: null as any,
      rnbMapController: null as any,
      mapKey: 0,
      buildingsList: [] as any[],
      viewMode: 'map' as 'map' | 'list',
      noBuildingFound: false,
      geocodedCityData: null as { city: string; postcode: string; citycode: string } | null,
      maxListItems: 20,
      pickLocationInitialized: false,
      addressFieldsDebounceTimeout: 0 as unknown as ReturnType<typeof setTimeout>,
      isLoadingMap: false,
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
          if (newValue && newValue.length > 10) {
            const codePostal = this.idAddress === 'adresse_logement_adresse_suggestion'
              ? ' ' + this.getCodePostalFromQueryParam()
              : ''
            requests.validateAddress(newValue + codePostal, this.handleAddressFound)
          } else {
            this.clearSuggestions()
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
  mounted () {
    document.addEventListener('click', this.handleClickOutside)
  },
  beforeUnmount () {
    document.removeEventListener('click', this.handleClickOutside)
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
    },
    pickLocationRequired (): boolean {
      return this.formStore.data.type_logement_nature !== 'autre'
    },
    pickLocationHeading (): string {
      return 'Sélectionner le bâtiment correspondant au logement' + (this.pickLocationRequired ? ' (obligatoire)' : '')
    },
    pickLocationAnnouncement (): string {
      return this.showPickLocation ? this.pickLocationHeading + '.' : ''
    },
    buildingChoices (): Array<{ label: string; value: string }> {
      // buildingsList est déjà trié par proximité (cf. rnb-map-controller.js) : on ne garde
      // que les plus proches, une liste de 100 items rend la page inutilisable.
      return this.buildingsList
        .slice(0, this.maxListItems)
        .map((b: any) => ({
          label: this.formatBuildingLabel(b),
          value: b.rnb_id
        }))
    },
    selectedRnbIdModel: {
      get (): string | null {
        return this.selectedRnbId
      },
      set (value: string | null) {
        this.selectedRnbId = value
        this.previousRnbId = value ?? undefined
        this.selectedBuilding = this.buildingsList.find((b: any) => b.rnb_id === value) ?? null
        if (value) {
          this.noBuildingFound = false
        }
      }
    }

  },
  methods: {
    updateValue (value: any) {
      this.$emit('update:modelValue', value)
    },
    handleAddressFieldsEdited (changeCommune:Boolean) {
      this.formStore.data[this.id + '_detail_manual'] = 1
      this.showPickLocation = false
      if (changeCommune) {
        formStore.data[this.id + '_detail_need_refresh_insee'] = true
      }

      const isEmpty =
        variableTester.isEmpty(this.formStore.data[this.id + '_detail_numero']) &&
        variableTester.isEmpty(this.formStore.data[this.id + '_detail_code_postal']) &&
        variableTester.isEmpty(this.formStore.data[this.id + '_detail_commune'])
      const isComplete =
        !variableTester.isEmpty(this.formStore.data[this.id + '_detail_numero']) &&
        !variableTester.isEmpty(this.formStore.data[this.id + '_detail_code_postal']) &&
        !variableTester.isEmpty(this.formStore.data[this.id + '_detail_commune'])

      if (isEmpty) {
        if (this.validate !== null && this.validate.required === false) {
          this.formStore.data[this.id + '_detail_manual'] = 0
        }
        this.pickLocationInitialized = false
        clearTimeout(this.addressFieldsDebounceTimeout)
        return
      }

      if (!isComplete) {
        clearTimeout(this.addressFieldsDebounceTimeout)
        return
      }

      this.showPickLocation = this.canPickLocation

      clearTimeout(this.addressFieldsDebounceTimeout)
      this.addressFieldsDebounceTimeout = setTimeout(() => {
        if (!this.pickLocationInitialized) {
          this.selectedRnbId = this.previousRnbId || null
          delete this.formStore.data[this.id + '_detail_rnb_id']
          this.mapKey++
          this.pickLocationInitialized = true
          this.$nextTick(() => this.initMap())
        } else {
          this.recenterMap()
        }
      }, 400)
    },

    recenterMap () {
      if (!this.map) return
      this.isLoadingMap = true
      this.geocodeAddress().then((result) => {
        if (result) {
          this.geocodedCityData = { city: result.city, postcode: result.postcode, citycode: result.citycode }
          this.map.setView(result.coords, 18)
        }
        this.isLoadingMap = false
      })
    },
    geocodeAddress (): Promise<{ coords: [number, number]; city: string; postcode: string; citycode: string } | null> {
      const apiAdresse = 'https://data.geopf.fr/geocodage/search/?q='
      const address = this.formStore.data[this.id + '_detail_numero'] + ' ' + this.formStore.data[this.id + '_detail_commune']
      const postCode = this.formStore.data[this.id + '_detail_code_postal']

      return fetch(apiAdresse + address + '&postcode=' + postCode)
        .then((response) => response.json())
        .then((json) => {
          if (json.features && json.features.length > 0) {
            const props = json.features[0].properties
            const coords: [number, number] = [json.features[0].geometry.coordinates[1], json.features[0].geometry.coordinates[0]]
            return { coords, city: props.city, postcode: props.postcode, citycode: props.citycode }
          }
          return null
        })
        .catch(() => null)
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
      this.clearSuggestions()
    },
    handleEscapeSuggestion () {
      this.clearSuggestions()
    },
    clearSuggestions () {
      this.suggestions.length = 0
      this.selectedSuggestionIndex = -1
    },
    handleClickOutside (event: MouseEvent) {
      if (this.suggestions.length === 0) {
        return
      }
      const target = event.target as HTMLElement
      if (this.$el && !this.$el.contains(target)) {
        this.clearSuggestions()
      }
    },
    handleAddressFound (requestResponse: any) {
      // Ignorer les réponses tardives si le champ a été vidé entre-temps
      const currentValue = this.formStore.data[this.idAddress]
      if (!currentValue || currentValue.length <= 10) {
        this.clearSuggestions()
        return
      }
      this.suggestions = requestResponse.features
    },
    getCodePostalFromQueryParam () {
      const queryString = window.location.search
      const urlParams = new URLSearchParams(queryString)
      return urlParams.get('cp') || ''
    },
    initMap () {
      if (this.map) {
        this.map.remove()
        this.map = null
        this.vectorTileLayer = null
      }

      const geolocParis: [number, number] = [48.8566, 2.3522] // Coordonnées de Paris par défaut

      this.isLoadingMap = true
      this.geocodeAddress().then((result) => {
        if (result) {
          // Conservé comme repli pour un bâtiment RNB sans adresse connue (cf.
          // handleSubmitPickLocation) : ce géocodage identifie déjà commune/CP/insee.
          this.geocodedCityData = { city: result.city, postcode: result.postcode, citycode: result.citycode }
          this.setupMap(result.coords, 18)
        } else {
          this.geocodedCityData = null
          this.setupMap(geolocParis, 13)
        }
        this.isLoadingMap = false
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
            onSelect: (rnbId: string, building: any) => {
              this.selectedRnbId = rnbId
              this.previousRnbId = rnbId
              this.selectedBuilding = building
              this.noBuildingFound = false
            },
            onAnnounce: (text: string) => {
              if (announcement) announcement.textContent = text
            },
            onFocusSubmit: () => {
              const btn = this.$refs.pickLocationSubmit as HTMLElement
              if (btn) btn.focus()
            },
            onBuildingsUpdate: (buildings: any[]) => {
              this.buildingsList = buildings
            },
          })
        }, 100)
      })
    },
    handleSubmitPickLocation () {
      if (this.selectedRnbId) {
        // Stocker le RNB ID dans formStore
        this.formStore.data[this.id + '_detail_rnb_id'] = this.selectedRnbId
        delete this.formStore.data[this.id + '_detail_no_building_found']

        const oldCommune = this.formStore.data[this.id + '_detail_commune']
        const oldCodePostal = this.formStore.data[this.id + '_detail_code_postal']

        const address = this.selectedBuilding?.addresses?.[0]
        if (address) {
          // Le bâtiment RNB porte une adresse officielle : on l'utilise pour fiabiliser
          // commune/code postal/insee et on recompose l'adresse affichée (le numéro
          // tapé par l'usager est conservé).
          this.formStore.data[this.id + '_detail_commune'] = address.city_name
          this.formStore.data[this.id + '_detail_code_postal'] = address.city_zipcode
          this.formStore.data[this.id + '_detail_insee'] = address.city_insee_code
          this.formStore.data[this.id + '_detail_need_refresh_insee'] = false
          this.formStore.data[this.id] = this.formStore.data[this.id + '_detail_numero'] + ' ' + address.city_zipcode + ' ' + address.city_name
          this.formStore.data[this.id + '_suggestion'] = this.formStore.data[this.id]
        } else if (this.geocodedCityData) {
          // Bâtiment sans adresse connue au RNB : on ne touche ni au numéro ni à la rue
          // tapés par l'usager, seulement commune/CP/insee, déjà fiabilisés par le
          // géocodage qui a servi à centrer la carte.
          this.formStore.data[this.id + '_detail_commune'] = this.geocodedCityData.city
          this.formStore.data[this.id + '_detail_code_postal'] = this.geocodedCityData.postcode
          this.formStore.data[this.id + '_detail_insee'] = this.geocodedCityData.citycode
          this.formStore.data[this.id + '_detail_need_refresh_insee'] = false
        }

        formStore.addressCorrectionMessage = buildAddressCorrectionMessage(
          oldCodePostal,
          oldCommune,
          this.formStore.data[this.id + '_detail_code_postal'],
          this.formStore.data[this.id + '_detail_commune']
        )

      } else if (this.noBuildingFound) {
        // L'usager indique ne pas trouver son bâtiment : on ne bloque pas le formulaire,
        // mais on trace l'information pour qu'un agent puisse la résoudre plus tard.
        delete this.formStore.data[this.id + '_detail_rnb_id']
        this.formStore.data[this.id + '_detail_no_building_found'] = 1
      }
    },
    formatBuildingLabel (building: any): string {
      const address = building.addresses && building.addresses[0]
      if (!address) {
        return 'Bâtiment sans adresse connue'
      }
      return [address.street_number, address.street].filter(Boolean).join(' ')
    },
    toggleViewMode () {
      this.viewMode = this.viewMode === 'map' ? 'list' : 'map'
      if (this.viewMode === 'map' && this.map) {
        this.$nextTick(() => {
          setTimeout(() => this.map.invalidateSize(), 50)
        })
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

@media (max-width: 48em) {
  .pick-location-container {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1000;
    overflow-y: auto;
    border-radius: 0;
    margin-top: 0 !important;
  }

  .pick-location-map {
    height: calc(100vh - 11rem);
  }
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
