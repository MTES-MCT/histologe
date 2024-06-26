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
      access_name="address"
      access_autocomplete="address-line1"
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
      label="Saisir une adresse manuellement"
      :customCss="buttonCss + ' btn-link fr-btn--icon-left fr-icon-edit-line'"
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
      // Avoids searching when an option is selected in the list
      isSearchSkipped: false
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
      formStore.data[this.id + '_detail_need_refresh_insee'] = true
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
        this.formStore.data[this.id + '_detail_need_refresh_insee'] = false
        this.formStore.data[this.idAddress] = this.suggestions[index].properties.label
        this.formStore.data[this.id] = this.suggestions[index].properties.label
        this.formStore.data[this.id + '_detail_numero'] = this.suggestions[index].properties.name
        this.formStore.data[this.id + '_detail_code_postal'] = this.suggestions[index].properties.postcode
        this.formStore.data[this.id + '_detail_commune'] = this.suggestions[index].properties.city
        this.formStore.data[this.id + '_detail_insee'] = this.suggestions[index].properties.citycode
        this.formStore.data[this.id + '_detail_geoloc_lng'] = this.suggestions[index].geometry.coordinates[0]
        this.formStore.data[this.id + '_detail_geoloc_lat'] = this.suggestions[index].geometry.coordinates[1]
        this.formStore.data[this.id + '_detail_manual'] = 0
        this.suggestions.length = 0
        setTimeout(() => {
          this.isSearchSkipped = false
        }, 200)
      }
    },
    handleAddressFound (requestResponse: any) {
      this.suggestions = requestResponse.features
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
