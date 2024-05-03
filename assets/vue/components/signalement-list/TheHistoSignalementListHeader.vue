<template>
  <div class="fr-grid-row fr-mb-1w">
    <div class="fr-col fr-col-md-9">
      <h2>{{ total }} signalement{{total > 1 ? 's' : ''}} trouv{{total > 1 ? 'és' : 'é'}}</h2>
    </div>
  </div>
  <div class="fr-grid-row fr-mb-1w">
    <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
      <HistoSelect
          id="order-type"
          v-model="sharedState.input.order"
          @update:modelValue="onChange(false)"
          inner-label="Trier par"
          :option-items=orderList
      />
    </div>
    <div class="fr-col-12 fr-col-lg-6 fr-col-xl-9 fr-text--right">
      <a :href="`${sharedProps.ajaxurlExportCsv}`" class="fr-btn fr-btn--secondary fr-btn--icon-left fr-icon-download-fill">
        Exporter les résultats
      </a>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import HistoSelect from '../common/HistoSelect.vue'

export default defineComponent({
  name: 'TheHistoSignalementListHeader',
  components: {
    HistoSelect
  },
  props: {
    total: {
      type: Number,
      required: true
    },
    onChange: { type: Function }
  },
  data () {
    return {
      sharedState: store.state,
      sharedProps: store.props,

      orderList: [
        { Id: 'reference-DESC', Text: 'Ordre décroissant : par référence' },
        { Id: 'reference-ASC', Text: 'Ordre croissant : par référence' },
        { Id: 'nomOccupant-ASC', Text: 'Ordre alphabétique (A -> Z)' },
        { Id: 'nomOccupant-DESC', Text: 'Ordre alphabétique inversé (Z -> A)' },
        { Id: 'createdAt-DESC', Text: 'Le plus récent' },
        { Id: 'createdAt-ASC', Text: 'Le plus ancien' }
      ]
    }
  }
})
</script>
