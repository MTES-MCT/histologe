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
      customCss="btn-link fr-btn--icon-left fr-icon-eye-line"
      :validate="validate"
      v-model="formStore.data[idShow]"
      :hasError="formStore.validationErrors[idShow] !== undefined"
      :error="formStore.validationErrors[idShow]"
      :action="actionShow"
      :clickEvent="handleClickButton"
    />

    <SignalementFormSubscreen
      :key="idSubscreen"
      :id="idSubscreen"
      label=""
      customCss="fr-hidden fr-mt-3v"
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
      idAddress: this.id + '_suggestion',
      idShow: this.id + '_afficher_les_champs',
      idSubscreen: this.id + '_detail',
      actionShow: 'show:' + this.id + '_detail',
      screens: { body: updatedSubscreenData },
      suggestions: [] as any[],
      formStore,
      isModalOpen: false,
      modalLabel: '',
      modalDescription: ''
    }
  },
  created () {
    watch(
      () => this.formStore.data[this.idAddress],
      (newValue: any) => {
        clearTimeout(this.idFetchTimeout)
        this.idFetchTimeout = setTimeout(() => {
          if (newValue.length > 10) {
            const codePostal = 'adresse_logement_adresse_suggestion' === this.idAddress
                ? ' ' + this.getCodePostalFromQueryParam()
                : ''
            requests.validateAddress(newValue + codePostal, this.handleAddressFound)
          }
        }, 200)
      }
    )
  },
  methods: {
    updateValue (value: any) {
      this.$emit('update:modelValue', value)
    },
    handleClickButton (type:string, param:string, slugButton:string) {
      if (this.clickEvent !== undefined) {
        this.clickEvent(type, param, slugButton)
      }
    },
    handleSubscreenModelUpdate (newValue: string) {
      // Mettre à jour la valeur dans formStore.data lorsque la valeur du sous-écran change
      this.formStore.data[this.idSubscreen] = newValue
    },
    handleClickSuggestion (index: number) {
      if (this.suggestions) {
        this.formStore.data[this.id] = this.suggestions[index].properties.label
        this.formStore.data[this.id + '_detail_numero'] = this.suggestions[index].properties.name
        this.formStore.data[this.id + '_detail_code_postal'] = this.suggestions[index].properties.postcode
        this.formStore.data[this.id + '_detail_commune'] = this.suggestions[index].properties.city
        this.formStore.data[this.id + '_detail_insee'] = this.suggestions[index].properties.citycode
        this.formStore.data[this.id + '_detail_geoloc_lng'] = this.suggestions[index].geometry.coordinates[0]
        this.formStore.data[this.id + '_detail_geoloc_lat'] = this.suggestions[index].geometry.coordinates[1]
        if(this.isTerritoryToCheck){
          requests.checkTerritory(this.suggestions[index].properties.postcode, this.suggestions[index].properties.citycode, this.handleTerritoryChecked);
        }
        this.suggestions.length = 0
      }
      const subscreen = document.querySelector('#' + this.idSubscreen)
      const buttonShow = document.querySelector('#' + this.idShow)
      if (subscreen) {
        subscreen.classList.remove('fr-hidden')
      }
      if (buttonShow) {
        buttonShow.classList.add('fr-hidden')
      }
    },
    handleAddressFound (requestResponse: any) {
      this.suggestions = requestResponse.features
    },
    handleTerritoryChecked (requestResponse: any) {
      if(!requestResponse.success) {
          this.modalLabel = requestResponse.label
          this.modalDescription = requestResponse.message
          this.isModalOpen = true

          this.formStore.data[this.id] = ''
      }
      // TODO : vérifier si dans territoire expé NDE pour comportement différent ?
    },
    getCodePostalFromQueryParam() {
      const queryString = window.location.search;
      const urlParams = new URLSearchParams(queryString);
      return urlParams.get('cp');
    },
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
