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
            >
            <!-- TODO : mettre multiple ? -->
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
      // TODO : prise en compte de l'upload, utiliser la route handle_upload ?
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
</style>
