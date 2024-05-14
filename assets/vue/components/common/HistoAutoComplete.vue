<template>
  <div class="histo-autocomplete" @click="closeAutocomplete">
    <input
        :id="id"
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
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'HistoAutoComplete',
  emits: ['update:modelValue'],
  props: {
    id: { type: String, default: '' },
    suggestions: {
      type: Array,
      required: true
    },
    multiple: {
      type: Boolean,
      default: false
    },
    placeholder: {
      type: String,
      default: ''
    }
  },
  data () {
    return {
      searchText: '',
      selectedSuggestions: [] as string[],
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
        this.suggestionFilteredList = []
      } else {
        this.searchText = this.suggestionFilteredList[index]
        this.suggestionFilteredList = []
      }
      this.$emit('update:modelValue', this.multiple ? this.selectedSuggestions : this.searchText)
    },
    updateSearch () {
      if (this.searchText.length < 1) {
        this.suggestionFilteredList = []
      } else {
        this.suggestionFilteredList = (this.suggestions as string[])
          .filter((item: string) =>
            !this.selectedSuggestions.includes(item) &&
                item.toLowerCase().includes(this.searchText.toLowerCase())
          )
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
        this.selectSuggestion(this.selectedSuggestionIndex)
        this.selectedSuggestionIndex = -1
      }
    },
    closeAutocomplete (event: any) {
      const target = event.target as HTMLElement
      if (target && !event.target.closest('.fr-autocomplete-list')) {
        this.suggestionFilteredList = []
        this.selectedSuggestionIndex = -1
      }
    }
  }
})
</script>

<style>
 .histo-autocomplete {
   position: relative;
   width: 100%;
   ul {
     position: absolute;
     width: 100%;
     z-index: 1;
   }
 }
</style>
