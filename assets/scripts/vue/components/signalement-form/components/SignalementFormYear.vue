<template>
  <div :class="['fr-input-group', {'fr-input-group--error' : hasError}]" :id="id">
    <label :class="[ customCss, 'fr-label' ]" :for="id + '_input'">{{ label }}</label>
    <input
      :id="id + '_input'"
      :name="id"
      :placeholder="placeholder"
      type="number"
      min="1800"
      max="2099"
      step="1"
      :value="internalValue"
      :class="[ customCss, 'fr-input', {'fr-input--error' : hasError} ]"
      @input="updateValue($event)"
      />
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
  name: 'SignalementFormYear',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    placeholder: { type: String, default: '1984' },
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
