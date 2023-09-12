<template>
  <div class="signalement-form-upload-photos" :id="id">
    <h2 v-if="label">{{ label }}</h2>
    <p v-if="description" v-html="description"></p>
    <SignalementFormInfo
      :id="id + '-info'"
      :label="labelInfo"
      />
    <SignalementFormUpload
      :id="id + '-upload'"
      :label="labelUpload"
      v-model="formStore.data[id + '-upload']"
      :multiple="true"
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
    labelUpload: { type: String, default: null }
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
