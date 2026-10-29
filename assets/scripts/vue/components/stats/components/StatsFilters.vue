<template>
  <section id="stats-filters" class="stats-filters">
    <h2 class="fr-h3 fr-mb-0">Statistiques détaillées</h2>
    <p class="fr-mb-5w">
      Modifiez les filtres et / ou la plage de dates pour mettre les données à jour.
      <br>
      Par défaut, les signalements refusés ne sont pas comptabilisés.
    </p>

    <div class="fr-container--fluid">
      <div class="fr-grid-row fr-grid-row--gutters">
        <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
          <HistoMultiSelect
            id="filter-communes"
            v-model="sharedState.filters.communes"
            @update:modelValue="onChange"
            :option-items="sharedState.options.communesList"
            :active="isTerritorySelected"
            >
            <template #label>Communes</template>
          </HistoMultiSelect>
        </div>
        <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
          <HistoMultiSelect
            id="filter-epcis"
            v-model="sharedState.filters.epcis"
            @update:modelValue="onChange"
            :option-items="sharedState.options.epcisList"
            :active="isTerritorySelected"
            >
            <template #label>EPCI</template>
          </HistoMultiSelect>
        </div>
        <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
          <HistoSelect
            id="filter-statut"
            v-model="sharedState.filters.statut"
            @update:modelValue="onChange"
            :option-items="STATUS_LIST"
            >
            <template #label>Statut</template>
          </HistoSelect>
        </div>
        <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
          <HistoMultiSelect
            id="filter-etiquettes"
            v-model="sharedState.filters.etiquettes"
            @update:modelValue="onChange"
            :option-items="sharedState.options.etiquettesList"
            :active="isTerritorySelected"
            >
            <template #label>Etiquettes</template>
          </HistoMultiSelect>
        </div>
        <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
          <HistoSelect
            id="filter-type"
            v-model="sharedState.filters.type"
            @update:modelValue="onChange"
            :option-items="TYPES_LIST"
            >
            <template #label>Type</template>
          </HistoSelect>
        </div>
        <div class="fr-col-12 fr-col-lg-6 fr-col-xl-4">
          <HistoDatePicker
            id="histofiltersdatepicker"
            ref="datePicker"
            v-model="sharedState.filters.dateRange"
            @update:modelValue="onChange"
            >
            <template #label>Dates des signalements</template>
          </HistoDatePicker>
        </div>
        <div class="fr-col-12 fr-col-lg-6 fr-col-xl-5">
          <div class="fr-mb-3v">
            <HistoCheckbox
              id="count-refused"
              v-model="sharedState.filters.countRefused"
              @update:modelValue="onChange"
              >
              <template #label>Cocher la case pour comptabiliser les signalements refusés</template>
            </HistoCheckbox>
          </div>
          <HistoCheckbox
            v-if="sharedState.options.canFilterArchived"
            id="count-archived"
            v-model="sharedState.filters.countArchived"
            @update:modelValue="onChange"
            >
            <template #label>Cocher la case pour comptabiliser les signalements archivés</template>
          </HistoCheckbox>
        </div>
        <div class="fr-col-12 fr-col-lg-12 fr-col-xl-3 align-right">
          <a href="#" @click.prevent="onReset" class="fr-link"><span class="fr-fi-refresh-line"></span>Tout réinitialiser</a>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, nextTick, useTemplateRef } from 'vue'
import { store } from '../composables/useStatsStore'
import HistoSelect from '../../common/HistoSelect.vue'
import HistoMultiSelect from '../../common/HistoMultiSelect.vue'
import HistoCheckbox from '../../common/HistoCheckbox.vue'
import HistoDatePicker from '../../common/external/HistoDatePicker.vue'

const STATUS_LIST = [
  { Id: 'all', Text: 'Tous' },
  { Id: 'new', Text: 'Nouveau' },
  { Id: 'active', Text: 'En cours' },
  { Id: 'closed', Text: 'Fermé' }
]

const TYPES_LIST = [
  { Id: 'all', Text: 'Tous' },
  { Id: 'public', Text: 'Public' },
  { Id: 'private', Text: 'Privé' },
  { Id: 'unset', Text: 'Non renseigné' }
]

// Émissions
const emit = defineEmits<{
  change: []
  reset: []
}>()

// State
const sharedState = store.state
const datePicker = useTemplateRef<InstanceType<typeof HistoDatePicker>>('datePicker')

// Les filtres dépendant du territoire ne sont actifs que si un territoire est sélectionné
const isTerritorySelected = computed(() =>
  !sharedState.options.canFilterTerritoires || sharedState.filters.territoire !== 'all'
)

const onChange = (): void => {
  emit('change')
}

const onReset = async (): Promise<void> => {
  emit('reset')
  await nextTick()
  datePicker.value?.updateDate(sharedState.filters.dateRange)
}
</script>

<style>
  #stats-filters .fr-container--fluid {
    overflow: visible;
  }

  #stats-filters .align-right {
    text-align: right;
  }

  #stats-filters .fr-fi-refresh-line::before{
    margin-right: 5px;
    font-size: 1rem;
    color: var(--blue-france-sun-113-625);
    --icon-size: 1.1rem;
  }
  .histo-date-picker input {
    background-color: var(--background-contrast-grey);
    box-shadow: inset 0 -2px 0 0 var(--border-plain-grey);
    border-radius: .25rem .25rem 0 0;
  }
</style>
