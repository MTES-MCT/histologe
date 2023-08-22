<template>
    <div :class="['fr-checkbox-group', { 'fr-checkbox-group--error': hasError }]" :id="id">
      <input
          type="checkbox"
          :name="id"
          :value="internalValue"
          :class="[ customCss ]"
          @input="updateValue($event)"
          aria-describedby="checkbox-error-messages"
          >
      <label :class="[ customCss, 'fr-label' ]" :for="id">{{ label }}</label>
      <div class="fr-messages-group" id="checkbox-error-messages" aria-live="assertive">
        <p
          id="checkbox-error-messages"
          class="fr-message fr-message--error"
          v-if="hasError"
          >
          {{ error }}
        </p>
      </div>
    </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'SignalementFormCheckbox',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    description: { type: String, default: null },
    modelValue: { type: String, default: null },
    customCss: { type: String, default: '' },
    validate: { type: Object, default: null },
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
