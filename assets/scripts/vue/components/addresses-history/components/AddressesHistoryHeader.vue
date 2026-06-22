<template>
  <div class="fr-grid-row fr-mb-1w">
    <div class="fr-col-12 fr-col-lg-6 fr-col-xl-8">
        Affichage :
        <ul class="fr-btns-group fr-btns-group--center fr-btns-group--inline-lg fr-btns-group--icon-left">
            <li>
                <button :class="['fr-btn', 'fr-icon-road-map-line', sharedState.viewMode === 'map' ? '' : 'fr-btn--tertiary']" type="button" @click="onChange('map')">
                    Carte
                </button>
            </li>
            <li>
                <button :class="['fr-btn', 'fr-icon-list-unordered', sharedState.viewMode === 'list' ? '' : 'fr-btn--tertiary']" type="button" @click="onChange('list')">
                    Liste
                </button>
            </li>
        </ul>
    </div>
    <div class="fr-col-12 fr-col-lg-6 fr-col-xl-4 fr-text--right">
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
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from '../store'

export default defineComponent({
  name: 'AddressesHistoryHeader',
  props: {
    total: {
      type: Number,
      required: true
    }
  },
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
