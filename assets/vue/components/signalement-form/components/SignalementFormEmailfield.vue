<template>
  <div :class="['fr-input-group', { 'fr-input-group--disabled': disabled }]" :id="id">
    <label class='fr-label' :for="id + '_input'">
      {{ label }}
      <span class="fr-hint-text">{{ description }}</span>
    </label>
    <div :class="[ customCss, 'fr-input-wrap' ]">
      <input
          type="email"
          :id="id + '_input'"
          :name="access_name"
          :autocomplete="access_autocomplete"
          :value="internalValue"
          :class="[ customCss, 'fr-input' ]"
          @input="updateValue($event)"
          aria-describedby="text-input-error-desc-error"
          :disabled="disabled"
      >
    </div>
    <div
        id="text-input-error-desc-error"
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
  name: 'SignalementFormEmailfield',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    description: { type: String, default: null },
    modelValue: { type: String, default: null },
    customCss: { type: String, default: '' },
    validate: { type: Object, default: null },
    hasError: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: '' },
    access_name: { type: String, default: '' },
    access_autocomplete: { type: String, default: '' }
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
