<template>
  <fieldset
    :id="id"
    :class="[customCss, 'signalement-form-only-choice fr-fieldset', {'fr-fieldset--error' : hasError} ]"
    :aria-labelledby="id + '-radio-hint-legend'"
    :ref="id"
    >
      <legend
        :class="['fr-fieldset__legend--regular', 'fr-fieldset__legend', customLegendCss]"
        :id="id + '-radio-hint-legend'"
        >
        {{ variablesReplacer.replace(label) }}
      </legend>
      <div
        v-for="radioValue in values"
        :class="['fr-fieldset__element', (radioValue.value === 'oui' || radioValue.value === 'non') ? 'item-divided' : '']"
        :key="radioValue.value"
        >
          <div :class="['fr-radio-group', modelValue == radioValue.value ? 'is-checked' : '']">
            <input
              type="radio"
              :id="id + '_' + radioValue.value"
              :ref="id + '_' + radioValue.value + '_ref'"
              :name="id"
              v-bind:key="radioValue.value"
              :value="radioValue.value"
              class="fr-input"
              @input="updateValue($event)"
              :checked="radioValue.value === modelValue"
              :aria-describedby="id + '-radio-hint-messages'"
              >
            <label class="fr-label" :for="id + '_' + radioValue.value">
                {{ variablesReplacer.replace(radioValue.label) }}
            </label>
          </div>
      </div>
      <div class="fr-messages-group" :id="id + '-radio-hint-messages'" aria-live="assertive">
          <p
            class="fr-message fr-message--error fr-error-text"
            role="alert"
            :id="id + '-messages-error'"
            v-if="hasError"
            >{{ error }}</p>
      </div>
  </fieldset>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { matomo } from '../matomo'
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
    error: { type: String, default: '' },
    access_focus: { type: Boolean, default: false },
    // les propriétés suivantes ne sont pas utilisées,
    // mais si on ne les met pas, elles apparaissent dans le DOM
    // et ça soulève des erreurs W3C
    clickEvent: Function,
    handleClickComponent: Function,
    access_name: { type: String, default: undefined },
    access_autocomplete: { type: String, default: undefined }
  },
  data () {
    return {
      matomo,
      variablesReplacer,
      idRef: this.id + '_ref'
    }
  },
  mounted () {
    const element = this.$refs[this.id] as HTMLElement
    if (this.access_focus && element && !element.classList.contains('fr-hidden')) {
      this.focusInput()
    }
  },
  methods: {
    updateValue (event: Event) {
      const value = (event.target as HTMLInputElement).getAttribute('value')
      this.$emit('update:modelValue', value)
      if (this.customCss.includes('send-injonction-event')) {
        matomo.pushInjonctionEvent('changeRadioValue', this.id + ' >> ' + (value ?? ''))
      }
    },
    focusInput () {
      const focusableElement = (this.$refs[this.id + '_' + this.values[0].value + '_ref']) as Array<HTMLElement>
      if (focusableElement && focusableElement[0]) {
        focusableElement[0].focus()
      }
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
