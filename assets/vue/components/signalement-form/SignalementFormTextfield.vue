<template>
<div>
    <input
        type="text"
        :id="id"
        :name="id"
        :class="[ css, 'fr-input' ]"
        v-model="dataComponent"
        @change="$emit('update:modelValue', $event.target.value)"
        >
      <label :class="[ css, 'fr-label' ]" :for="id">{{ label }}</label>
</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'

export default defineComponent({
  name: 'SignalementFormTextfield',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    conditional: { type: Object },
    css: { type: String, default: '' },
    onChange: { type: Function }
  },
  data () {
    type ObjectKey = keyof typeof store.state
    console.log(this.id)
    console.log(store.state[this.id as ObjectKey])
    return {
      data: store.state,
      dataComponent: store.state[this.id as ObjectKey]
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
</style>
