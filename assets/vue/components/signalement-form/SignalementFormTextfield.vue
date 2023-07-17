<template>
<div>
  <label :class="[ customCss, 'fr-label' ]" :for="id">{{ label }}</label>
  <input
        type="text"
        :id="id"
        :name="id"
        :value="internalValue"
        :class="[ customCss, 'fr-input' ]"
        @input="updateValue($event.target.value)"
        >
</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'SignalementFormTextfield',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    modelValue: { type: String, default: null },
    customCss: { type: String, default: '' }
  },
  computed: {
    internalValue: {
      get () {
        return this.modelValue
      },
      set (newValue: string) {
        this.$emit('update:modelValue', newValue)
      }
    }
  },
  methods: {
    updateValue (value: any) {
      this.$emit('update:modelValue', value)
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
</style>
