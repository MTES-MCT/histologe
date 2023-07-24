<template>
  <div :class="[ customCss ]">
    <h2>{{ label }}</h2>
    <p v-html="description"></p>
    <div
      v-if="components != undefined"
      >
      <component
        v-for="component in components.body"
        :is="component.type"
        v-bind:key="component.slug"
        :id="component.slug"
        :label="component.label"
        :action="component.action"
        :values="component.values"
        :customCss="component.customCss"
        :validate="component.validate"
        :disabled="component.disabled"
        v-model="formStore.data[component.slug]"
        :hasError="formStore.validationErrors[component.slug]  !== undefined"
        :error="formStore.validationErrors[component.slug]"
        :class="{ 'fr-hidden': component.conditional && !formStore.shouldShowField(component.conditional.show) }"
        :clickEvent="handleClickComponent"
      />
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './store'
import SignalementFormTextfield from './SignalementFormTextfield.vue'
import SignalementFormButton from './SignalementFormButton.vue'
import SignalementFormOnlyChoice from './SignalementFormOnlyChoice.vue'

export default defineComponent({
  name: 'SignalementFormSubscreen',
  components: {
    SignalementFormTextfield,
    SignalementFormButton,
    SignalementFormOnlyChoice
  },
  props: {
    label: String,
    description: String,
    components: Object,
    customCss: { type: String, default: '' },
    handleClickComponent: Function
  },
  data () {
    return {
      formStore
    }
  },
  methods: {
    updateFormData (slug: string, value: any) {
      this.formStore.data[slug] = value
    }
  }
})
</script>

<style>
</style>
