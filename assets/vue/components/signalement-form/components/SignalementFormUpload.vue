<template>
<div :class="['fr-upload-group', { 'fr-upload-group--disabled': disabled }]" :id="id">
  <label class='fr-label' :for="id">
    {{ label }}
    <span class="fr-hint-text">{{ description }}</span>
  </label>
    <div :class="[ customCss, 'fr-upload-wrap' ]">
      <input
            type="file"
            :name="id"
            :value="internalValue"
            :class="[ customCss, 'fr-upload' ]"
            @input="updateValue($event)"
            aria-describedby="text-upload-error-desc-error"
            :disabled="disabled"
            @change="uploadFile($event)"
            >
            <!-- TODO : mettre multiple ? -->
            <!-- TODO : gérer type de fichier accept=".pdf,.doc,.docx" -->
    </div>
    <div
      id="text-upload-error-desc-error"
      class="fr-error-text"
      v-if="hasError"
      >
      {{ error }}
    </div>
</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { requests } from './../requests'

export default defineComponent({
  name: 'SignalementFormUpload',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    description: { type: String, default: null },
    modelValue: { type: String, default: null },
    customCss: { type: String, default: '' },
    validate: { type: Object, default: null },
    hasError: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: '' }
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
  methods: {
    updateValue (event: Event) {
      const value = (event.target as HTMLInputElement).value
      this.$emit('update:modelValue', value)
    },
    onFileUploaded (requestResponse: any) {
      console.log(requestResponse)
      // TODO : gérer l'affichage (erreur ou réussite)
      if (requestResponse) {
        // TODO : enregistrer l'objet reçu à la place de "C:\\fakepath\\xxx.png" ?
      }
    },
    uploadFile (event: Event) {
      // TODO : afficher une barre de progression ?
      const fileInput = event.target as HTMLInputElement
      if (fileInput.files && fileInput.files.length > 0) {
        const formData = new FormData()
        formData.append('signalement[documents]', fileInput.files[0])
        // TODO : faut-il permettre une sélection multiple   ?
        requests.uploadFile(formData, this.onFileUploaded)
      }
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
</style>
