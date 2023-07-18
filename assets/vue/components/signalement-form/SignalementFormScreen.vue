<template>
  <div>
    <h1>{{ label }}</h1>
    <div v-html="description"></div>
    <component
      v-for="component in components.body"
      :is="component.type"
      v-bind:key="component.slug"
      :id="component.slug"
      :label="component.label"
      :action="component.action"
      :customCss="component.customCss"
      :validate="component.validate"
      v-model="formStore.data[component.slug]"
      :hasError="formStore.validationErrors[component.slug]  !== undefined"
      :error="formStore.validationErrors[component.slug]"
      :class="{ 'fr-hidden': component.conditional && !formStore.shouldShowField(component.conditional.show) }"
      :clickEvent="handleClickComponent"
    />
  </div>
  <div>
    <component
      v-for="component in components.footer"
      :is="component.type"
      v-bind:key="component.slug"
      :id="component.slug"
      :label="component.label"
      :action="component.action"
      :customCss="component.customCss"
      v-model="formStore.data[component.slug]"
      :class="{ 'fr-hidden': component.conditional && !formStore.shouldShowField(component.conditional.show) }"
      :clickEvent="handleClickComponent"
    />
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './store'
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
    components: Object,
    changeEvent: Function
  },
  data () {
    return {
      formStore
    }
  },
  methods: {
    isRequired (field: any): boolean {
      if ((field.validate === undefined && formStore.inputComponents.includes(field.type)) || // si c'est un composant de saisie sans objet de validation c'est qu'il est obligatoire
          (field.validate && field.validate.required)) { // ou il y a des règles de validation explicites
        return true
      } else {
        return false
      }
    },
    updateFormData (slug: string, value: any) {
      this.formStore.data[slug] = value
    },
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
      formStore.validationErrors = {}

      if (this.components) {
        for (const field of this.components.body) {
          if (this.isRequired(field)) {
            const value = formStore.data[field.slug]
            if (!value) {
              formStore.validationErrors[field.slug] = 'Ce champ est requis' // field.errorText ?
            }
          }
          // Effectuer d'autres validations nécessaires pour les autres règles (minLength, maxLength, pattern, etc.)
        }
        if (Object.keys(formStore.validationErrors).length > 0) {
          window.scrollTo(0, 0)
          return
        }
      }
      // si pas d'erreur de validation, on change d'écran
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
