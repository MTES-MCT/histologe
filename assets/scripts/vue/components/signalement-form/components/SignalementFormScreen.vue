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
    <div v-if="components != undefined">
      <template v-if="shouldAddFieldset()">
        <fieldset class="fr-fieldset" id="checkboxes">
          <SignalementFormComponentGenerator
            :componentList="components.body"
            :handleClickComponent="handleClickComponent"
          />
        </fieldset>
      </template>
      <template v-else>
        <SignalementFormComponentGenerator
          :componentList="components.body"
          :handleClickComponent="handleClickComponent"
        />
      </template>
    </div>
  </div>
  <div
    v-if="components !== undefined && components.footer !== undefined && components.footer.length > 0"
    class="fr-container form-screen-footer"
    >
    <div>
      <SignalementFormComponentGenerator
        :componentList="components.footer"
        :handleClickComponent="handleClickComponent"
      />
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'
import { requests } from '../requests'
import { variablesReplacer } from './../services/variableReplacer'
import { componentValidator } from './../services/componentValidator'
import { findPreviousScreen, findNextScreen } from '../services/disorderScreenNavigator'
import SignalementFormComponentGenerator from './SignalementFormComponentGenerator.vue'

export default defineComponent({
  name: 'SignalementFormScreen',
  components: {
    SignalementFormComponentGenerator
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
        // Les autres composants requis doivent avoir une valeur correspondante dans le Store
        if (this.isRequired(component) && !value && component.type !== 'SignalementFormAddress') {
          formStore.validationErrors[component.slug] = 'Ce champ est requis'
        }

        componentValidator.validate(component)

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
        componentToShow.removeAttribute('aria-hidden')
        componentToShow.removeAttribute('hidden')
      }
      const buttonToHide = document.querySelector('#' + slugButton)
      if (buttonToHide) {
        buttonToHide.classList.add('fr-hidden')
        buttonToHide.setAttribute('aria-hidden', 'true')
        buttonToHide.setAttribute('hidden', 'true')
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
            this.$nextTick(() => {
              // Tableau contenant toutes les classes d'erreur possibles
              const errorClasses = [
                'signalement-form-roomlist-error',
                'fr-fieldset--error',
                'fr-input--error',
                'fr-select--error',
                'fr-checkbox-group--error',
                'fr-input-group--error',
                'custom-file-input-error'
              ]
              // Construire dynamiquement le sélecteur pour toutes les classes d'erreur
              const selectors = errorClasses.map(errorClass => `.${errorClass}`).join(', ')

              // Sélectionner tous les éléments correspondant aux classes d'erreur pour tester
              const ancestorsWithError = document.querySelectorAll(selectors)
              if (ancestorsWithError.length > 0) {
                // Sélectionner le premier input ou textarea dans le premier élément trouvé
                const firstAncestorWithError = ancestorsWithError[0]
                const inputElement = firstAncestorWithError.querySelector('input, textarea') as HTMLElement
                // Si un élément input/textarea est trouvé, mettre le focus dessus
                if (inputElement) {
                  inputElement.focus()
                  inputElement.scrollIntoView({ behavior: 'smooth', block: 'center' })
                }
              }
            })
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
    },
    shouldAddFieldset () {
      if (formStore.currentScreen &&
          formStore.currentScreen.components &&
          formStore.currentScreen.components.body &&
          formStore.currentScreen.components?.body?.length > 0
      ) {
        if (formStore.currentScreen.slug.startsWith('desordres_batiment_') ||
            formStore.currentScreen.slug.startsWith('desordres_logement_') ||
            formStore.currentScreen.slug === 'utilisation_service'
        ) {
          return true
        }
      }
      return false
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
