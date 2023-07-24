<template>
    <div
      id="app-signalement-form"
      class="signalement-form"
      :data-ajaxurl="sharedProps.ajaxurl"
      >
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
</template>

<script lang="ts">
import screenData from './exemple_socle.json'
import { defineComponent } from 'vue'
import formStore from './store'
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
    const currentScreen = screenData[formStore.currentScreenIndex]
    return {
      sharedProps: formStore.props,
      currentScreen
    }
  },
  created () {
    formStore.screenData = screenData
    if (initElements !== null) {
      this.sharedProps.ajaxurl = initElements.dataset.ajaxurl
    }
  },
  methods: {
    changeScreenBySlug (slug:string) {
      if (formStore.screenData) {
        const screenIndex = formStore.screenData.findIndex((screen) => screen.slug === slug)
        if (screenIndex !== -1) {
          formStore.currentScreenIndex = screenIndex
          this.currentScreen = formStore.screenData[screenIndex]
        }
      }
    }
  }
})
</script>

<style>
  .signalement-form {
    background-color: '#FFF';
  }
</style>
