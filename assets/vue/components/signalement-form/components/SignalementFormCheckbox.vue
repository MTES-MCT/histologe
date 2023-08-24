<template>
    <div :class="['fr-checkbox-group', { 'fr-checkbox-group--error': hasError }]" >
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
      <label :class="[ customCss, 'fr-label' ]" :for="idCheckbox">{{ labelVariablesReplaced }}</label>
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
      idCheckbox: this.id + '_check'
    }
  },
  computed: {
    labelVariablesReplaced (): string {
      if (this.label !== undefined) {
        return variablesReplacer.replace(this.label)
      }
      return ''
    }
  },
  methods: {
    updateValue (event: Event) {
      const value = (event.target as HTMLInputElement).checked
      this.$emit('update:modelValue', Number(value))
    }
  },
  emits: ['update:modelValue']
})
</script>
