<template>
  <div :class="['fr-input-group', { 'fr-input-group--disabled': disabled }]" :id="id">
    <label class='fr-label' :for="id">
      {{ label }}
      <span class="fr-hint-text">{{ description }}</span>
    </label>
    <div :class="[ customCss, 'fr-input-wrap' ]">
      <input
          type="email"
          :name="id"
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
import formStore from './../store'

export default defineComponent({
  name: 'SignalementFormEmailfield',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    description: { type: String, default: null },
    modelValue: { type: String, default: null },
    customCss: { type: String, default: '' },
    validate: { type: Object, default: null },
    disabled: { type: Boolean, default: false }
  },
  data () {
    return {
      formStore,
      hasError: false,
      error: ''
    }
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
    isValidEmail (email: string): boolean {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      return emailRegex.test(email)
    },
    updateValue (event: Event) {
      const value = (event.target as HTMLInputElement).value
      if (this.isValidEmail(value)) {
        this.$emit('update:modelValue', value)
        this.hasError = false
        this.error = ''
      } else {
        this.$emit('update:modelValue', '')
        console.log('erreur')
        // this.formStore.validationErrors
        this.hasError = true
        this.error = 'Email invalide'
      }
    }
  },
  emits: ['update:modelValue']
})
</script>
