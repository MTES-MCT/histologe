<template>
<div :class="['fr-mb-5v fr-upload-group', { 'fr-upload-group--disabled': disabled }]" :id="id">
  <div :class="[ customCss, 'fr-upload-wrap', 'fr-py-3v' ]">
    <label :for="id + '_input'" class="fr-btn fr-btn--secondary fr-btn--icon-left fr-icon-add-line">
      {{ label }}
    </label>
    <span class="fr-hint-text">{{ description }}</span>
    <input
      type="file"
      :id="id + '_input'"
      :name="id"
      class="custom-file-input"
      aria-describedby="text-upload-error-desc-error"
      :disabled="disabled"
      :multiple="multiple"
      @change="uploadFile($event)"
      >
      <!-- TODO : gérer type de fichier accept=".pdf,.doc,.docx" -->
  </div>

  <div v-if="formStore.data[id] !== undefined">
    <div
      v-for="(file, index) in formStore.data[id]"
      :key="index"
      class="fr-grid-row "
      >
      <div class="fr-col-8 cut-text">
        <i>{{ getFileTitle(file) }}</i>
      </div>
      <div class="fr-col-4 fr-signalement-form-upload-delete-button">
        <button
          id="signalement_uploadedfile_delete"
          class="fr-link fr-icon-close-circle-line fr-link--icon-left fr-link--error"
          @click="deleteFile(file)"
          >
          Supprimer
        </button>
      </div>
    </div>
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
    getFileTitle (file: any) {
      if (typeof file === 'string') {
        return file
      } else if (typeof file === 'object' && file.titre !== undefined) {
        return file.titre
      } else if (typeof file === 'object') {
        try {
          const parsedValue = JSON.parse(file)
          if (parsedValue.titre !== undefined) {
            return parsedValue.titre
          }
        } catch (error) {
          // Handle JSON parsing error if needed
        }
      }
      return 'Titre inconnu'
    },
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
    deleteFile (file: any) {
      const index = this.uploadedFiles.indexOf(file)
      if (index !== -1) {
        this.uploadedFiles.splice(index, 1)
        this.$emit('update:modelValue', this.uploadedFiles)
      }
      // TODO : la supprimer sur le bucket ?
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
.custom-file-input {
  opacity: 0;
  position: relative;
  line-height: 2.5rem;
  top: -2.5rem;
  width: 100%;
}
.fr-link--error {
  color: var(--text-default-error);
}
.fr-upload-group .cut-text {
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
}
.fr-upload-group .fr-signalement-form-upload-delete-button {
  display: flex;
  justify-content: right;
}
.fr-upload-group .fr-signalement-form-upload-delete-button button {
  display: inline-flex;
}
</style>
