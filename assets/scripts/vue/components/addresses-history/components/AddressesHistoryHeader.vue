<template>
  <section class="fr-grid-row fr-mb-1w fr-container-sml">
    <div class="fr-col-12 fr-col-lg-6 fr-col-xl-8 fr-my-2v fr-my-md-0 fr-grid-row fr-grid-row--middle">
        <div class="fr-mr-4v">Affichage :</div>
        <div class="fr-mr-2v">
          <button :class="['fr-btn', 'fr-btn--icon-left', 'fr-icon-road-map-line', sharedState.viewMode === 'map' ? '' : 'fr-btn--tertiary']" type="button" @click="onChange('map')">
              Carte
          </button>
        </div>
        <div>
          <button :class="['fr-btn', 'fr-btn--icon-left', 'fr-icon-list-unordered', sharedState.viewMode === 'list' ? '' : 'fr-btn--tertiary']" type="button" @click="onChange('list')">
              Liste
          </button>
        </div>
    </div>
    <div class="fr-col-12 fr-col-lg-6 fr-col-xl-4 fr-mb-2v fr-mb-md-0 fr-text--right">
      <a :href="canExport ? `${sharedProps.ajaxurlExportCsv}` : undefined"
         :class="[
              'fr-btn',
              'fr-btn--secondary',
              'fr-btn--icon-left',
              'fr-icon-download-fill',
              'fr-btn--block',
              'fr-btn--md-inline',
              { 'fr-label--disabled': !canExport }]"
          >Exporter les résultats</a>
    </div>
  </section>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from '../store'

export default defineComponent({
  name: 'AddressesHistoryHeader',
  emits: ['change'],
  data () {
    return {
      sharedState: store.state,
      sharedProps: store.props
    }
  },
  computed: {
    canExport () {
      return false
      /*
      return Object.entries(this.sharedState.input.filters).some(
        ([key, value]) => key !== 'isImported' && (value !== null && value !== undefined && !(Array.isArray(value) && value.length === 0))
      ) && this.total > 0
       */
    }
  },
  methods: {
    onChange(pageName: string) {
      this.$emit('change', pageName)
    }
  }
})
</script>
<style>
</style>
