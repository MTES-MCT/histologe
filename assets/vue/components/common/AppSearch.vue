<template>
  <div class="histo-search fr-input-wrap fr-icon-search-line">
    <input
        class="fr-input"
        :id="id"
        :name="id"
        :value="modelValue"
        @input="onInputEvent"
        :placeholder="placeholder"
        type="search"
    />
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'AppSearch',
  props: {
    id: { type: String, default: '' },
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    minLengthSearch: { type: Number, default: 0 }
  },
  emits: ['update:modelValue'],
  methods: {
    onInputEvent (e: any) {
      if (e.target.value.length >= this.minLengthSearch) {
        this.$emit('update:modelValue', e.target.value)
      } else if (e.target.value.length === 0) {
        this.$emit('update:modelValue', '')
      }
    }
  }
})
</script>
