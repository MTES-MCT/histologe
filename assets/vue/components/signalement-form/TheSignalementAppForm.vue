<template>
    <div
      id="app-signalement-form"
      class="signalement-form"
      :data-ajaxurl="sharedProps.ajaxurl"
      :data-ajaxurl-questions="sharedProps.ajaxurlQuestions"
      :data-ajaxurl-post-signalement-draft="sharedProps.ajaxurlPostSignalementDraft"
      :data-ajaxurl-put-signalement-draft="sharedProps.ajaxurlPutSignalementDraft"
      :data-ajaxurl-get-signalement-draft="sharedProps.ajaxurlGetSignalementDraft"
      >
      <div v-if="isLoadingInit" class="loading fr-m-10w">
        Initialisation du formulaire...

        <div v-if="isErrorInit" class="fr-my-5w">
          Erreur lors de l'initialisation du formulaire.<br><br>
          Veuillez recharger la page ou nous prévenir via le formulaire de contact.
        </div>
      </div>

      <div v-else-if="currentScreen">
        <SignalementFormBreadCrumbs
          :currentStep="currentScreen.label"
        />
        <SignalementFormScreen
          :label="currentScreen.label"
          :description="currentScreen.description"
          :components="currentScreen.components"
          :changeEvent="saveAndChangeScreenBySlug"
        />
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
      currentScreen: null as { slug: string; label: string; description: string ; components: Components } | null
    }
  },
  created () {
    if (initElements !== null) {
      this.sharedProps.ajaxurl = initElements.dataset.ajaxurl
      this.sharedProps.ajaxurlQuestions = initElements.dataset.ajaxurlQuestions
      this.sharedProps.ajaxurlPostSignalementDraft = initElements.dataset.ajaxurlPostSignalementDraft
      this.sharedProps.ajaxurlPutSignalementDraft = initElements.dataset.ajaxurlPutSignalementDraft
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
          }
        }
      }
    }
  }
})
</script>

<style>
  .signalement-form {
    background-color: white;
  }
  .btn-link {
    background-color: white;
    color: var(--artwork-minor-blue-cumulus);
    text-decoration: underline;
  }
</style>
