<template>
  <div class="fr-input-group">
    <SignalementFormTextfield
      :key="id"
      :id="id"
      label="Commençons par l'adresse du logement"
      description="Tapez puis sélectionnez l'adresse dans la liste"
      :customCss="customCss"
      :validate="validate"
      v-model="formStore.data[id]"
      :hasError="formStore.validationErrors[id]  !== undefined"
      :error="formStore.validationErrors[id]"
    />

    <SignalementFormButton
      :key="id"
      :id="id"
      label="Afficher tous les champs"
      customCss="btn-link"
      :validate="validate"
      v-model="formStore.data[id]"
      :hasError="formStore.validationErrors[id]  !== undefined"
      :error="formStore.validationErrors[id]"
      action="show:adresse-logement-tous-les-champs"
    />

    <SignalementFormSubscreen
      key="adresse-logement-tous-les-champs"
      id="adresse-logement-tous-les-champs"
      label="Afficher tous les champs"
      customCss="fr-hidden"
      :components="screens"
      :validate="validate"
      v-model="formStore.data[id]"
      :hasError="formStore.validationErrors[id]  !== undefined"
      :error="formStore.validationErrors[id]"
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
    return {
      screens: subscreenData,
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
      () => this.formStore.data[this.id],
      (newValue: any) => {
        // Faites ce que vous devez faire avec la nouvelle valeur
        console.log('Nouvelle valeur :', newValue)
        // TODO  : appeler à intervalles réguliers, pas à chaque changement
        requests.validateAddress(newValue, this.onAddressFound)
      }
    )
  },
  methods: {
    updateValue (value: any) {
      this.$emit('update:modelValue', value)
    },
    onAddressFound (requestResponse: any) {
      console.log(requestResponse)
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
</style>
