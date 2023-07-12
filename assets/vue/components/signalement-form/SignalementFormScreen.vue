<template>
  <div>
    <h1>{{ label }}</h1>
    <p>{{ description }}</p>
    <component
      v-for="component in components"
      :is="component.type"
      v-bind:key="component.slug"
      :id="component.slug"
      :label="component.label"
      :action="component.action"
      :conditional="component.conditional"
      :css="component.css"
      :clickEvent="handleClickComponent"
    />
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import SignalementFormTextfield from './SignalementFormTextfield.vue'
import SignalementFormButton from './SignalementFormButton.vue'

export default defineComponent({
  name: 'SignalementFormScreen',
  components: {
    SignalementFormTextfield,
    SignalementFormButton
  },
  props: {
    label: String,
    description: String,
    components: Array,
    changeEvent: Function
  },
  methods: {
    handleClickComponent (type:string, param:string) {
      if (type === 'link') {
        window.location.href = param
      } else if (type === 'cancel') {
        alert('on fait quoi quand on annule ?')
      } else if (type === 'goto') {
        this.showScreenBySlug(param)
      } else if (type === 'show') {
        this.showComponentBySlug(param)
      }
    },
    showScreenBySlug (slug:string) {
      if (this.changeEvent !== undefined) {
        this.changeEvent(slug)
      }
    },
    showComponentBySlug (slug:string) {
      console.log(slug)
    }
  }
})
</script>

<style>
</style>
