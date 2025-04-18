<template>
<div :class="['fr-mb-6w fr-upload-group', { 'fr-upload-group--disabled': disabled }, {'custom-file-input-error' : hasError}]" :id="id">
  <div :class="[ customCss, 'fr-upload-wrap', 'fr-py-3v' ]">
    <input
      type="file"
      :id="id + '_input'"
      :name="id"
      :class="['custom-file-input']"
      :aria-describedby="hasError ? id + '-text-upload-error-desc-error' : undefined"
      :disabled="disabled"
      :multiple="multiple"
      @change="uploadFile($event)"
      :accept="accept"
      >
      <label :for="id + '_input'" class="fr-btn fr-btn--secondary fr-btn--icon-left fr-icon-add-line">
        {{ label }}
      </label>
      <span class="fr-hint-text">{{ description }}</span>
  </div>

  <div v-if="formStore.data[id] !== undefined">
    <div
      v-for="(file, index) in formStore.data[id]"
      :key="index"
      class="fr-grid-row"
      >
      <div class="fr-col-8 cut-text">
        <i>{{ getFileTitle(file) }}</i>
      </div>
      <div class="fr-col-4 fr-signalement-form-upload-delete-button">
        <button
          class="fr-link fr-icon-close-circle-line fr-link--icon-left fr-link--error"
          @click="deleteFile(file)"
          :aria-label="'Supprimer le fichier ' + getFileTitle(file)"
          >
          Supprimer
        </button>
      </div>
    </div>
  </div>
  <div
    :id="id + '-text-upload-error-desc-error'"
    class="fr-error-text"
    role="alert"
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
            v-if="uploadPercentage > 0 && uploadPercentage <= 100"
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
    multiple: { type: Boolean, default: false },
    type: { type: String, default: 'documents' },
    // les propriétés suivantes ne sont pas utilisées,
    // mais si on ne les met pas, elles apparaissent dans le DOM
    // et ça soulève des erreurs W3C
    handleClickComponent: Function,
    clickEvent: Function,
    access_name: { type: String, default: undefined },
    access_autocomplete: { type: String, default: undefined },
    access_focus: { type: Boolean, default: false }
  },
  data () {
    return {
      hasError: false,
      error: '',
      uploadPercentage: 0,
      uploadedFiles: <any>[],
      photosMimeTypes: [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
      ],
      photosExtensions: [
        'jpeg',
        'jpg',
        'png',
        'gif',
        'pdf'
      ],
      documentsMimeTypes: [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.oasis.opendocument.text',
        'application/msword',
        'text/plain',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/octet-stream',
        'message/rfc822',
        'application/vnd.ms-outlook'
      ],
      documentsExtensions: [
        'jpeg',
        'jpg',
        'png',
        'gif',
        'pdf',
        'docx',
        'odt',
        'doc',
        'txt',
        'xls',
        'xlsx',
        'eml',
        'msg'
      ],
      formStore
    }
  },
  computed: {
    accept () {
      if (this.type === 'documents') {
        return this.documentsMimeTypes.join(',')
      } else {
        return this.photosMimeTypes.join(',')
      }
    }
  },
  created () {
    this.initializeUploadedFiles()
  },
  methods: {
    initializeUploadedFiles () {
      const files = this.formStore.data[this.id]
      if (files && Array.isArray(files)) {
        this.uploadedFiles = files
      } else {
        this.uploadedFiles = []
      }
    },
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
      this.uploadPercentage = 0
      if (requestResponse) {
        if (requestResponse.name === 'AxiosError') {
          this.hasError = true
          this.error = requestResponse.response.data.error ?? requestResponse.message
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
      
      if (fileInput.files && fileInput.files.length > 0) {
        for (let i = 0; i < fileInput.files.length; i++) {
          const file = fileInput.files[i]
          const ext = file.name.split('.').pop()?.toLowerCase() ?? ''
          if (this.type === 'documents' && (this.documentsMimeTypes.indexOf(file.type) === -1 || this.documentsExtensions.indexOf(ext) === -1)) {
            fileInput.value = ''
            this.hasError = true
            this.error = 'Les fichiers de format ' + ext?.toUpperCase() + ' ne sont pas pris en charge.'
          } else if (this.type === 'photos' && (this.photosMimeTypes.indexOf(file.type) === -1 || this.photosExtensions.indexOf(ext) === -1)) {
            fileInput.value = ''
            this.hasError = true
            this.error = 'Les fichiers de format ' + ext?.toUpperCase() + ' ne sont pas pris en charge, merci de convertir votre image en JPEG, PNG ou en GIF avant de l\'envoyer.'
          } else if (file.size > 10 * 1024 * 1024) {
            fileInput.value = ''
            this.hasError = true
            this.error = 'L\'image dépasse 10MB'
          } else {
            this.hasError = false
          }
        }

        for (let i = 0; i < fileInput.files.length; i++) {
          const file = fileInput.files[i]
          const formData = new FormData()
          formData.append('signalement[' + this.type + ']', file)
          requests.uploadFile(formData, this.onFileUploaded, this.onFileProgress)
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
  position: absolute;
}
.custom-file-input:focus + label{
  outline-color: #0a76f6;
  outline-offset: 2px;
  outline-style: solid;
  outline-width: 2px;
}
.custom-file-input-error{
  border-left: 3px solid var(--text-default-error);
  margin-left: 10px;
  padding-left: 18px;
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
