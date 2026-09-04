<template>
  <div class="fr-input-group">
    <label class="fr-label" :for="id">
      <slot name="label"></slot>
    </label>
    <div class="fr-input-wrap fr-icon-search-line">
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
</div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'

export default defineComponent({
  name: 'AppSearch',
  props: {
    id: { type: String, default: '' },
    modelValue: { type: String as PropType<string | undefined>, default: '' },
    placeholder: { type: String, default: '' },
    minLengthSearch: { type: Number, default: 0 }
  },
  emits: ['update:modelValue'],
  methods: {
    onInputEvent (e: Event) {
      const target = e.target as HTMLInputElement
      if (target.value.length >= this.minLengthSearch) {
        this.$emit('update:modelValue', target.value)
      } else if (target.value.length === 0) {
        this.$emit('update:modelValue', undefined)
      }
    }
  }
})
</script>
