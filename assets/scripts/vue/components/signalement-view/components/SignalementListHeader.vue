<template>
  <div class="fr-grid-row fr-mb-1w">
    <div class="fr-col fr-col-md-9">
      <h2>{{ sharedState.selectedSavedSearchId !== undefined ? 'Résultats de la recherche sauvegardée - ' : '' }}{{ total }} signalement{{total > 1 ? 's' : ''}} trouv{{total > 1 ? 'és' : 'é'}}</h2>
    </div>
  </div>
  <div class="fr-grid-row fr-mb-1w">
    <div class="fr-col-12 fr-col-lg-6 fr-col-xl-4">
      <label for="order-type" class="fr-mr-1w">Trier par :</label>
      <HistoSelect
          id="order-type"
          v-model="sharedState.input.order"
          @update:modelValue="onChange(false)"
          :option-items=orderList
      />
    </div>
    <div v-if="sharedState.user.isAdmin" class="fr-col-12 fr-col-lg-6 fr-col-xl-8 fr-text--right">
      <a :href="canExport ? `${sharedProps.ajaxurlExportCsv}` : undefined"
         :class="[
              'fr-btn',
              'fr-btn--secondary',
              'fr-btn--icon-left',
              'fr-icon-download-fill',
              { 'fr-label--disabled': !canExport }]"
      >
        Exporter les résultats
      </a>
    </div>
    <div v-if="!sharedState.user.isAdmin" class="fr-col-12 fr-col-lg-6 fr-col-xl-8 fr-text--right">
      <a :href="(total > 0) ? `${sharedProps.ajaxurlExportCsv}` : undefined"
         :class="[
              'fr-btn',
              'fr-btn--secondary',
              'fr-btn--icon-left',
              'fr-icon-download-fill',
              { 'fr-label--disabled': (total == 0) }]"
      >
        Exporter les résultats
      </a>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from '../store'
import HistoSelect from '../../common/HistoSelect.vue'

export default defineComponent({
  name: 'SignalementListHeader',
  components: {
    HistoSelect
  },
  mounted () {
    const urlParams = new URLSearchParams(window.location.search)
    const sortBy = urlParams.get('sortBy')
    const direction = urlParams.get('direction')
    this.sharedState.input.order = 'reference-DESC'
    if (sortBy && direction) {
      const orderValue = `${sortBy}-${direction.toUpperCase()}`
      if (this.orderList.some(item => item.Id === orderValue)) {
        this.sharedState.input.order = orderValue
      }
    }
  },
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
      sharedProps: store.props,

      orderList: [
        { Id: 'reference-DESC', Text: 'Ordre décroissant' },
        { Id: 'reference-ASC', Text: 'Ordre croissant' },
        { Id: 'nomOccupant-ASC', Text: 'Ordre alphabétique (A -> Z)' },
        { Id: 'nomOccupant-DESC', Text: 'Ordre alphabétique inversé (Z -> A)' },
        { Id: 'lastSuiviAt-DESC', Text: 'Suivi le plus récent' },
        { Id: 'lastSuiviAt-ASC', Text: 'Suivi le plus ancien' },
        { Id: 'villeOccupant-ASC', Text: 'Nom de commune (A -> Z)' },
        { Id: 'villeOccupant-DESC', Text: 'Nom de commune (Z -> A)' },
        { Id: 'createdAt-DESC', Text: 'Date la plus récente (du plus récent au plus ancien)' },
        { Id: 'createdAt-ASC', Text: 'Date la plus ancienne (de la plus ancienne à la plus récente)' }
      ]
    }
  },
  computed: {
    canExport () {
      return Object.entries(this.sharedState.input.filters).some(
        ([key, value]) => key !== 'isImported' && (value !== null && value !== undefined && !(Array.isArray(value) && value.length === 0))
      ) && this.total > 0
    }
  },
  methods: {
    onChange(refresh: boolean) {
      this.$emit('change', refresh)
    }
  }
})
</script>
<style>
  #order-type {
    width: max-content;
    display: inline-block;
  }
</style>
