<template>
  <div :class="['fr-input-group', {'fr-input-group--error' : hasError}]" :id="id">
  <label :class="[ customCss, 'fr-label' ]" :for="id + '_input'">{{ label }}</label>
  <input
      type="time"
      :id="id + '_input'"
      :name="id"
      :value="internalValue"
      :class="[ customCss, 'fr-input', {'fr-input--error' : hasError} ]"
      @input="updateValue($event)"
      aria-describedby="text-input-error-desc-error"
      >
  <div
    id="text-input-error-desc-error"
    class="fr-error-text"
    role="alert"
    v-if="hasError"
    >
    {{ error }}
  </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'SignalementFormTime',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    modelValue: { type: String, default: null },
    customCss: { type: String, default: '' },
    hasError: { type: Boolean, default: false },
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
    }
  },
  emits: ['update:modelValue']
})
</script>
