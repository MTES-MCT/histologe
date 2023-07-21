<template>
  <div class="fr-input-group">
    <SignalementFormTextfield
      :key="idAdress"
      :id="idAdress"
      label="Commençons par l'adresse du logement"
      description="Tapez puis sélectionnez l'adresse dans la liste"
      :customCss="customCss"
      :validate="validate"
      v-model="formStore.data[idAdress]"
      :hasError="formStore.validationErrors[idAdress]  !== undefined"
      :error="formStore.validationErrors[idAdress]"
    />
    <div id="sous_menu"></div>
    <SignalementFormButton
      :key="idShow"
      :id="idShow"
      label="Afficher tous les champs"
      customCss="btn-link"
      :validate="validate"
      v-model="formStore.data[idShow]"
      :hasError="formStore.validationErrors[idShow]  !== undefined"
      :error="formStore.validationErrors[idShow]"
      action="show:adresse-logement-tous-les-champs"
    />

    <SignalementFormSubscreen
      :key="idSubscreen"
      :id="idSubscreen"
      label=""
      customCss="fr-hidden"
      :components="screens"
      :validate="validate"
      v-model="formStore.data[idSubscreen]"
      :hasError="formStore.validationErrors[idSubscreen]  !== undefined"
      :error="formStore.validationErrors[idSubscreen]"
      @update:modelValue="handleSubscreenModelUpdate"
    />
  </div>
</template>

<script lang="ts">
import { defineComponent, watch } from 'vue'
import formStore from './store'
import { requests } from './requests'
import subscreenData from './address_subscreen.json'
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
    modelValue: { type: String, default: null },
    customCss: { type: String, default: '' },
    validate: { type: Object, default: null },
    hasError: { type: Boolean, default: false },
    error: { type: String, default: '' }
  },
  data () {
    const updatedSubscreenData = this.generateSubscreenData(this.id, subscreenData.body)

    return {
      idFetchTimeout: 0 as unknown as ReturnType<typeof setTimeout>,
      idAdress: this.id + '_suggestion',
      idShow: this.id + '_afficher_les_champs',
      idSubscreen: this.id + '_tous_les_champs',
      screens: { body: updatedSubscreenData },
      formStore
    }
  },
  computed: {
    internalValue: {
      get () {
        return this.modelValue
      },
      set (newValue: string) {
        this.$emit('update:modelValue', newValue)
      }
    }
  },
  created () {
    watch(
      () => this.formStore.data[this.idAdress],
      (newValue: any) => {
        clearTimeout(this.idFetchTimeout)
        this.idFetchTimeout = setTimeout(() => {
          if (newValue.length > 10) {
            requests.validateAddress(newValue, this.onAddressFound)
          }
        })
      }
    )
  },
  methods: {
    updateValue (value: any) {
      this.$emit('update:modelValue', value)
    },
    handleSubscreenModelUpdate (newValue: string) {
      // Mettre à jour la valeur dans formStore.data lorsque la valeur du sous-écran change
      this.formStore.data[this.idSubscreen] = newValue
    },
    generateSubscreenData (id: string, data: any[]) {
      return data.map((component) => {
        return {
          ...component,
          slug: id + '_' + component.slug
        }
      })
    },
    onAddressFound (requestResponse: any) {
      const container = document.querySelector('#sous_menu')
      const subscreen = document.querySelector('#' + this.idSubscreen)
      const buttonShow = document.querySelector('#' + this.idShow)

      // TODO : que faire si code postal dans département non ouvert ?
      // TODO : répertorier les exclusions de code postal du 69 ?
      // TODO : vérifier si dans territoire expé NDE pour comportement différent ?
      if (container) {
        container.innerHTML = ''

        for (const feature of requestResponse.features) {
          const suggestion = document.createElement('div')
          suggestion.classList.add(
            'fr-col-12',
            'fr-p-3v',
            'fr-text-label--blue-france',
            'fr-adresse-suggestion'
          )
          suggestion.innerHTML = feature.properties.label
          suggestion.addEventListener('click', () => {
            this.formStore.data[this.id + '_tous_les_champs_numero'] = feature.properties.name
            this.formStore.data[this.id + '_tous_les_champs_code_postal'] = feature.properties.postcode
            this.formStore.data[this.id + '_tous_les_champs_commune'] = feature.properties.city
            this.formStore.data[this.id + '_tous_les_champs_insee'] = feature.properties.citycode
            this.formStore.data[this.id + '_tous_les_champs_geoloc_lat'] = feature.geometry.coordinates[0]
            this.formStore.data[this.id + '_tous_les_champs_geoloc_lng'] = feature.geometry.coordinates[1]

            container.innerHTML = ''
            if (subscreen) {
              subscreen.classList.remove('fr-hidden')
            }
            if (buttonShow) {
              buttonShow.classList.add('fr-hidden')
            }
          })
          container.appendChild(suggestion)
        }
      }
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
.fr-adresse-suggestion:hover {
    background-color: var(--artwork-minor-blue-cumulus);
    color: white !important;
}
</style>
