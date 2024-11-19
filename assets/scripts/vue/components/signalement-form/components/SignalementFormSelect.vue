<template>
  <div
    :id="id"
    :class="[customCss, 'fr-select-group', { 'fr-select-group--error': hasError }]"
    :aria-labelledby="id + '-select-label'"
  >
    <label
      :for="id + '-select'"
      class="fr-label"
      :id="id + '-select-label'"
    >
      {{ variablesReplacer.replace(label) }}
      <span class="fr-hint-text">{{ description }}</span>
    </label>
    <select
      :id="id + '-select'"
      :class="['fr-select', { 'fr-select--error': hasError }]"
      :aria-describedby="id + '-select-hint-messages'"
      v-model="formStore.data[id]"
      @change="updateValue"
    >
      <option
        key=""
        value=""
      ></option>
      <option
        v-for="option in values"
        :key="option.value"
        :value="option.value"
      >
        {{ variablesReplacer.replace(option.label) }}
      </option>
    </select>
    <div class="fr-messages-group" :id="id + '-select-hint-messages'" aria-live="assertive">
      <p
        class="fr-message fr-message--error fr-error-text"
        role="alert"
        v-if="hasError"
      >
        {{ error }}
      </p>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { variablesReplacer } from '../services/variableReplacer'
import formStore from './../store'

export default defineComponent({
  name: 'SignalementFormSelect',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    modelValue: { type: String as () => null | string, default: null },
    values: { type: Array as () => Array<{ label: string; value: string }>, default: null },
    description: { type: String, default: null },
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
      variablesReplacer,
      formStore
    }
  },
  methods: {
    updateValue (event: Event) {
      const value = (event.target as HTMLSelectElement).value
      console.log(value)
      this.$emit('update:modelValue', value)
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
</style>
