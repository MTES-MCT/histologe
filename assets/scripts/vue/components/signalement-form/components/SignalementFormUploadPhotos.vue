<template>
  <div class="signalement-form-upload-photos" :id="id">
    <h3 v-if="label">{{ label }}</h3>
    <p v-if="description" v-html="description"></p>
    <SignalementFormInfo
      :id="id + '_info'"
      :label="labelInfo"
      />
    <SignalementFormUpload
      :id="id + '_upload'"
      :label="labelUpload"
      v-model="formStore.data[id + '_upload']"
      :multiple="true"
      :type="fileType"
      />
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'
import SignalementFormInfo from './SignalementFormInfo.vue'
import SignalementFormUpload from './SignalementFormUpload.vue'

export default defineComponent({
  name: 'SignalementFormUploadPhotos',
  components: {
    SignalementFormInfo,
    SignalementFormUpload
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
    fileType: { type: String, default: 'photos' },
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
