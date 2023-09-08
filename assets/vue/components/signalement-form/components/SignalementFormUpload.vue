<template>
<div :class="['fr-upload-group', { 'fr-upload-group--disabled': disabled }]" :id="id">
  <label class='fr-label' :for="id" >
    {{ label }}
    <span class="fr-hint-text">{{ description }}</span>
  </label>
    <div :class="[ customCss, 'fr-upload-wrap' ]">
      <input
        type="file"
        :name="id"
        class="fr-upload"
        aria-describedby="text-upload-error-desc-error"
        :disabled="disabled"
        :multiple="multiple"
        @change="uploadFile($event)"
        >
        <p v-if="formStore.data[id] !== undefined">{{ uploadedFileTitle }}</p>
        <!-- TODO : gérer type de fichier accept=".pdf,.doc,.docx" -->
    </div>
    <div
      id="text-upload-error-desc-error"
      class="fr-error-text"
      v-if="hasError"
      >
      {{ error }}
    </div>
    <div class="fr-grid-row">
        <div class="fr-col">
            <progress
              max="100"
              id="progress_signalement_photos"
              :value="uploadPercentage"
              v-if="uploadPercentage > 0 && uploadPercentage < 100"
              >
            </progress>
        </div>
    </div>
    <div class="fr-grid-row">
        <div class="fr-col fr-text--center">
          <button
            id="signalement_uploadedfile_delete"
            class="fr-mt-2v fr-btn fr-btn--sm fr-btn--danger"
            v-if="formStore.data[id] !== undefined"
            @click="deleteFile"
            >
            Supprimer
          </button>
        </div>
    </div>
</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { requests } from './../requests'
import formStore from './../store'

export default defineComponent({
  name: 'SignalementFormUpload',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    description: { type: String, default: null },
    modelValue: {
      type: Array as () => Array<Object>,
      default: () => []
    },
    customCss: { type: String, default: '' },
    validate: { type: Object, default: null },
    disabled: { type: Boolean, default: false },
    multiple: { type: Boolean, default: false }
  },
  data () {
    return {
      hasError: false,
      error: '',
      uploadPercentage: 0,
      uploadedFiles: <any>[],
      formStore
    }
  },
  methods: {
    onFileUploaded (requestResponse: any) {
      if (requestResponse) {
        if (requestResponse.name === 'AxiosError') {
          this.hasError = true
          this.error = requestResponse.message
        }
        if (requestResponse.file !== undefined) {
          this.uploadedFiles.push(requestResponse)
          this.$emit('update:modelValue', this.uploadedFiles)
          // TODO : on affiche aussi une preview ?
        }
      }
    },
    onFileProgress (percentCompleted: any) {
      this.uploadPercentage = percentCompleted
    },
    uploadFile (event: Event) {
      const fileInput = event.target as HTMLInputElement
      // on supprime la donnée enregistrée jusqu'ici
      this.$emit('update:modelValue', undefined)
      if (fileInput.files && fileInput.files.length > 0) {
        for (let i = 0; i < fileInput.files.length; i++) {
          const file = fileInput.files[i]
          if (file.type === 'image/heic' || file.type === 'image/heif') {
            fileInput.value = ''
            this.hasError = true
            this.error = 'Les fichiers de format HEIC/HEIF ne sont pas pris en charge, merci de convertir votre image en JPEG ou en PNG avant de l\'envoyer.'
            break
          } else if (file.size > 10 * 1024 * 1024) {
            fileInput.value = ''
            this.hasError = true
            this.error = 'L\'image dépasse 10MB'
            break
          } else {
            this.hasError = false
          }
        }

        if (!this.hasError) {
          for (let i = 0; i < fileInput.files.length; i++) {
            const file = fileInput.files[i]
            const formData = new FormData()
            formData.append('signalement[documents]', file)
            requests.uploadFile(formData, this.onFileUploaded, this.onFileProgress)
          }
        }
      }
    },
    deleteFile (event: Event) {
      // on supprime la donnée enregistrée jusqu'ici
      this.$emit('update:modelValue', undefined)
      this.uploadedFiles = []
      // TODO : la supprimer sur le bucket ?
    }
  },
  computed: {
    uploadedFileTitle () {
      if (this.formStore.data[this.id] !== undefined) {
        let buffer = ''
        for (const file of this.formStore.data[this.id]) {
          if (file.titre !== undefined) {
            if (buffer !== '') {
              buffer += ', '
            }
            buffer += file.titre
          } else {
            const parsedValue = JSON.parse(file)
            if (buffer !== '') {
              buffer += ', '
            }
            buffer += parsedValue.titre
          }
        }
        return buffer
      } else {
        return undefined
      }
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
</style>
