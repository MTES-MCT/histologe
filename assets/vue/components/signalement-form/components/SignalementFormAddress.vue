<template>
  <div :class="[customCss, 'signalement-form-address']" :id="id">
    <SignalementFormTextfield
      :key="idAddress"
      :id="idAddress"
      :label="label"
      :description="description"
      placeholder="Taper l'adresse ici"
      :validate="validate"
      v-model="formStore.data[idAddress]"
      :hasError="hasError"
      :error="error"
    />

    <div class="fr-grid-row fr-background-alt--blue-france fr-text-label--blue-france fr-address-group">
      <div
        v-for="(suggestion, index) in suggestions"
        :key="index"
        class="fr-col-12 fr-p-3v fr-text-label--blue-france fr-address-suggestion"
        @click="handleClickSuggestion(index)"
        >
        {{ suggestion.properties.label }}
      </div>
    </div>

    <SignalementFormButton
      :key="idShow"
      :id="idShow"
      label="Afficher tous les champs"
      :customCss="buttonCss + ' btn-link fr-btn--icon-left fr-icon-eye-line'"
      :validate="validate"
      :action="actionShow"
      :clickEvent="handleClickButton"
    />

    <SignalementFormSubscreen
      :key="idSubscreen"
      :id="idSubscreen"
      label=""
      :customCss="subscreenCss + ' fr-mt-3v'"
      :components="screens"
      :validate="validate"
      v-model="formStore.data[idSubscreen]"
      :hasError="formStore.validationErrors[idSubscreen] !== undefined"
      :error="formStore.validationErrors[idSubscreen]"
      @update:modelValue="handleSubscreenModelUpdate"
    />

    <SignalementFormModal
      v-model="isModalOpen"
      id="check_territory_modal"
      :label="modalLabel"
      :description="modalDescription"
    />
  </div>
</template>

<script lang="ts">
import { defineComponent, watch } from 'vue'
import formStore from './../store'
import { requests } from './../requests'
import { subscreenManager } from './../services/subscreenManager'
import subscreenData from './../address_subscreen.json'
import SignalementFormTextfield from './SignalementFormTextfield.vue'
import SignalementFormButton from './SignalementFormButton.vue'
import SignalementFormSubscreen from './SignalementFormSubscreen.vue'
import SignalementFormModal from './SignalementFormModal.vue'

export default defineComponent({
  name: 'SignalementFormAddress',
  components: {
    SignalementFormTextfield,
    SignalementFormButton,
    SignalementFormSubscreen,
    SignalementFormModal
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
    isTerritoryToCheck: { type: Boolean, default: false },
    clickEvent: Function
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
      actionShow: 'show:' + this.id + '_detail',
      screens: { body: updatedSubscreenData },
      suggestions: [] as any[],
      formStore,
      isModalOpen: false,
      // Avoids searching when an option is selected in the list
      isSearchSkipped: false,
      modalLabel: '',
      modalDescription: ''
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
      () => this.formStore.data[this.id + '_detail_code_postal'],
      async () => {
        this.handleAddressFieldsEdited()
      }
    )
    watch(
      () => this.formStore.data[this.id + '_detail_commune'],
      async () => {
        this.handleAddressFieldsEdited()
      }
    )
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
      if (this.buttonCss === '') {
        return 'fr-hidden'
      }
      return ''
    }
  },
  methods: {
    updateValue (value: any) {
      this.$emit('update:modelValue', value)
    },
    handleAddressFieldsEdited () {
      let isUpdateAddress = true
      let search = ''
      if (formStore.data[this.id + '_detail_code_postal'] === '' || formStore.data[this.id + '_detail_code_postal'] === undefined) {
        isUpdateAddress = false
      } else {
        search = formStore.data[this.id + '_detail_code_postal'] + ' '
      }

      if (formStore.data[this.id + '_detail_commune'] === '' || formStore.data[this.id + '_detail_commune'] === undefined) {
        isUpdateAddress = false
      } else {
        search += formStore.data[this.id + '_detail_commune']
      }

      if (isUpdateAddress) {
        this.isTyping = true
        clearTimeout(this.idFetchTimeout)
        this.idFetchTimeout = setTimeout(() => {
          this.isTyping = false
          requests.validateAddress(search, this.handleUpdateInsee)
        }, 300)
      }
    },
    handleUpdateInsee (requestResponse: any) {
      // Si le code postal / la commune ont été édités à la main, on valide l'ouverture du territoire
      if (requestResponse.features !== undefined) {
        const suggestions = requestResponse.features
        if (suggestions[0] !== undefined) {
          formStore.data[this.id + '_detail_insee'] = suggestions[0].properties.citycode
          this.checkTerritory(
            formStore.data[this.id + '_detail_code_postal'],
            formStore.data[this.id + '_detail_insee']
          )
        }
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
        this.formStore.data[this.idAddress] = this.suggestions[index].properties.label
        this.formStore.data[this.id] = this.suggestions[index].properties.label
        this.formStore.data[this.id + '_detail_numero'] = this.suggestions[index].properties.name
        this.formStore.data[this.id + '_detail_code_postal'] = this.suggestions[index].properties.postcode
        this.formStore.data[this.id + '_detail_commune'] = this.suggestions[index].properties.city
        this.formStore.data[this.id + '_detail_insee'] = this.suggestions[index].properties.citycode
        this.formStore.data[this.id + '_detail_geoloc_lng'] = this.suggestions[index].geometry.coordinates[0]
        this.formStore.data[this.id + '_detail_geoloc_lat'] = this.suggestions[index].geometry.coordinates[1]
        this.formStore.data[this.id + '_detail_manual'] = 0
        if (this.isTerritoryToCheck) {
          this.checkTerritory(
            this.suggestions[index].properties.postcode,
            this.suggestions[index].properties.citycode
          )
        }
        this.suggestions.length = 0
      }
    },
    handleAddressFound (requestResponse: any) {
      this.suggestions = requestResponse.features
    },
    handleTerritoryChecked (requestResponse: any) {
      // Si on a re-saisi du texte entre-temps, pas la peine de faire cette requête complémentaire
      if (this.isTyping) {
        return
      }
      if (!requestResponse.success) {
        this.modalLabel = requestResponse.label
        this.modalDescription = requestResponse.message
        this.isModalOpen = true

        this.formStore.data[this.id] = ''
      }
      this.isSearchSkipped = false
    },
    checkTerritory (postCode: any, cityCode: any) {
      requests.checkTerritory(
        postCode,
        cityCode,
        this.handleTerritoryChecked
      )
    },
    getCodePostalFromQueryParam () {
      const queryString = window.location.search
      const urlParams = new URLSearchParams(queryString)
      return urlParams.get('cp') || ''
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
</style>
