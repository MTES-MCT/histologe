<template>
  <div class="histo-search fr-input-wrap fr-icon-search-line">
    <input
        class="fr-input"
        :id="id"
        :name="id"
        :value="modelValue"
        @input="onInputEvent"
        @search="onSearchEvent"
        :placeholder="placeholder"
        type="search"
    />
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'HistoSearch',
  props: {
    id: { type: String, default: '' },
    modelValue: { type: String, default: '' },
    onInput: { type: Function },
    placeholder: { type: String, default: '' },
    minLengthSearch: { type: Number, default: 0 }
  },
  emits: ['update:modelValue'],
  methods: {
    onInputEvent (e: any) {
      if (e.target.value.length >= this.minLengthSearch) {
        this.$emit('update:modelValue', e.target.value)
        if (this.onInput !== undefined) {
          this.onInput(e.target.value)
        }
      }
    },
    onSearchEvent (e: any) {
      if (e.target.value === '') {
        this.$emit('update:modelValue', null)
        if (this.onInput !== undefined) {
          this.onInput(null)
        }
      }
    }
  }
})
</script>
