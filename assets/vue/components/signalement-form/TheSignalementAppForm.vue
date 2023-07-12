<template>
    <div
      id="app-signalement-form"
      class="signalement-form fr-pt-5w"
      :data-ajaxurl="sharedProps.ajaxurl"
      >
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
import { store } from './store'
// import { requests } from './requests'
import SignalementFormScreen from './SignalementFormScreen.vue'
const initElements:any = document.querySelector('#app-front-stats')

export default defineComponent({
  name: 'TheSignalementAppForm',
  components: {
    SignalementFormScreen
  },
  data () {
    const currentScreen = screenData[0]
    return {
      screens: screenData,
      sharedState: store.state,
      sharedProps: store.props,
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
