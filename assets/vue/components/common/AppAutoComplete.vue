<template>
  <div class="app-autocomplete" @click="closeAutocomplete">
    <input
        :id="id"
        :name="id"
        class="fr-input"
        type="text"
        v-model="searchText"
        @input="updateSearch"
        :placeholder="placeholder"
        autocomplete="off"
        @keydown.down.prevent="handleDownSuggestion"
        @keydown.up.prevent="handleUpSuggestion"
        @keydown.enter.prevent="handleEnterSuggestion"
    />
    <ul v-if="suggestionFilteredList.length > 0"
        class="fr-grid-row fr-background-alt--blue-france fr-text-label--blue-france fr-autocomplete-list" >
      <li
          class="fr-col-12 fr-p-3v fr-text-label--blue-france fr-autocomplete-suggestion"
          v-for="(suggestion, index) in suggestionFilteredList"
          :key="index"
          @click="selectSuggestion(index)"
          :class="{ 'fr-autocomplete-suggestion-highlighted': index === selectedSuggestionIndex }"
      >
        {{ suggestion }}
      </li>
    </ul>
    <ul v-else-if="searchText.length > 0"
        class="fr-grid-row fr-background--white fr-text-label--red-marianne fr-autocomplete-list">
      <li class="fr-col-12 fr-p-3v fr-autocomplete-suggestion--disabled fr-text--xs">Aucun résultat trouvé</li>
    </ul>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'AppAutoComplete',
  emits: ['update:modelValue'],
  props: {
    id: { type: String, default: '' },
    modelValue: { type: Array, default: () => [] },
    suggestions: {
      type: Array,
      required: true
    },
    initSelectedSuggestions: {
      type: Array
    },
    multiple: {
      type: Boolean,
      default: false
    },
    placeholder: {
      type: String,
      default: ''
    },
    reset: {
      type: Boolean,
      default: false
    }
  },
  watch: {
    reset () {
      this.resetData()
    },
    modelValue (newValue: any) {
      if (newValue !== this.selectedSuggestions) {
        this.selectedSuggestions = []
      } else {
        this.selectedSuggestions = newValue
      }
    }
  },
  data () {
    return {
      searchText: '',
      selectedSuggestions: this.initSelectedSuggestions || [] as string[],
      suggestionFilteredList: [] as string[],
      selectedSuggestion: '',
      selectedSuggestionIndex: -1
    }
  },
  created () {
    document.addEventListener('click', this.closeAutocomplete)
  },
  methods: {
    selectSuggestion (index: number) {
      this.selectedSuggestionIndex = index
      if (this.multiple) {
        this.selectedSuggestions.push(this.suggestionFilteredList[index])
        this.searchText = ''
      } else {
        this.searchText = this.suggestionFilteredList[index]
      }
      this.suggestionFilteredList = []
      this.$emit('update:modelValue', this.multiple ? this.selectedSuggestions : this.searchText)
    },
    updateSearch () {
      if (this.searchText.length < 1) {
        this.suggestionFilteredList = []
      } else if (this.searchText.length > 1) {
        const searchTextNormalized = this.searchText
          .toLowerCase()
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .replace(/[\s-]/g, '')

        this.suggestionFilteredList = (this.suggestions as string[])
          .filter((item: string) => {
            const itemNormalized = item
              .toLowerCase()
              .normalize('NFD')
              .replace(/[\u0300-\u036f]/g, '')
              .replace(/[\s-]/g, '')

            return !this.selectedSuggestions.includes(item) && itemNormalized.includes(searchTextNormalized)
          })
      }
    },
    handleDownSuggestion () {
      if (this.selectedSuggestionIndex < this.suggestionFilteredList.length - 1) {
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
        this.selectSuggestion(this.selectedSuggestionIndex)
        this.selectedSuggestionIndex = -1
      }
    },
    closeAutocomplete (event: any) {
      const target = event.target as HTMLElement
      if (target && !event.target.closest('.fr-autocomplete-list')) {
        this.suggestionFilteredList = []
        this.selectedSuggestionIndex = -1
        this.searchText = ''
      }
    },
    resetData () {
      this.searchText = ''
      this.selectedSuggestions = []
      this.suggestionFilteredList = []
      this.selectedSuggestion = ''
      this.selectedSuggestionIndex = -1
    }
  }
})
</script>

<style>
 .app-autocomplete {
   position: relative;
   width: 100%;
   ul {
     position: absolute;
     width: 100%;
     z-index: 1;
   }
 }
 .fr-autocomplete-suggestion--disabled:hover {
   background-color: unset;
   cursor: default;
 }
</style>
