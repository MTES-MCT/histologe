<template>
  <span class="histo-search">
    <input
        class="fr-input"
        :id="id"
        :name="id"
        :value="modelValue"
        @input="onInputEvent"
        :placeholder="placeholder"
        type="search"
    />
  </span>
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
    }
  }
})
</script>
