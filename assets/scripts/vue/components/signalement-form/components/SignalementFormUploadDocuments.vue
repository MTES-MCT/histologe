<template>
  <SignalementFormUploadPhotos
    :id="id"
    :label="label"
    :description="description"
    v-model="formStore.data[id + '_upload']"
    :labelInfo="labelInfo"
    :labelUpload="labelUpload"
    :multiple="true"
    fileType="documents"
    />
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from '../store'
import SignalementFormUploadPhotos from './SignalementFormUploadPhotos.vue'

export default defineComponent({
  name: 'SignalementFormUploadDocuments',
  components: {
    SignalementFormUploadPhotos
  },
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    description: { type: String, default: null },
    modelValue: {
      type: Array as () => Array<Object>,
      default: () => []
    },
    labelInfo: { type: String, default: null },
    labelUpload: { type: String, default: null },
    // les propriétés suivantes ne sont pas utilisées,
    // mais si on ne les met pas, elles apparaissent dans le DOM
    // et ça soulève des erreurs W3C
    hasError: { type: Boolean, default: false },
    handleClickComponent: Function,
    clickEvent: Function,
    access_name: { type: String, default: undefined },
    access_autocomplete: { type: String, default: undefined },
    access_focus: { type: Boolean, default: false }
  },
  data () {
    return {
      formStore
    }
  }
})
</script>

<style>
</style>
