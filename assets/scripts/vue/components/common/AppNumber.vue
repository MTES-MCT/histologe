<template>
  <div class="fr-input-group">
    <label class="fr-label" :for="id">
      <slot name="label"></slot>
    </label>
    <input
        class="fr-input"
        :id="id"
        :name="id"
        :value="modelValue"
        @input="onInputEvent"
        :placeholder="placeholder"
        min="0"
        type="number"
    />
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'AppNumber',
  props: {
    id: { type: String, default: '' },
    modelValue: String,
    onInput: { type: Function },
    placeholder: String
  },
  emits: ['update:modelValue'],
  methods: {
    onInputEvent (e: any) {
      if (e.target.value > 0) {
        this.$emit('update:modelValue', e.target.value)
        if (this.onInput !== undefined) {
          this.onInput(e.target.value)
        }
      }
    }
  }
})
</script>
