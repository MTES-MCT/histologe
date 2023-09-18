<template>
  <div class="fr-fieldset__element signalement-form-checkbox">
    <div :class="['fr-checkbox-group', { 'fr-checkbox-group--error': hasError }]" :id="id">
      <input
          type="checkbox"
          :id="idCheckbox"
          :name="idCheckbox"
          :value="modelValue"
          :class="[ customCss ]"
          @input="updateValue($event)"
          aria-describedby="checkbox-error-messages"
          :checked="Boolean(modelValue)"
          >
      <label :class="[ customCss, 'fr-label' ]" :for="idCheckbox">{{ variablesReplacer.replace(label) }}</label>
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
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'
import { variablesReplacer } from './../services/variableReplacer'

export default defineComponent({
  name: 'SignalementFormCheckbox',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    description: { type: String, default: null },
    modelValue: { type: Number, default: 0 },
    customCss: { type: String, default: '' },
    validate: { type: Object, default: null },
    hasError: { type: Boolean, default: false },
    error: { type: String, default: '' }
  },
  data () {
    return {
      formStore,
      variablesReplacer,
      idCheckbox: this.id + '_check'
    }
  },
  methods: {
    updateValue (event: Event) {
      const value = (event.target as HTMLInputElement).checked
      this.$emit('update:modelValue', value ? 1 : null)
      if (!value) {
        for (const dataname in formStore.data) {
          if (dataname.includes(this.id)) {
            formStore.data[dataname] = null
          }
        }
      }
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
.signalement-form-checkbox {
  margin-top: 1rem;
}
</style>
