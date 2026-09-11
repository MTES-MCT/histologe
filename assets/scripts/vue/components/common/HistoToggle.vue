<template>
  <div :class="`histo-toggle ${containerClass}`">
    <div class="fr-toggle">
      <input
        type="checkbox"
        class="fr-toggle__input"
        :id="id"
        :checked="modelValue"
        @change="$emit('update:modelValue', $event.target.checked)"
        >
      <label class="fr-toggle__label" :for="id" :data-fr-checked-label="checkedLabel" :data-fr-unchecked-label="uncheckedLabel"><slot name="label"></slot></label>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'HistoToggle',
  props: {
    id: { type: String, default: null },
    containerClass: { type: String, default: '' },
    modelValue: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    checkedLabel: { type: String, default: 'Activé' },
    uncheckedLabel: { type: String, default: 'Désactivé' },
    onChange: { type: Function }
  },
  emits: ['update:modelValue']
})
</script>

<style>
  .histo-toggle input {
    background: #FFF;
  }
</style>
