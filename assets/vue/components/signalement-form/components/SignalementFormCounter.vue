<template>
    <!-- Champ type number -->
    <div class="fr-input-group">
    <label :class="[ customCss, 'fr-label' ]" :for="id">{{ label }}</label>
    <input
        type="number"
        pattern="[0-9]*"
        inputmode="numeric"
        :id="id"
        :name="id"
        :value="internalValue"
        :class="[ customCss, 'fr-input' ]"
        @input="updateValue($event)"
        aria-describedby="text-input-error-desc-error"
        >
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
  name: 'SignalementFormCounter',
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
