<template>
  <fieldset :id="id" :class="[customCss, 'fr-fieldset']" aria-labelledby="radio-hint-legend radio-hint-messages">
      <legend :class="['fr-fieldset__legend--regular', 'fr-fieldset__legend', customLegendCss]" id="radio-hint-legend">
        {{ variablesReplacer.replace(label) }}
      </legend>
      <div v-for="radioValue in values" :class="['fr-fieldset__element', (radioValue.value === 'oui' || radioValue.value === 'non') ? 'item-divided' : '']" :key="radioValue.value">
          <div class="fr-radio-group">
            <input
              type="radio"
              :id="id + '_' + radioValue.value"
              :name="id"
              v-bind:key="radioValue.value"
              :value="radioValue.value"
              class="fr-input"
              @input="updateValue($event)"
              :checked="radioValue.value === modelValue"
              aria-describedby="radio-error-messages"
              >
            <label class="fr-label" :for="id + '_' + radioValue.value">
                {{ variablesReplacer.replace(radioValue.label) }}
            </label>
          </div>
      </div>
      <div class="fr-messages-group" :id="id + '-messages'" aria-live="assertive">
          <p class="fr-message fr-message--error fr-error-text" :id="id + '-messages-error'" v-if="hasError">{{ error }}</p>
      </div>
  </fieldset>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { variablesReplacer } from './../services/variableReplacer'

export default defineComponent({
  name: 'SignalementFormOnlyChoice',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    modelValue: { type: String as () => null | string, default: null },
    values: { type: Array as () => Array<{ label: string; value: string }>, default: null },
    customCss: { type: String, default: '' },
    validate: { type: Object, default: null },
    hasError: { type: Boolean, default: false },
    error: { type: String, default: '' }
  },
  data () {
    return {
      variablesReplacer
    }
  },
  methods: {
    updateValue (event: Event) {
      const value = (event.target as HTMLInputElement).getAttribute('value')
      this.$emit('update:modelValue', value)
    }
  },
  computed: {
    customLegendCss () {
      if (this.customCss.includes('question-h1')) {
        return 'fr-h1'
      }
      return ''
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
  .signalement-form-only-choice .fr-radio-group {
    width: 100%;
    max-width: 500px;
    padding: 1rem;
    border: 1px solid var(--border-disabled-grey);
    background-color: var(--grey-1000-50);
  }
  .signalement-form-only-choice .fr-radio-group:hover {
    background-color: var(--grey-1000-50-hover);
  }

  @media (max-width: 48em) {
    .signalement-form-only-choice .fr-fieldset__element.item-divided {
      flex-basis: content;
    }
  }
</style>
