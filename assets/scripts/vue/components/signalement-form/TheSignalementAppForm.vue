<template>
    <div
      id="app-signalement-form"
      class="signalement-form"
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
              <h1 class="fr-h6">Mon signalement</h1>
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
              <button type="submit" class="fr-hidden" hidden aria-hidden="true">Submit</button>
            </form>
          </div>
          <SignalementFormModal
            v-model="isTerritoryModalOpen"
            id="check_territory_modal"
            :label="territoryModalLabel"
            :description="territoryModalDescription"
          />
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
import { matomo } from './matomo'
import { requests } from './requests'
import * as Sentry from '@sentry/browser'
import { profileUpdater } from './services/profileUpdater'
import SignalementFormScreen from './components/SignalementFormScreen.vue'
import SignalementFormBreadCrumbs from './components/SignalementFormBreadCrumbs.vue'
import SignalementFormModal from './components/SignalementFormModal.vue'
import SignalementFormModalAlreadyExists from './components/SignalementFormModalAlreadyExists.vue'
const initElements:any = document.querySelector('#app-signalement-form-container')

export default defineComponent({
  name: 'TheSignalementAppForm',
  components: {
    SignalementFormScreen,
    SignalementFormBreadCrumbs,
    SignalementFormModal,
    SignalementFormModalAlreadyExists
  },
  data () {
    return {
      slugCommonProfil: ['introduction', 'adresse_logement_intro', 'adresse_logement', 'signalement_concerne', 'draft_mail', 'lien_suivi_mail'],
      slugCoordonnees: ['vos_coordonnees_occupant', 'vos_coordonnees_tiers'],
      nextSlug: '',
      isErrorInit: false,
      isLoadingInit: true,
      isIntroSkipped: false,
      formStore,
      dictionaryStore,
      matomo,
      sharedProps: formStore.props,
      isTerritoryModalOpen: false,
      territoryModalLabel: '',
      territoryModalDescription: ''
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
      this.sharedProps.initProfile = initElements.dataset.initProfile
      if (initElements.dataset.ajaxurlGetSignalementDraft !== undefined) {
        this.sharedProps.ajaxurlGetSignalementDraft = initElements.dataset.ajaxurlGetSignalementDraft
        requests.initWithExistingData(this.handleInitData)
      } else {
        this.initProfile()
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
    initProfile () {
      switch (this.sharedProps.initProfile) {
        case 'locataire':
          formStore.data.signalement_concerne_profil = 'logement_occupez'
          formStore.data.signalement_concerne_profil_detail_occupant = 'locataire'
          break
        case 'locataire_parc_prive':
          formStore.data.signalement_concerne_profil = 'logement_occupez'
          formStore.data.signalement_concerne_profil_detail_occupant = 'locataire'
          formStore.data.signalement_concerne_logement_social_autre_tiers = 'non'
          break
        case 'locataire_parc_public':
          formStore.data.signalement_concerne_profil = 'logement_occupez'
          formStore.data.signalement_concerne_profil_detail_occupant = 'locataire'
          formStore.data.signalement_concerne_logement_social_autre_tiers = 'oui'
          break
        case 'bailleur_occupant':
          formStore.data.signalement_concerne_profil = 'logement_occupez'
          formStore.data.signalement_concerne_profil_detail_occupant = 'bailleur_occupant'
          formStore.data.signalement_concerne_profil_detail_bailleur_proprietaire = 'particulier'
          break
        case 'tiers_particulier':
          formStore.data.signalement_concerne_profil = 'autre_logement'
          formStore.data.signalement_concerne_profil_detail_tiers = 'tiers_particulier'
          break
        case 'tiers_pro':
          formStore.data.signalement_concerne_profil = 'autre_logement'
          formStore.data.signalement_concerne_profil_detail_tiers = 'tiers_pro'
          break
        case 'bailleur':
          formStore.data.signalement_concerne_profil = 'autre_logement'
          formStore.data.signalement_concerne_profil_detail_tiers = 'bailleur'
          break
        case 'service_secours':
          formStore.data.signalement_concerne_profil = 'autre_logement'
          formStore.data.signalement_concerne_profil_detail_tiers = 'service_secours'
          break
        case 'bailleur_social':
          formStore.data.signalement_concerne_profil = 'autre_logement'
          formStore.data.signalement_concerne_profil_detail_tiers = 'bailleur'
          formStore.data.signalement_concerne_profil_detail_bailleur_bailleur = 'organisme_societe'
          formStore.data.signalement_concerne_logement_social_autre_tiers = 'oui'
          break
      }
      if (this.sharedProps.initProfile !== '' && formStore.data.signalement_concerne_profil != undefined) {
        this.isIntroSkipped = true
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
          let screenIndex = this.isIntroSkipped ? 1 : 0
          formStore.currentScreen = requestResponse[screenIndex]
        }
      }
    },
    saveAndChangeScreenBySlug (slug:string, isSaveAndCheck:boolean, isCheckLocation:boolean = false) {
      this.nextSlug = slug
      this.removeNextScreensIfProfileUpdated()
      if (isSaveAndCheck) {
        if (formStore.data.uuidSignalementDraft === '') {
          requests.checkIfAlreadyExists(this.showDraftModalOrNot)
        } else {
          requests.saveSignalementDraft(this.changeScreenBySlug)
        }
      } else if (isCheckLocation) {
        if (formStore.data.adresse_logement_adresse_detail_need_refresh_insee) {
          const search = formStore.data.adresse_logement_adresse_detail_code_postal + ' ' + formStore.data.adresse_logement_adresse_detail_commune
          requests.validateAddress(search, this.handleValidateAddress)
        } else {
          this.checkTerritory(
            formStore.data.adresse_logement_adresse_detail_code_postal,
            formStore.data.adresse_logement_adresse_detail_insee
          )
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
          formStore.alreadyExists.hasCreatedRecently = requestResponse.has_created_recently
          formStore.alreadyExists.createdAt = requestResponse.created_at
          formStore.alreadyExists.updatedAt = requestResponse.updated_at
          formStore.alreadyExists.draftExists = requestResponse.draft_exists
          if (formStore.alreadyExists.type === 'signalement') {
            matomo.pushEvent('showModal', 'Signalement existant')
          }
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
    handleValidateAddress (requestResponse: any) {
      // Si le code postal / la commune ont été édités à la main, on valide l'ouverture du territoire
      if (requestResponse.features !== undefined) {
        const suggestions = requestResponse.features
        if (suggestions[0] !== undefined) {
          formStore.data.adresse_logement_adresse_detail_commune = suggestions[0].properties.city
          formStore.data.adresse_logement_adresse_detail_insee = suggestions[0].properties.citycode
          formStore.data.adresse_logement_adresse = formStore.data.adresse_logement_adresse_detail_numero + ' ' + formStore.data.adresse_logement_adresse_detail_code_postal + ' ' + formStore.data.adresse_logement_adresse_detail_commune
          formStore.data.adresse_logement_adresse_suggestion = formStore.data.adresse_logement_adresse_detail_numero + ' ' + formStore.data.adresse_logement_adresse_detail_code_postal + ' ' + formStore.data.adresse_logement_adresse_detail_commune

          this.checkTerritory(
            formStore.data.adresse_logement_adresse_detail_code_postal,
            formStore.data.adresse_logement_adresse_detail_insee
          )
        }
      }
    },
    checkTerritory (postCode: any, cityCode: any) {
      requests.checkTerritory(
        postCode,
        cityCode,
        this.handleTerritoryChecked
      )
    },
    handleTerritoryChecked (requestResponse: any) {
      formStore.lastButtonClicked = ''
      if (requestResponse.success) {
        this.changeScreenBySlug(undefined)
      } else {
        this.territoryModalLabel = requestResponse.label
        this.territoryModalDescription = requestResponse.message
        this.isTerritoryModalOpen = true
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
          matomo.pushEvent('changeScreen', formStore.data.currentStep)
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
</style>
