<template>
  <div class="histo-checkbox">
    <div class="fr-checkbox-group">
      <input
        type="checkbox"
        :id="id"
        :name="id"
        :checked="modelValue"
        @change="$emit('update:modelValue', $event.target.checked)"
        >
      <label class="fr-label" :for="id"><slot name="label"></slot></label>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
	name: 'WDGCheckbox',
	props: {
		id: { type: String, default: null },
    modelValue: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    onChange: { type: Function }
  },
  emits: ['update:modelValue']
})
</script>

<style>
</style>
