<template>
  <div class="fr-container form-screen-body">
    <div
      v-if="icon"
      class="icon"
      >
      <img :src="icon.src" :alt="icon.alt">
    </div>
    <h1>{{ label }}</h1>
    <div v-html="descriptionVariablesReplaced"></div>
    <div
      v-if="components != undefined"
      >
      <component
        v-for="component in components.body"
        :is="component.type"
        v-bind:key="component.slug"
        :id="component.slug"
        :label="component.label"
        :labelInfo="component.labelInfo"
        :labelUpload="component.labelUpload"
        :description="component.description"
        :components="component.components"
        :icons="component.icons"
        :action="component.action"
        :link="component.link"
        :linktarget="component.linktarget"
        :values="component.values"
        :defaultValue="component.defaultValue"
        :customCss="component.customCss"
        :validate="component.validate"
        :disabled="component.disabled"
        :multiple="component.multiple"
        v-model="formStore.data[component.slug]"
        :hasError="formStore.validationErrors[component.slug]  !== undefined"
        :error="formStore.validationErrors[component.slug]"
        :class="{ 'fr-hidden': component.conditional && !formStore.shouldShowField(component.conditional.show) }"
        :clickEvent="handleClickComponent"
        :handleClickComponent="handleClickComponent"
      />
    </div>
  </div>
  <div
    v-if="components !== undefined && components.footer !== undefined && components.footer.length > 0"
    class="fr-container form-screen-footer"
    >
    <div>
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
        :class="[ { 'fr-hidden': component.conditional && !formStore.shouldShowField(component.conditional.show) } ]"
        :clickEvent="handleClickComponent"
      />
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'
import SignalementFormAddress from './SignalementFormAddress.vue'
import SignalementFormButton from './SignalementFormButton.vue'
import SignalementFormCheckbox from './SignalementFormCheckbox.vue'
import SignalementFormConfirmation from './SignalementFormConfirmation.vue'
import SignalementFormCounter from './SignalementFormCounter.vue'
import SignalementFormDate from './SignalementFormDate.vue'
import SignalementFormDisorderCategoryItem from './SignalementFormDisorderCategoryItem.vue'
import SignalementFormDisorderCategoryList from './SignalementFormDisorderCategoryList.vue'
import SignalementFormDisorderOverview from './SignalementFormDisorderOverview.vue'
import SignalementFormEmailfield from './SignalementFormEmailfield.vue'
import SignalementFormInfo from './SignalementFormInfo.vue'
import SignalementFormLink from './SignalementFormLink.vue'
import SignalementFormOnlyChoice from './SignalementFormOnlyChoice.vue'
import SignalementFormOverview from './SignalementFormOverview.vue'
import SignalementFormPhonefield from './SignalementFormPhonefield.vue'
import SignalementFormRoomList from './SignalementFormRoomList.vue'
import SignalementFormSubscreen from './SignalementFormSubscreen.vue'
import SignalementFormTextfield from './SignalementFormTextfield.vue'
import SignalementFormTextarea from './SignalementFormTextarea.vue'
import SignalementFormTime from './SignalementFormTime.vue'
import SignalementFormUpload from './SignalementFormUpload.vue'
import SignalementFormUploadPhotos from './SignalementFormUploadPhotos.vue'
import SignalementFormWarning from './SignalementFormWarning.vue'
import SignalementFormYear from './SignalementFormYear.vue'
import { variablesReplacer } from './../services/variableReplacer'
import { findPreviousScreen, findNextScreen } from '../services/disorderScreenNavigator'

export default defineComponent({
  name: 'SignalementFormScreen',
  components: {
    SignalementFormTextfield,
    SignalementFormTextarea,
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
    SignalementFormUpload,
    SignalementFormUploadPhotos,
    SignalementFormEmailfield,
    SignalementFormOverview,
    SignalementFormConfirmation,
    SignalementFormDisorderCategoryItem,
    SignalementFormDisorderCategoryList,
    SignalementFormDisorderOverview,
    SignalementFormRoomList
  },
  props: {
    label: String,
    description: String,
    icon: Object,
    components: Object,
    changeEvent: Function
  },
  data () {
    return {
      formStore,
      currentDisorderIndex: {
        batiment: 0,
        logement: 0
      } as { [key: string]: number }
    }
  },
  computed: {
    descriptionVariablesReplaced (): string {
      if (this.description !== undefined) {
        return variablesReplacer.replace(this.description)
      }
      return ''
    }
  },
  methods: {
    isRequired (field: any): boolean {
      const component = document.querySelector('#' + field.slug)
      if (((field.validate === undefined && formStore.inputComponents.includes(field.type)) || // si c'est un composant de saisie sans objet de validation c'est qu'il est obligatoire
          (field.validate && field.validate.required !== false)) && // ou il y a des règles de validation explicites
          component?.classList.contains('fr-hidden') === false) { // et que le composant n'est pas caché par conditionnalité
        return true
      } else {
        return false
      }
    },
    needToValidateSubComponents (field: any): boolean {
      const component = document.querySelector('#' + field.slug)
      if ((field.type === 'SignalementFormSubscreen' || field.type === 'SignalementFormAddress') && // si le composant est de type Subscreen ou Address
            field.components && // et il a des composants enfants
            (component?.classList.contains('fr-hidden') === false || field.type === 'SignalementFormAddress')) { // et que le composant n'est pas caché par conditionnalité si ce n'est pas un SignalementFormAddress
        return true
      } else {
        return false
      }
    },
    validateComponents (components: any) {
      for (const component of components) {
        const value = formStore.data[component.slug]
        if (this.isRequired(component)) {
          if (!value) {
            formStore.validationErrors[component.slug] = 'Ce champ est requis'
          }
        }
        let regexPattern
        // s'il y a une valeur, on vérifie si un pattern est requis (ou si c'est un type email)
        if (value && component.type === 'SignalementFormEmailfield') {
          regexPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        } else if (value && component.validate?.pattern !== undefined) {
          regexPattern = new RegExp(component.validate.pattern)
        }
        if (regexPattern !== undefined) {
          if (regexPattern.test(value) === false) {
            formStore.validationErrors[component.slug] = 'Format invalide'
          }
        }
        // TODO : Effectuer d'autres validations nécessaires pour les autres règles (minLength, maxLength, etc.)
        // Vérifier si ce composant nécessite une validation de ses sous-composants
        if (this.needToValidateSubComponents(component)) {
          this.validateComponents(component.components.body)
        }
      }
    },
    updateFormData (slug: string, value: any) {
      this.formStore.data[slug] = value
    },
    async handleClickComponent (type:string, param:string, param2:string) {
      if (type === 'link') {
        window.location.href = param
      } else if (type === 'cancel') {
        alert('on fait quoi quand on annule ?')
      } else if (type === 'show') {
        this.showComponentBySlug(param, param2)
      } else if (type === 'toggle') {
        this.toggleComponentBySlug(param, param2)
      } else if (type.includes('goto')) {
        await this.showScreenBySlug(param, param2, type.includes('save'))
      } else if (type.includes('resolve')) {
        this.navigateToDisorderScreen(param, param2, type.includes('save'))
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
    },
    toggleComponentBySlug (slug:string, isVisible:string) {
      const componentToToggle = document.querySelector('#' + slug)
      if (componentToToggle) {
        if (isVisible === '1') {
          componentToToggle.classList.remove('fr-hidden')
        } else {
          componentToToggle.classList.add('fr-hidden')
        }
      }
    },
    async showScreenBySlug (slug: string, slugButton:string, isSaveAndCheck:boolean) {
      formStore.validationErrors = {}

      if (isSaveAndCheck) {
        if (this.components && this.components.body) {
          this.validateComponents(this.components.body)
          if (Object.keys(formStore.validationErrors).length > 0) {
            window.scrollTo(0, 0)
            return
          }
        }
      }

      // Si pas d'erreur de validation, ou screen précédent (donc pas de validation), on change d'écran
      if (this.changeEvent !== undefined) {
        formStore.lastButtonClicked = slugButton
        await this.changeEvent(slug, isSaveAndCheck)
      }
    },
    async navigateToDisorderScreen (action: string, slugButton:string, isSaveAndCheck:boolean) {
      if (action === 'findNextScreen') {
        const index = formStore.data.currentStep.includes('batiment') ? this.currentDisorderIndex.batiment : this.currentDisorderIndex.logement
        const { currentCategory, incrementIndex, nextScreenSlug } = findNextScreen(formStore, index, slugButton)
        await this.showScreenBySlug(nextScreenSlug, slugButton, isSaveAndCheck)
        if (Object.keys(formStore.validationErrors).length === 0) {
          this.currentDisorderIndex[currentCategory] = incrementIndex
        }
      }

      if (action === 'findPreviousScreen') {
        const index = formStore.data.currentStep.includes('batiment') ? this.currentDisorderIndex.batiment : this.currentDisorderIndex.logement
        const { currentCategory, decrementIndex, previousScreenSlug } = findPreviousScreen(formStore, index)
        await this.showScreenBySlug(previousScreenSlug, slugButton, isSaveAndCheck)

        this.currentDisorderIndex[currentCategory] = decrementIndex < 0 ? 0 : decrementIndex
      }
    }
  }
})
</script>

<style>
  @media (max-width: 48em) {
    .form-screen-body {
      margin-bottom: 7.5rem !important;
    }

    .form-screen-body .icon {
      text-align: center;
    }

    .form-screen-footer {
      position: fixed;
      left: 0px;
      bottom: 0px;

      background-position: 0 0;
      background-repeat: no-repeat;
      background-size: 100% 1px;
      background-image: linear-gradient(0deg, var(--border-default-grey), var(--border-default-grey));
      background-color: var(--background-default-grey);
    }
  }

  .form-screen-body .icon img {
    max-height: 78px;
    width: auto;
  }

  .form-screen-footer > div {
    display: flex;
    justify-content: right;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
  }

  .form-screen-footer button, .form-screen-footer a {
    display: inline-flex;
    margin-left: 0.25rem;
  }
</style>
