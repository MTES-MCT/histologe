<template>
  <div :class="[customCss, 'fr-mb-3w']" :id="id + '_textfield'">
    <SignalementFormTextfield
        :key="id"
        :id="id"
        v-model="formStore.data[id]"
        :label="label"
        :description="description"
        :placeholder="placeholder"
        :validate="validate"
        :autocomplete="autocomplete"
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
    disabled: { type: Boolean, default: false }
  },
  data () {
    return {
      idFetchTimeout: 0 as unknown as ReturnType<typeof setTimeout>,
      suggestions: [] as any[],
      formStore,
      selectedSuggestion: '',
      selectedSuggestionIndex: -1
    }
  },
  created () {
    document.addEventListener('click', this.closeAutocomplete)
    watch(
      () => this.formStore.data[this.id],
      (newValue: any) => {
        clearTimeout(this.idFetchTimeout)
        this.idFetchTimeout = setTimeout(() => {
          const name = newValue.trim()
          if (name.length > 1) {
            this.selectedSuggestionIndex = -1
            const url = this.autocomplete.isAbsoluteLink
              ? variablesReplacer.replace(this.autocomplete.route)
              : window.location.origin + variablesReplacer.replace(this.autocomplete.route)
            requests.getAutompleteSuggestions(url, this.handleSuggestions)
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
        this.formStore.data[this.id] = this.suggestions[index].name
        const idWithoutAutocomplete = this.id.replace('_autocomplete', '')
        this.formStore.data[idWithoutAutocomplete] = this.suggestions[index].name
        this.selectedSuggestion = this.suggestions[index].name
        this.suggestions.length = 0
      }
    },
    handleSuggestions (requestResponse: any) {
      const idWithoutAutocomplete = this.id.replace('_autocomplete', '')
      this.formStore.data[idWithoutAutocomplete] = this.formStore.data[this.id]
      if (this.formStore.data[this.id] !== this.selectedSuggestion) {
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
}
</style>
