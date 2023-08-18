<template>
    <div
      id="app-signalement-form"
      class="signalement-form"
      :data-ajaxurl="sharedProps.ajaxurl"
      :data-ajaxurl-questions="sharedProps.ajaxurlQuestions"
      :data-ajaxurl-post-signalement-draft="sharedProps.ajaxurlPostSignalementDraft"
      :data-ajaxurl-put-signalement-draft="sharedProps.ajaxurlPutSignalementDraft"
      :data-ajaxurl-handle-upload="sharedProps.ajaxurlHandleUpload"
      :data-ajaxurl-get-signalement-draft="sharedProps.ajaxurlGetSignalementDraft"
      >
      <div v-if="isLoadingInit" class="loading fr-m-10w">
        Initialisation du formulaire...

        <div v-if="isErrorInit" class="fr-my-5w">
          Erreur lors de l'initialisation du formulaire.<br><br>
          Veuillez recharger la page ou nous prévenir via le formulaire de contact.
        </div>
      </div>

      <div v-else-if="currentScreen" class="fr-container">
        <div class="fr-grid-row fr-grid-row--gutters">
          <div
            v-if="!currentScreen.desktopIllustration"
            class="fr-col-12 fr-col-md-4"
            >
              <SignalementFormBreadCrumbs :currentStep="currentScreen.label" />
          </div>
          <div class="fr-col-12 fr-col-md-8">
            <SignalementFormScreen
              :label="currentScreen.label"
              :description="currentScreen.description"
              :desktopIllustration="currentScreen.desktopIllustration"
              :components="currentScreen.components"
              :changeEvent="saveAndChangeScreenBySlug"
              />
          </div>
          <div
            v-if="currentScreen.desktopIllustration"
            class="fr-hidden fr-unhidden-md fr-col-12 fr-col-md-4 desktop-illustration"
            >
              <img :src="currentScreen.desktopIllustration">
          </div>
        </div>
      </div>
    </div>
</template>

<script lang="ts">
// import screenData from './exemple_socle.json'
import { defineComponent } from 'vue'
import formStore from './store'
import { requests } from './requests'
import { services } from './services'
import SignalementFormScreen from './components/SignalementFormScreen.vue'
import SignalementFormBreadCrumbs from './components/SignalementFormBreadCrumbs.vue'
const initElements:any = document.querySelector('#app-signalement-form')
// TODO : centraliser les interfaces et les utiliser partout
interface Components {
  body?: any[];
  footer?: any[];
}

export default defineComponent({
  name: 'TheSignalementAppForm',
  components: {
    SignalementFormScreen,
    SignalementFormBreadCrumbs
  },
  data () {
    return {
      slugCoordonnees: ['vos_coordonnees_occupant', 'vos_coordonnees_tiers'],
      nextSlug: '',
      isErrorInit: false,
      isLoadingInit: true,
      formStore,
      sharedProps: formStore.props,
      currentScreen: null as { slug: string; label: string; description: string; desktopIllustration: string; components: Components } | null
    }
  },
  created () {
    if (initElements !== null) {
      this.sharedProps.ajaxurl = initElements.dataset.ajaxurl
      this.sharedProps.ajaxurlQuestions = initElements.dataset.ajaxurlQuestions
      this.sharedProps.ajaxurlPostSignalementDraft = initElements.dataset.ajaxurlPostSignalementDraft
      this.sharedProps.ajaxurlPutSignalementDraft = initElements.dataset.ajaxurlPutSignalementDraft
      this.sharedProps.ajaxurlHandleUpload = initElements.dataset.ajaxurlHandleUpload
      if (initElements.dataset.ajaxurlGetSignalementDraft !== undefined) {
        this.sharedProps.ajaxurlGetSignalementDraft = initElements.dataset.ajaxurlGetSignalementDraft
        requests.initWithExistingData(this.handleInitData)
      } else {
        requests.initQuestions(this.handleInitQuestions)
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
      }
      if (formStore.data.currentStep.split(':')[1] !== undefined) {
        this.nextSlug = formStore.data.currentStep.split(':')[1]
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
          this.changeScreenBySlug('rien') // TODO : que mettre ?
        } else {
          this.currentScreen = requestResponse[0]
        }
      }
    },
    saveAndChangeScreenBySlug (slug:string) {
      this.nextSlug = slug
      requests.saveSignalementDraft(this.changeScreenBySlug)
    },
    changeScreenBySlug (requestResponse: any) {
      // si on reçoit un uuid on l'enregistre pour les mises à jour
      if (requestResponse.uuid) {
        formStore.data.uuidSignalementDraft = requestResponse.uuid
      }
      if (formStore.screenData) {
        const screenIndex = formStore.screenData.findIndex((screen: any) => screen.slug === this.nextSlug)
        if (screenIndex !== -1) {
          formStore.currentScreenIndex = screenIndex
          this.currentScreen = formStore.screenData[screenIndex]
          formStore.data.currentStep = formStore.currentScreenIndex + ':' + this.currentScreen?.slug
          if (this.currentScreen?.components && this.currentScreen.components.body) {
            // Prétraitement des composants avec repeat
            this.currentScreen.components.body = formStore.preprocessScreen(this.currentScreen.components.body)
          }
        } else {
          if (this.slugCoordonnees.includes(this.nextSlug)) { // TODO à mettre à jour suivant le slug des différents profils
            // on détermine le profil
            services.updateProfil()
            // on fait un appel API pour charger la suite des questions avant de changer d'écran
            requests.initQuestionsProfil(this.handleInitQuestions)
          } else {
            // on fait un appel API pour charger la suite des questions avant de changer d'écran
            requests.initQuestionsProfil(this.handleInitQuestions)
          }
        }
      }
    }
  }
})
</script>

<style>
  .fr-header, .fr-footer {
    display: none;
  }
  @media (min-width: 48em) {
    .fr-header, .fr-footer {
      display: inherit;
    }
  }
  .signalement-form {
    background-color: white;
  }
  .btn-link {
    background-color: white;
    color: var(--artwork-minor-blue-cumulus);
    text-decoration: underline;
  }
</style>
