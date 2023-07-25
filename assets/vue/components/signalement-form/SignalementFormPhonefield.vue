<template>
  <div>
    <SignalementFormTextfield
      :key="id"
      :id="id"
      :label="label"
      customCss="fr-fi-telegram-line"
      :validate="validate"
      v-model="formStore.data[id]"
      :hasError="formStore.validationErrors[id]  !== undefined"
      :error="formStore.validationErrors[id]"
    />
      <!-- TODO pattern à mettre ? -->
    <!-- TODO mise à jour du dsfr nécessaire pour "fr-icon-phone-line" -->
    <SignalementFormButton
      :key="idShow"
      :id="idShow"
      label="Ajouter un numéro"
      customCss="btn-link"
      :validate="validate"
      v-model="formStore.data[idShow]"
      :hasError="formStore.validationErrors[idShow]  !== undefined"
      :error="formStore.validationErrors[idShow]"
      :action="actionShow"
      :clickEvent="handleClickButton"
    />

    <SignalementFormTextfield
      :key="idSecond"
      :id="idSecond"
      label="Téléphone secondaire (facultatif)"
      customCss="fr-icon-phone-line"
      :validate="validate"
      v-model="formStore.data[idSecond]"
      :hasError="formStore.validationErrors[idSecond]  !== undefined"
      :error="formStore.validationErrors[idSecond]"
      class="fr-hidden"
    />
      <!-- TODO pattern à mettre ? -->
    <!-- TODO mise à jour du dsfr nécessaire pour "fr-icon-phone-line" -->
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './store'
import SignalementFormTextfield from './SignalementFormTextfield.vue'
import SignalementFormButton from './SignalementFormButton.vue'

export default defineComponent({
  name: 'SignalementFormPhonefield',
  components: {
    SignalementFormTextfield,
    SignalementFormButton
  },
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    modelValue: { type: String, default: null },
    customCss: { type: String, default: '' },
    validate: { type: Object, default: null },
    hasError: { type: Boolean, default: false },
    error: { type: String, default: '' },
    clickEvent: Function
  },
  data () {
    return {
      idSecond: this.id + '_secondaire',
      idShow: this.id + '_ajouter_numero',
      actionShow: 'show:' + this.id + '_secondaire',
      formStore
    }
  },
  methods: {
    updateValue (value: any) {
      this.$emit('update:modelValue', value)
    },
    handleClickButton (type:string, param:string, slugButton:string) {
      if (this.clickEvent !== undefined) {
        this.clickEvent(type, param, slugButton)
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
</style>
