<template>
  <div :class="[customCss, 'fr-mb-3w']" :id="id">
    <SignalementFormTextfield
        :key="idAutocomplete"
        :id="idAutocomplete"
        v-model="formStore.data[idAutocomplete]"
        :label="label"
        :description="description"
        :placeholder="placeholder"
        :validate="validate"
        :access_name="access_name"
        :access_autocomplete="access_autocomplete"
        :access_focus="access_focus"
        :hasError="hasError"
        :error="error"
        @keydown.down.prevent="handleDownSuggestion"
        @keydown.up.prevent="handleUpSuggestion"
        @keydown.enter.prevent="handleEnterSuggestion"
    />

    <ul class="fr-grid-row fr-background-alt--blue-france fr-text-label--blue-france fr-autocomplete-group"
        @click="closeAutocomplete">
      <li class="fr-col-12 fr-p-3v fr-text-label--blue-france fr-autocomplete-suggestion"
           v-for="(suggestion, index) in suggestions"
           :key="index"
          :class="{ 'fr-autocomplete-suggestion-highlighted': index === selectedSuggestionIndex }"
          @click="handleClickSuggestion(index)">
        {{ suggestion.name }}
      </li>
    </ul>
  </div>
</template>

<script lang="ts">
import { defineComponent, watch } from 'vue'
import { requests } from '../requests'
import { variablesReplacer } from './../services/variableReplacer'
import SignalementFormTextfield from './SignalementFormTextfield.vue'
import formStore from '../store'

export default defineComponent({
  name: 'SignalementFormAutocomplete',
  components: {
    SignalementFormTextfield
  },
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    description: { type: String, default: null },
    placeholder: { type: String, default: null },
    modelValue: { type: String, default: null },
    customCss: { type: String, default: '' },
    validate: { type: Object, default: null },
    hasError: { type: Boolean, default: false },
    error: { type: String, default: '' },
    autocomplete: { type: Object, default: null },
    disabled: { type: Boolean, default: false },
    access_name: { type: String, default: '' },
    access_autocomplete: { type: String, default: '' },
    access_focus: { type: Boolean, default: false },
    // les propriétés suivantes ne sont pas utilisées,
    // mais si on ne les met pas, elles apparaissent dans le DOM
    // et ça soulève des erreurs W3C
    clickEvent: Function,
    handleClickComponent: Function
  },
  data () {
    return {
      idFetchTimeout: 0 as unknown as ReturnType<typeof setTimeout>,
      idAutocomplete: this.id + '_textfield',
      suggestions: [] as any[],
      formStore,
      selectedSuggestion: '',
      selectedSuggestionIndex: -1
    }
  },
  created () {
    document.addEventListener('click', this.closeAutocomplete)
    watch(
      () => this.formStore.data[this.idAutocomplete],
      (newValue: any) => {
        clearTimeout(this.idFetchTimeout)
        this.idFetchTimeout = setTimeout(() => {
          const name = newValue.trim()
          this.updateValue(name)
          if (name.length > 1) {
            this.selectedSuggestionIndex = -1
            const url = this.autocomplete.isAbsoluteLink
              ? variablesReplacer.replace(this.autocomplete.route)
              : window.location.origin + variablesReplacer.replace(this.autocomplete.route)
            requests.getAutompleteSuggestions(url, this.handleSuggestions)
          } else {
            this.suggestions = []
            this.selectedSuggestionIndex = -1
          }
        }, 200)
      }
    )
  },
  methods: {
    updateValue (value: any) {
      this.$emit('update:modelValue', value)
    },
    handleClickSuggestion (index: number) {
      if (this.suggestions && this.suggestions[index]) {
        this.selectedSuggestionIndex = index
        this.formStore.data[this.idAutocomplete] = this.suggestions[index].name
        const idWithoutAutocomplete = this.idAutocomplete.replace('_autocomplete_textfield', '')
        this.formStore.data[idWithoutAutocomplete] = this.suggestions[index].name
        this.selectedSuggestion = this.suggestions[index].name
        this.suggestions.length = 0
      }
    },
    handleSuggestions (requestResponse: any) {
      const idWithoutAutocomplete = this.idAutocomplete.replace('_autocomplete_textfield', '')
      this.formStore.data[idWithoutAutocomplete] = this.formStore.data[this.idAutocomplete]
      if (this.formStore.data[this.idAutocomplete] !== this.selectedSuggestion) {
        this.suggestions = requestResponse
      }
    },
    handleDownSuggestion () {
      if (this.selectedSuggestionIndex < this.suggestions.length - 1) {
        this.selectedSuggestionIndex++
      }
    },
    handleUpSuggestion () {
      if (this.selectedSuggestionIndex > 0) {
        this.selectedSuggestionIndex--
      }
    },
    handleEnterSuggestion () {
      if (this.selectedSuggestionIndex !== -1) {
        this.handleClickSuggestion(this.selectedSuggestionIndex)
        this.selectedSuggestionIndex = -1
      }
    },
    closeAutocomplete (event: any) {
      const target = event.target as HTMLElement
      if (target && !event.target.closest('.fr-autocomplete-group')) {
        this.suggestions = []
        this.selectedSuggestionIndex = -1
      }
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
ul.fr-autocomplete-group {
  list-style-type: none;
  margin-top: -1.5rem;
  cursor: pointer;
}
</style>
