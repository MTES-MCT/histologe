<template>
  <fieldset class="fr-fieldset" id="radio-hint" aria-labelledby="radio-hint-legend radio-hint-messages">
      <legend :class="[ customCss, 'fr-fieldset__legend--regular', 'fr-fieldset__legend']" :for="id" id="radio-hint-legend">
        {{ label }}
      </legend>
      <div v-for="radioValue in values" class="fr-fieldset__element" :key="radioValue.slug">
          <div class="fr-radio-group">
            <input
              type="radio"
              :id="radioValue.slug"
              v-bind:key="radioValue.slug"
              :name="id"
              :value="radioValue.slug"
              :class="[ customCss, 'fr-input' ]"
              @input="updateValue($event)"
              aria-describedby="radio-error-messages"
              >
              <label class="fr-label" :for="radioValue.slug">
                  {{ radioValue.value }}
              </label>
          </div>
      </div>
      <div class="fr-messages-group" id="radio-error-messages" aria-live="assertive">
          <p class="fr-message fr-message--error fr-error-text" id="radio-error-message-error" v-if="hasError">{{ error }}</p>
      </div>
  </fieldset>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'SignalementFormOnlyChoice',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    modelValue: { type: String, default: null },
    values: { type: Array as () => Array<{ slug: string; value: string }>, default: null },
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
      const value = (event.target as HTMLInputElement).getAttribute('value')
      this.$emit('update:modelValue', value)
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
</style>
