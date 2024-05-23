<template>
    <div
      id="app-signalement-form"
      class="signalement-form"
      :data-ajaxurl="sharedProps.ajaxurl"
      :data-ajaxurl-dictionary="sharedProps.ajaxurlDictionary"
      :data-ajaxurl-questions="sharedProps.ajaxurlQuestions"
      :data-ajaxurl-desordres="sharedProps.ajaxurlDesordres"
      :data-ajaxurl-post-signalement-draft="sharedProps.ajaxurlPostSignalementDraft"
      :data-ajaxurl-put-signalement-draft="sharedProps.ajaxurlPutSignalementDraft"
      :data-ajaxurl-handle-upload="sharedProps.ajaxurlHandleUpload"
      :data-ajaxurl-get-signalement-draft="sharedProps.ajaxurlGetSignalementDraft"
      :data-ajaxurl-platform-name="sharedProps.platformName"
      :data-ajaxurl-check-territory="sharedProps.ajaxurlCheckTerritory"
      :data-ajaxurl-check-signalement-or-draft-already-exists="sharedProps.ajaxurlCheckSignalementOrDraftAlreadyExists"
      :data-ajaxurl-send-mail-continue-from-draft="sharedProps.ajaxurlSendMailContinueFromDraft"
      :data-ajaxurl-send-mail-get-lien-suivi="sharedProps.ajaxurlSendMailGetLienSuivi"
      :data-ajaxurl-archive-draft="sharedProps.ajaxurlArchiveDraft"
      >
      <div v-if="isLoadingInit" class="loading fr-m-10w fr-grid-row fr-grid-row--center">
        Initialisation du formulaire...

        <div v-if="isErrorInit" class="fr-my-5w">
          Erreur lors de l'initialisation du formulaire.<br><br>
          Veuillez recharger la page ou nous prévenir via le formulaire de contact.
        </div>
      </div>

      <div v-else-if="formStore.currentScreen" class="fr-container">
        <div :class="['fr-grid-row fr-grid-row--gutters', formStore.currentScreen.slug === 'introduction' ? 'fr-grid-row--center' : '']">
          <div
            v-if="formStore.currentScreen.slug !== 'introduction'"
            class="fr-col-12 fr-col-md-4"
            >
              <SignalementFormBreadCrumbs
                :clickEvent="saveAndChangeScreenBySlug"
                />
          </div>
          <div class="fr-col-12 fr-col-md-8">
            <form @submit.prevent="handleSubmit" autocomplete="on">
              <SignalementFormScreen
                :label="formStore.currentScreen.label"
                :description="formStore.currentScreen.description"
                :icon="formStore.currentScreen.icon"
                :components="formStore.currentScreen.components"
                :customCss="formStore.currentScreen.customCss"
                :changeEvent="saveAndChangeScreenBySlug"
                />
              <button type="submit" class="hidden-submit">Submit</button>
            </form>
          </div>
        </div>
        <SignalementFormModalAlreadyExists
          :mailSentEvent="saveAndChangeScreenBySlug"
          :newClickEvent="changeScreenBySlug"
        />
      </div>
    </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './store'
import dictionaryStore from './dictionary-store'
import { requests } from './requests'
import * as Sentry from '@sentry/browser'
import { profileUpdater } from './services/profileUpdater'
import SignalementFormScreen from './components/SignalementFormScreen.vue'
import SignalementFormBreadCrumbs from './components/SignalementFormBreadCrumbs.vue'
import SignalementFormModalAlreadyExists from './components/SignalementFormModalAlreadyExists.vue'
const initElements:any = document.querySelector('#app-signalement-form')

export default defineComponent({
  name: 'TheSignalementAppForm',
  components: {
    SignalementFormScreen,
    SignalementFormBreadCrumbs,
    SignalementFormModalAlreadyExists
  },
  data () {
    return {
      slugCommonProfil: ['introduction', 'adresse_logement_intro', 'adresse_logement', 'signalement_concerne', 'draft_mail', 'lien_suivi_mail'],
      slugCoordonnees: ['vos_coordonnees_occupant', 'vos_coordonnees_tiers'],
      nextSlug: '',
      isErrorInit: false,
      isLoadingInit: true,
      formStore,
      dictionaryStore,
      sharedProps: formStore.props
    }
  },
  created () {
    if (initElements !== null) {
      this.sharedProps.platformName = initElements.dataset.platformName
      this.sharedProps.ajaxurl = initElements.dataset.ajaxurl
      this.sharedProps.ajaxurlDictionary = initElements.dataset.ajaxurlDictionary
      this.sharedProps.ajaxurlQuestions = initElements.dataset.ajaxurlQuestions
      this.sharedProps.ajaxurlDesordres = initElements.dataset.ajaxurlDesordres
      this.sharedProps.ajaxurlPostSignalementDraft = initElements.dataset.ajaxurlPostSignalementDraft
      this.sharedProps.ajaxurlPutSignalementDraft = initElements.dataset.ajaxurlPutSignalementDraft
      this.sharedProps.ajaxurlHandleUpload = initElements.dataset.ajaxurlHandleUpload
      this.sharedProps.ajaxurlCheckTerritory = initElements.dataset.ajaxurlCheckTerritory
      this.sharedProps.ajaxurlCheckSignalementOrDraftAlreadyExists = initElements.dataset.ajaxurlCheckSignalementOrDraftAlreadyExists
      this.sharedProps.ajaxurlSendMailContinueFromDraft = initElements.dataset.ajaxurlSendMailContinueFromDraft
      this.sharedProps.ajaxurlSendMailGetLienSuivi = initElements.dataset.ajaxurlSendMailGetLienSuivi
      this.sharedProps.ajaxurlArchiveDraft = initElements.dataset.ajaxurlArchiveDraft
      if (initElements.dataset.ajaxurlGetSignalementDraft !== undefined) {
        this.sharedProps.ajaxurlGetSignalementDraft = initElements.dataset.ajaxurlGetSignalementDraft
        requests.initWithExistingData(this.handleInitData)
      } else {
        requests.initDictionary(this.handleInitDictionary)
      }
    } else {
      this.isErrorInit = true
    }
  },
  methods: {
    handleInitData (requestResponse: any) {
      if (requestResponse.signalement && requestResponse.signalement.payload) {
        for (const prop in requestResponse.signalement.payload) {
          formStore.data[prop] = requestResponse.signalement.payload[prop]
        }
        formStore.data.uuidSignalementDraft = requestResponse.signalement.uuid
      }
      if (formStore.data.currentStep !== undefined) {
        this.nextSlug = formStore.data.currentStep
      }
      requests.initDictionary(this.handleInitDictionary)
    },
    handleInitDictionary (requestResponse: any) {
      for (const slug in requestResponse) {
        dictionaryStore[slug] = requestResponse[slug]
      }
      requests.initQuestions(this.handleInitQuestions)
    },
    handleInitQuestions (requestResponse: any) {
      if (requestResponse === 'error') {
        this.isErrorInit = true
      } else {
        this.isLoadingInit = false
        formStore.screenData = formStore.screenData.concat(requestResponse)
        if (this.nextSlug !== '') {
          this.changeScreenBySlug(undefined) // TODO : que mettre ?
        } else {
          formStore.currentScreen = requestResponse[0]
        }
      }
    },
    saveAndChangeScreenBySlug (slug:string, isSaveAndCheck:boolean) {
      this.nextSlug = slug
      if (isSaveAndCheck) {
        if (formStore.data.uuidSignalementDraft === '') {
          requests.checkIfAlreadyExists(this.showDraftModalOrNot)
        } else {
          requests.saveSignalementDraft(this.changeScreenBySlug)
        }
      } else {
        this.changeScreenBySlug(undefined)
      }
    },
    showDraftModalOrNot (requestResponse: any) {
      if (requestResponse) {
        if (requestResponse.already_exists === true) {
          const link = document.getElementById('fr-modal-already-exists-button')
          formStore.alreadyExists.type = requestResponse.type
          formStore.alreadyExists.signalements = requestResponse.signalements
          formStore.alreadyExists.createdAt = requestResponse.created_at
          formStore.alreadyExists.updatedAt = requestResponse.updated_at
          formStore.alreadyExists.uuidDraft = requestResponse.uuid_draft
          if (link) {
            formStore.lastButtonClicked = ''
            link.click()
          } else {
            Sentry.captureException(new Error('L\'élément lien n\'a pas été trouvé'))
            console.error('L\'élément lien n\'a pas été trouvé.')
          }
        } else {
          this.changeScreenBySlug(requestResponse)
        }
      } else {
        this.changeScreenBySlug(undefined)
      }
    },
    removeNextScreensIfProfileUpdated () {
      if (formStore.data.currentStep === 'signalement_concerne') {
        const profil = formStore.data.profil
        profileUpdater.update()
        if (profil !== formStore.data.profil) {
          formStore.screenData = formStore.screenData.filter(
            (screen: any) => this.slugCommonProfil.includes(screen.slug)
          )
        }
      }
    },
    changeScreenBySlug (requestResponse: any) {
      formStore.lastButtonClicked = ''
      // si on reçoit un uuid on l'enregistre pour les mises à jour
      if (requestResponse) {
        if (requestResponse.uuid) {
          formStore.data.uuidSignalementDraft = requestResponse.uuid
        } else {
          let errorMessage = ''
          for (const index in requestResponse.violations) {
            errorMessage += requestResponse.violations[index].title + '\n'
          }
          if (errorMessage.length > 0) {
            alert(errorMessage)
          } else {
            alert('Oups... Une erreur est survenue. Nous nous excusons pour ce désagrément, nos équipes ont été prévenues. Veuillez réessayer ultérieurement ou soumettre un nouveau formulaire. Merci de votre compréhension.')
          }
          return
        }
        if (requestResponse.signalementReference) {
          formStore.data.signalementReference = requestResponse.signalementReference
        }
        if (requestResponse.lienSuivi) {
          formStore.data.lienSuivi = requestResponse.lienSuivi
        }
      }

      if (formStore.screenData) {
        this.removeNextScreensIfProfileUpdated()
        // si le signalement n'a pas pu être créé (par exemple pas de désordres) la route ne renvoie pas de référence de signalement
        // du coup on ne va pas sur la page de confirmation, mais sur une page spéciale
        if (this.nextSlug === 'confirmation_signalement' && formStore.data.signalementReference === '') {
          this.nextSlug = 'signalement_incomplet'
        }
        const nextScreen = formStore.screenData.find((screen: any) => screen.slug === this.nextSlug)
        if (nextScreen !== undefined) {
          formStore.currentScreen = nextScreen
          formStore.data.currentStep = formStore.currentScreen?.slug
          if (formStore.currentScreen?.components && formStore.currentScreen.components.body) {
            // Prétraitement des composants avec repeat
            formStore.currentScreen.components.body = formStore.preprocessScreen(formStore.currentScreen.components.body)
          }
        } else {
          if (this.slugCoordonnees.includes(this.nextSlug)) { // TODO à mettre à jour suivant le slug des différents profils
            // on fait un appel API pour charger la suite des questions avant de changer d'écran
            requests.initQuestionsProfil(this.handleInitQuestions)
          } else if (this.nextSlug.indexOf('desordres') !== -1) {
            // TODO : c'est super moche ça
            requests.initDesordresProfil(this.handleInitQuestions)
          } else {
            // on fait un appel API pour charger la suite des questions avant de changer d'écran
            // TODO : il faudrait trouver un moyen de repérer si les questions profils ont déjà été chargées, et si c'est juste un slug qui n'existe pas avec ce profil
            requests.initQuestionsProfil(this.handleInitQuestions)
          }
        }
      }

      setTimeout(() => {
        window.scrollTo(0, 0)
      }, 50)
    },
    handleSubmit () {
      // il ne se passe rien, ce bouton ne sert que pour gérer l'accessibilité et l'autocomplete
    }
  }
})
</script>

<style>
  .remove-mobile-header-footer .fr-header, .remove-mobile-header-footer .fr-footer {
    display: none;
  }
  @media (min-width: 48em) {
    .remove-mobile-header-footer .fr-header, .remove-mobile-header-footer .fr-footer {
      display: inherit;
    }
  }
  .signalement-form {
    background-color: white;
  }
  .hidden-submit {
    display: none;
  }
</style>
