<template>
    <div
      id="app-signalement-form"
      class="histo-app-front-stats fr-pt-5w"
      :data-ajaxurl="sharedProps.ajaxurl"
      >
      <h1>Nouveau formulaire de signalement</h1>
    </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
// import { requests } from './requests'
const initElements:any = document.querySelector('#app-front-stats')

export default defineComponent({
  name: 'TheSignalementAppForm',
  components: {
  },
  data () {
    return {
      sharedState: store.state,
      sharedProps: store.props
    }
  },
  created () {
    if (initElements !== null) {
      this.sharedProps.ajaxurl = initElements.dataset.ajaxurl
      // requests.filter(this.handleRefresh)
    // } else {
    //   alert('Error while loading front statistics')
    }
  },
  methods: {
  }
})
</script>

<style>
  .histo-app-front-stats {
    background-color: '#FFF';
  }
  .histo-app-front-stats h1 {
    text-align: center;
    color: var(--blue-france-sun-113-625);
  }

  .histo-app-stats a {
    color: var(--blue-france-sun-113-625);
  }

  .loading {
    font-size: 2rem;
  }
</style>
