<template>
    <div
      id="app-signalement-form"
      class="signalement-form fr-p-5w"
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
      <SignalementFormScreen
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
import SignalementFormScreen from './SignalementFormScreen.vue'
const initElements:any = document.querySelector('#app-signalement-form')

export default defineComponent({
  name: 'TheSignalementAppForm',
  components: {
    SignalementFormScreen
  },
  data () {
    return {
      isErrorInit: false,
      isLoadingInit: true,
      sharedProps: formStore.props,
      // sharedState: formStore.data,
      screens: [] as Array<{ slug: string; label: string; description: string; components: object }>,
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
        this.screens = requestResponse
        this.currentScreen = requestResponse[0]
      }
    },
    changeScreenBySlug (slug:string) {
      for (const screen of this.screens) {
        if (screen.slug === slug) {
          this.currentScreen = screen
          break
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
