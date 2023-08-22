<template>
  <div class="signalement-form-address">
    <SignalementFormTextfield
      :key="idAddress"
      :id="idAddress"
      :label="label"
      :description="description"
      placeholder="Taper l'adresse ici"
      :customCss="customCss"
      :validate="validate"
      v-model="formStore.data[idAddress]"
      :hasError="formStore.validationErrors[idAddress] !== undefined"
      :error="formStore.validationErrors[idAddress]"
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
      customCss="fr-hidden"
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
import { subscreenServices } from './../services/subscreenData'
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
    const updatedSubscreenData = subscreenServices.generateSubscreenData(this.id, subscreenData.body)
    // on met à jour formStore en ajoutant les sous-composants du composant Address
    subscreenServices.addSubscreenData(this.id, updatedSubscreenData)
    return {
      idFetchTimeout: 0 as unknown as ReturnType<typeof setTimeout>,
      idAddress: this.id + '_suggestion',
      idShow: this.id + '_afficher_les_champs',
      idSubscreen: this.id + '_detail',
      actionShow: 'show:' + this.id + '_detail',
      screens: { body: updatedSubscreenData },
      suggestions: [] as any[],
      formStore
    }
  },
  created () {
    watch(
      () => this.formStore.data[this.idAddress],
      (newValue: any) => {
        clearTimeout(this.idFetchTimeout)
        this.idFetchTimeout = setTimeout(() => {
          if (newValue.length > 10) {
            requests.validateAddress(newValue, this.onAddressFound)
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
        this.formStore.data[this.id + '_detail_geoloc_lat'] = this.suggestions[index].geometry.coordinates[0]
        this.formStore.data[this.id + '_detail_geoloc_lng'] = this.suggestions[index].geometry.coordinates[1]
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
    onAddressFound (requestResponse: any) {
      this.suggestions = requestResponse.features
      // TODO : que faire si code postal dans département non ouvert ?
      // TODO : répertorier les exclusions de code postal du 69 ?
      // TODO : vérifier si dans territoire expé NDE pour comportement différent ?
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
