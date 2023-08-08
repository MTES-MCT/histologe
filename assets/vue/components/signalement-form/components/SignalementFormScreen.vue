<template>
  <div class="fr-container">
    <h1>{{ label }}</h1>
    <div v-html="description"></div>
    <div
      v-if="components != undefined"
      >
      <component
        v-for="component in components.body"
        :is="component.type"
        v-bind:key="component.slug"
        :id="component.slug"
        :label="component.label"
        :description="component.description"
        :components="component.components"
        :action="component.action"
        :link="component.link"
        :linktarget="component.linktarget"
        :values="component.values"
        :defaultValue="component.defaultValue"
        :customCss="component.customCss"
        :validate="component.validate"
        :disabled="component.disabled"
        v-model="formStore.data[component.slug]"
        :hasError="formStore.validationErrors[component.slug]  !== undefined"
        :error="formStore.validationErrors[component.slug]"
        :class="{ 'fr-hidden': component.conditional && !formStore.shouldShowField(component.conditional.show) }"
        :clickEvent="handleClickComponent"
        :handleClickComponent="handleClickComponent"
      />
    </div>
  </div>
  <div class="fr-mt-5w"
    v-if="components != undefined"
    >
    <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--center">
      <component
        v-for="component in components.footer"
        :is="component.type"
        v-bind:key="component.slug"
        :id="component.slug"
        :label="component.label"
        :action="component.action"
        :link="component.link"
        :linktarget="component.linktarget"
        :customCss="component.customCss"
        v-model="formStore.data[component.slug]"
        :class="[ 'fr-col-4', { 'fr-hidden': component.conditional && !formStore.shouldShowField(component.conditional.show) } ]"
        :clickEvent="handleClickComponent"
      />
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'
import SignalementFormTextfield from './SignalementFormTextfield.vue'
import SignalementFormButton from './SignalementFormButton.vue'
import SignalementFormLink from './SignalementFormLink.vue'
import SignalementFormOnlyChoice from './SignalementFormOnlyChoice.vue'
import SignalementFormSubscreen from './SignalementFormSubscreen.vue'
import SignalementFormAddress from './SignalementFormAddress.vue'
import SignalementFormDate from './SignalementFormDate.vue'
import SignalementFormYear from './SignalementFormYear.vue'
import SignalementFormTime from './SignalementFormTime.vue'
import SignalementFormCounter from './SignalementFormCounter.vue'
import SignalementFormWarning from './SignalementFormWarning.vue'
import SignalementFormInfo from './SignalementFormInfo.vue'
import SignalementFormCheckbox from './SignalementFormCheckbox.vue'
import SignalementFormPhonefield from './SignalementFormPhonefield.vue'
import SignalementFormEmailfield from './SignalementFormEmailfield.vue'
import SignalementFormOverview from './SignalementFormOverview.vue'
import SignalementFormConfirmation from './SignalementFormConfirmation.vue'
import { services } from './../services'

export default defineComponent({
  name: 'SignalementFormScreen',
  components: {
    SignalementFormTextfield,
    SignalementFormButton,
    SignalementFormLink,
    SignalementFormOnlyChoice,
    SignalementFormSubscreen,
    SignalementFormAddress,
    SignalementFormDate,
    SignalementFormYear,
    SignalementFormTime,
    SignalementFormCounter,
    SignalementFormWarning,
    SignalementFormInfo,
    SignalementFormCheckbox,
    SignalementFormPhonefield,
    SignalementFormEmailfield,
    SignalementFormOverview,
    SignalementFormConfirmation
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
      const subscreen = document.querySelector('#' + field.slug)
      if (((field.validate === undefined && formStore.inputComponents.includes(field.type)) || // si c'est un composant de saisie sans objet de validation c'est qu'il est obligatoire
          (field.validate && field.validate.required)) && // ou il y a des règles de validation explicites
          subscreen?.classList.contains('fr-hidden') === false) { // et que le composant n'est pas caché par conditionnalité
        return true
      } else {
        return false
      }
    },
    updateFormData (slug: string, value: any) {
      this.formStore.data[slug] = value
    },
    handleClickComponent (type:string, param:string, slugButton:string) {
      if (type === 'link') {
        window.location.href = param
      } else if (type === 'cancel') {
        alert('on fait quoi quand on annule ?')
      } else if (type === 'goto') {
        this.showScreenBySlug(param, slugButton)
      } else if (type === 'show') {
        this.showComponentBySlug(param, slugButton)
      }
    },
    showScreenBySlug (slug: string, slugButton:string) {
      formStore.validationErrors = {}
      const isScreenAfterCurrent = services.isScreenAfterCurrent(slug)

      const traverseComponents = (components: any) => {
        for (const field of components) {
          if (this.isRequired(field)) {
            const value = formStore.data[field.slug]
            if (!value) {
              formStore.validationErrors[field.slug] = 'Ce champ est requis' // field.errorText ?
            }
          }
          // Effectuer d'autres validations nécessaires pour les autres règles (minLength, maxLength, pattern, etc.)
          // Vérifier si le composant est de type Subscreen ou Address et a des composants enfants
          if ((field.type === 'SignalementFormSubscreen' || field.type === 'SignalementFormAddress') && field.components) {
            traverseComponents(field.components.body)
          }
        }
      }

      if (this.components && this.components.body && isScreenAfterCurrent) {
        traverseComponents(this.components.body)
        if (Object.keys(formStore.validationErrors).length > 0) {
          window.scrollTo(0, 0)
          return
        }
      }
      // Si pas d'erreur de validation, ou screen précédent (donc pas de validation), on change d'écran
      if (this.changeEvent !== undefined) {
        this.changeEvent(slug)
      }
    },
    showComponentBySlug (slug:string, slugButton:string) {
      const componentToShow = document.querySelector('#' + slug)
      if (componentToShow) {
        componentToShow.classList.remove('fr-hidden')
      }
      const buttonToHide = document.querySelector('#' + slugButton)
      if (buttonToHide) {
        buttonToHide.classList.add('fr-hidden')
      }
    }
  }
})
</script>

<style>
</style>
