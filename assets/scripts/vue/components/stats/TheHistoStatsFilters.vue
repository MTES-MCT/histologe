<template>
  <section id="histo-stats-filters" class="histo-stats-filters">
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
            @update:modelValue="onChange(false)"
            inner-label="Communes"
            :option-items=sharedState.filters.communesList
            :active="!sharedState.filters.canFilterTerritoires || sharedState.filters.territoire !== 'all'"
            />
        </div>
        <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
          <HistoSelect
            id="filter-statut"
            v-model="sharedState.filters.statut"
            @update:modelValue="onChange(false)"
            inner-label="Statut"
            :option-items=statusList
            />
        </div>
        <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
          <HistoMultiSelect
            id="filter-etiquettes"
            v-model="sharedState.filters.etiquettes"
            @update:modelValue="onChange(false)"
            inner-label="Etiquettes"
            :option-items=sharedState.filters.etiquettesList
            :active="!sharedState.filters.canFilterTerritoires || sharedState.filters.territoire !== 'all'"
            />
        </div>
        <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
          <HistoSelect
            id="filter-type"
            v-model="sharedState.filters.type"
            @update:modelValue="onChange(false)"
            inner-label="Type"
            :option-items=typesList
            />
        </div>
        <div class="fr-col-12 fr-col-lg-6 fr-col-xl-4">
          <HistoDatePicker
            id="histofiltersdatepicker"
            ref="histofiltersdatepicker"
            v-model="sharedState.filters.dateRange"
            @update:modelValue="onChange(false)"
            />
        </div>
        <div class="fr-col-12 fr-col-lg-6 fr-col-xl-5">
          <div class="fr-mb-3v">
            <HistoCheckbox
              id="count-refused"
              v-model="sharedState.filters.countRefused"
              @update:modelValue="onChange(false)"
              >
              <template #label>Cocher la case pour comptabiliser les signalements refusés</template>
            </HistoCheckbox>
          </div>
          <HistoCheckbox
            v-if="sharedState.filters.canFilterArchived"
            id="count-archived"
            v-model="sharedState.filters.countArchived"
            @update:modelValue="onChange(false)"
            >
            <template #label>Cocher la case pour comptabiliser les signalements archivés</template>
          </HistoCheckbox>
        </div>
        <div class="fr-col-12 fr-col-lg-12 fr-col-xl-3 align-right">
          <a href="#" @click="onReinitLocalEvent"><span class="fr-fi-refresh-line"></span>Tout réinitialiser</a>
        </div>
      </div>
    </div>
  </section>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import HistoSelect from '../common/HistoSelect.vue'
import HistoMultiSelect from '../common/HistoMultiSelect.vue'
import HistoCheckbox from '../common/HistoCheckbox.vue'
import HistoDatePicker from '../common/external/HistoDatePicker.vue'

export default defineComponent({
  name: 'TheHistoStatsFilters',
  props: {
    onChange: { type: Function }
  },
  components: {
    HistoSelect,
    HistoMultiSelect,
    HistoCheckbox,
    HistoDatePicker
  },
  data () {
    const etiquettes = new Array<string>()
    for (const element of store.state.filters.etiquettes) {
      etiquettes.push(element)
    }

    return {
      sharedState: store.state,
      initFilters: {
        territoire: store.state.filters.territoire,
        communes: store.state.filters.communes,
        statut: store.state.filters.statut,
        etiquettes,
        type: store.state.filters.type,
        dateRange: store.state.filters.dateRange,
        countRefused: store.state.filters.countRefused,
        countArchived: store.state.filters.countArchived
      },
      statusList: [
        { Id: 'all', Text: 'Tous' },
        { Id: 'new', Text: 'Nouveau' },
        { Id: 'active', Text: 'En cours' },
        { Id: 'closed', Text: 'Fermé' }
      ],
      typesList: [
        { Id: 'all', Text: 'Tous' },
        { Id: 'public', Text: 'Public' },
        { Id: 'private', Text: 'Privé' },
        { Id: 'unset', Text: 'Non renseigné' }
      ]
    }
  },
  methods: {
    onReinitLocalEvent () {
      // Date management
      (this.$refs.histofiltersdatepicker as any).updateDate(this.initFilters.dateRange)
      this.sharedState.filters.dateRange = this.initFilters.dateRange

      this.sharedState.filters.etiquettes = new Array<string>()
      this.sharedState.filters.communes = new Array<string>()

      // Other simple data
      this.sharedState.filters.territoire = this.initFilters.territoire
      this.sharedState.filters.statut = this.initFilters.statut
      this.sharedState.filters.type = this.initFilters.type
      this.sharedState.filters.countRefused = this.initFilters.countRefused
      this.sharedState.filters.countArchived = this.initFilters.countArchived

      if (this.onChange !== undefined) {
        this.onChange()
      }
    }
  }
})
</script>

<style>
  #histo-stats-filters .fr-container--fluid {
    overflow: visible;
  }

  #histo-stats-filters .align-right {
    text-align: right;
  }

  #histo-stats-filters .fr-fi-refresh-line::before{
    margin-right: 5px;
    font-size: 1rem;
    color: var(--blue-france-sun-113-625);
    --icon-size: 1.1rem;
  }
</style>
