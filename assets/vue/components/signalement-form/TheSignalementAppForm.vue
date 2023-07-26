<template>
    <div
      id="app-signalement-form"
      class="signalement-form"
      :data-ajaxurl="sharedProps.ajaxurl"
      :data-ajaxurl-questions="sharedProps.ajaxurlQuestions"
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
        class="fr-p-5w"
        :label="currentScreen.label"
        :description="currentScreen.description"
        :components="currentScreen.components"
        :changeEvent="changeScreenBySlug"
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
import SignalementFormScreen from './SignalementFormScreen.vue'
import SignalementFormBreadCrumbs from './SignalementFormBreadCrumbs.vue'
const initElements:any = document.querySelector('#app-signalement-form')

export default defineComponent({
  name: 'TheSignalementAppForm',
  components: {
    SignalementFormScreen,
    SignalementFormBreadCrumbs
  },
  data () {
    return {
      slugVosCoordonnees: 'vos_coordonnees_occupant',
      nextSlug: '',
      isErrorInit: false,
      isLoadingInit: true,
      sharedProps: formStore.props,
      currentScreen: null as { slug: string; label: string; description: string ; components: object } | null
    }
  },
  created () {
    if (initElements !== null) {
      this.sharedProps.ajaxurl = initElements.dataset.ajaxurl
      this.sharedProps.ajaxurlQuestions = initElements.dataset.ajaxurlQuestions
      requests.initQuestions(this.handleInitQuestions)
    } else {
      this.isErrorInit = true
    }
  },
  methods: {
    handleInitQuestions (requestResponse: any) {
      if (requestResponse === 'error') {
        this.isErrorInit = true
      } else {
        this.isLoadingInit = false
        formStore.screenData = formStore.screenData.concat(requestResponse)
        if (this.nextSlug !== '') {
          this.changeScreenBySlug(this.nextSlug)
        } else {
          this.currentScreen = requestResponse[0]
        }
      }
    },
    changeScreenBySlug (slug:string) {
      if (formStore.screenData) {
        const screenIndex = formStore.screenData.findIndex((screen: any) => screen.slug === slug)
        if (screenIndex !== -1) {
          formStore.currentScreenIndex = screenIndex
          this.currentScreen = formStore.screenData[screenIndex]
        } else {
          if (slug === this.slugVosCoordonnees) { // TODO à mettre à jour suivant le slug des différents profils
            // on détermine le profil
            services.updateProfil()
            this.nextSlug = slug
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
