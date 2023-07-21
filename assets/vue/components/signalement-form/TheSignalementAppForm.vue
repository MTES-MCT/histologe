<template>
    <div
      id="app-signalement-form"
      class="signalement-form fr-p-5w"
      :data-ajaxurl="sharedProps.ajaxurl"
      >
      <SignalementFormAriane
        :currentStep="currentScreen.label"
      />
      <SignalementFormScreen
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
// import { requests } from './requests'
import SignalementFormScreen from './SignalementFormScreen.vue'
import SignalementFormAriane from './SignalementFormAriane.vue'
const initElements:any = document.querySelector('#app-signalement-form')

export default defineComponent({
  name: 'TheSignalementAppForm',
  components: {
    SignalementFormScreen,
    SignalementFormAriane
  },
  data () {
    const currentScreen = screenData[0]
    return {
      screens: screenData,
      // sharedState: formStore.data,
      sharedProps: formStore.props,
      currentScreen
    }
  },
  created () {
    if (initElements !== null) {
      this.sharedProps.ajaxurl = initElements.dataset.ajaxurl
    }
  },
  methods: {
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
    background-color: '#FFF';
  }
</style>
