<template>
  <div :class="[ 'fr-container form-screen-body', customCss ]">
    <div
      v-if="icon"
      class="icon"
      >
      <img :src="icon.src" :alt="icon.alt">
    </div>
    <h1 v-if="formStore.currentScreen?.slug === 'introduction'" >{{ label }}</h1>
    <h2 v-else-if="label !== ''">{{ label }}</h2>
    <div v-html="variablesReplacer.replace(description)"></div>
    <div
      v-if="components != undefined"
      >
      <component
        v-for="component in components.body"
        :is="component.type"
        v-bind:key="component.slug"
        :id="component.slug"
        :label="component.label"
        :hint="component.hint"
        :labelInfo="component.labelInfo"
        :labelUpload="component.labelUpload"
        :description="component.description"
        :placeholder="component.placeholder"
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
        :ariaControls="component.ariaControls"
        :tagWhenEdit="component.tagWhenEdit"
        v-model="formStore.data[component.slug]"
        :hasError="formStore.validationErrors[component.slug]  !== undefined"
        :error="formStore.validationErrors[component.slug]"
        :class="{ 'fr-hidden': component.conditional && !formStore.shouldShowField(component.conditional.show) }"
        :autocomplete="component.autocomplete"
        :clickEvent="handleClickComponent"
        :handleClickComponent="handleClickComponent"
        :access_name="component.accessibility?.name ?? component.slug"
        :access_autocomplete="component.accessibility?.autocomplete ?? 'off'"
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
        :labelInfo="component.labelInfo"
        :labelUpload="component.labelUpload"
        :description="component.description"
        :placeholder="component.placeholder"
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
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'
import { requests } from '../requests'
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
import SignalementFormIcon from './SignalementFormIcon.vue'
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
import SignalementFormModal from './SignalementFormModal.vue'
import SignalementFormAutocomplete from './SignalementFormAutocomplete.vue'
import { variablesReplacer } from './../services/variableReplacer'
import { componentValidator } from './../services/componentValidator'
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
    SignalementFormIcon,
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
    SignalementFormRoomList,
    SignalementFormModal,
    SignalementFormAutocomplete
  },
  props: {
    label: String,
    description: String,
    icon: Object,
    components: Object,
    changeEvent: Function,
    customCss: String
  },
  data () {
    return {
      formStore,
      requests,
      variablesReplacer,
      componentValidator,
      currentDisorderIndex: {
        batiment: 0,
        logement: 0
      } as { [key: string]: number }
    }
  },
  methods: {
    isRequired (field: any): boolean {
      if (!field.slug.includes('{{number}}')) {
        const component = document.querySelector('#' + field.slug)
        if (((field.validate === undefined && formStore.inputComponents.includes(field.type)) || // si c'est un composant de saisie sans objet de validation c'est qu'il est obligatoire
            (field.validate && field.validate.required !== false)) && // ou il y a des règles de validation explicites
            component?.classList.contains('fr-hidden') === false) { // et que le composant n'est pas caché par conditionnalité
          return true
        }
      }
      return false
    },
    needToValidateSubComponents (field: any): boolean {
      if (!field.slug.includes('{{number}}')) {
        const component = document.querySelector('#' + field.slug)
        if ((field.type === 'SignalementFormSubscreen' || field.type === 'SignalementFormAddress') && // si le composant est de type Subscreen ou Address
              field.components && // et il a des composants enfants
              (component?.classList.contains('fr-hidden') === false || field.type === 'SignalementFormAddress')) { // et que le composant n'est pas caché par conditionnalité si ce n'est pas un SignalementFormAddress
          return true
        }
      }
      return false
    },
    validateComponents (components: any) {
      for (const component of components) {
        const value = formStore.data[component.slug]
        if (this.isRequired(component) && component.type !== 'SignalementFormAddress' && !value ) {
            formStore.validationErrors[component.slug] = 'Ce champ est requis'
        }
        if (component.type === 'SignalementFormAddress') {
          this.validateComponentAddress(component.slug, this.isRequired(component))
        }

        componentValidator.validate(component)

        // Vérifier si ce composant nécessite une validation de ses sous-composants
        if (this.needToValidateSubComponents(component)) {
          this.validateComponents(component.components.body)
        }
      }
    },
    validateComponentAddress (componentSlug: any, required: boolean) {
      if(required) {
        const validationError = 'Ce champ est requis'
        // tous les champs sont vides, on affiche l'erreur sur le champs de recherche
        if (!formStore.data[componentSlug] &&
              !formStore.data[componentSlug + '_detail_numero'] &&
              !formStore.data[componentSlug + '_detail_code_postal'] &&
              !formStore.data[componentSlug + '_detail_commune']
        ) {
          formStore.validationErrors[componentSlug] = validationError

        // il y a eu une édition manuelle : on vérifie tous les sous-champs
        } else if (formStore.data[componentSlug + '_detail_manual'] !== 0) {
          if (!formStore.data[componentSlug + '_detail_numero']) {
            formStore.validationErrors[componentSlug + '_detail_numero'] = validationError
          }
          if (!formStore.data[componentSlug + '_detail_code_postal']) {
            formStore.validationErrors[componentSlug + '_detail_code_postal'] = validationError
          }
          if (!formStore.data[componentSlug + '_detail_commune']) {
            formStore.validationErrors[componentSlug + '_detail_commune'] = validationError
          }
        }
      }
      // vérification du code postal
      if (formStore.data[componentSlug + '_detail_code_postal'] && !/^\d{5}$/.test(formStore.data[componentSlug + '_detail_code_postal'])) {
        formStore.validationErrors[componentSlug + '_detail_code_postal'] = 'Le code postal doit être composé de 5 chiffres'
      }

    },
    updateFormData (slug: string, value: any) {
      this.formStore.data[slug] = value
    },
    async handleClickComponent (type:string, param:string, param2:string) {
      if (type === 'link') {
        window.location.href = param
      } else if (type === 'show') {
        this.showComponentBySlug(param, param2)
      } else if (type === 'toggle') {
        this.toggleComponentBySlug(param, param2)
      } else if (type === 'archive') {
        requests.archiveDraft(this.gotoHomepage)
      } else if (type.includes('goto')) {
        await this.showScreenBySlug(param, param2, type.includes('save'), type.includes('checkloc'))
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
      const componentToToggle = document.querySelector('#' + slug + ' button')
      if (componentToToggle) {
        (componentToToggle as HTMLButtonElement).disabled = (isVisible !== '1')
      }
    },
    async showScreenBySlug (slug: string, slugButton:string, isSaveAndCheck:boolean, isCheckLocation:boolean) {
      formStore.validationErrors = {}

      if (isSaveAndCheck || isCheckLocation) {
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
        await this.changeEvent(slug, isSaveAndCheck, isCheckLocation)
      }
    },
    async navigateToDisorderScreen (action: string, slugButton:string, isSaveAndCheck:boolean) {
      if (action === 'findNextScreen') {
        const index = formStore.data.currentStep.includes('batiment') ? this.currentDisorderIndex.batiment : this.currentDisorderIndex.logement
        const { currentCategory, incrementIndex, nextScreenSlug } = findNextScreen(formStore, index, slugButton)
        await this.showScreenBySlug(nextScreenSlug, slugButton, isSaveAndCheck, false)
        if (Object.keys(formStore.validationErrors).length === 0) {
          this.currentDisorderIndex[currentCategory] = incrementIndex
        }
      }

      if (action === 'findPreviousScreen') {
        const index = formStore.data.currentStep.includes('batiment') ? this.currentDisorderIndex.batiment : this.currentDisorderIndex.logement
        const { currentCategory, decrementIndex, previousScreenSlug } = findPreviousScreen(formStore, index)
        await this.showScreenBySlug(previousScreenSlug, slugButton, isSaveAndCheck, false)

        this.currentDisorderIndex[currentCategory] = decrementIndex < 0 ? 0 : decrementIndex
      }
    },
    gotoHomepage () {
      window.location.href = '/'
    }
  }
})
</script>

<style>
  @media (max-width: 48em) {
    .form-screen-body {
      margin-bottom: 7.5rem !important;
    }

    .form-screen-body-margin {
      margin-bottom: 10rem !important;
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

  .fr-span-highlight-batiment {
    /* équivalent de --orange-terre-battue-main-645 en rgb */
    background-color: rgb(228, 121, 74, 0.25);
  }
  .fr-span-highlight-logement {
    /* équivalent de --blue-france-main-525 en rgb */
    background-color: rgb(106, 106, 244, 0.25);
  }
</style>
