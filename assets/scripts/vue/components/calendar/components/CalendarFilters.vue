<template>
  <div class="calendar-filters">
    <!-- Labels rendus ici, à gauche des champs, plutôt que dans les composants (au-dessus) -->
    <div v-if="territories.length > 0" class="calendar-filters__field">
      <label class="fr-label" for="calendar-filter-territory">Territoire</label>
      <HistoSelect
        id="calendar-filter-territory"
        :model-value="modelValue.territoire"
        :option-items="territoryItems"
        @update:model-value="onTerritoryChange"
      />
    </div>

    <fieldset
      v-else-if="operators.length > 0"
      class="calendar-filters__field calendar-filters__fieldset"
    >
      <legend class="fr-label">Opérateur</legend>
      <AppListCheckboxes
        id="calendar-filter-operators"
        :model-value="modelValue.operateurs"
        :option-groups="operatorGroups"
        placeholder="Tous les opérateurs"
        @update:model-value="onOperatorsChange"
      />
    </fieldset>

    <button
      type="button"
      class="fr-tag"
      :aria-pressed="initialUniquementMesDossiers ? 'true' : 'false'"
      @click="toggleUniquementMesDossiers"
    >
      Afficher uniquement mes dossiers
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import HistoSelect from '../../common/HistoSelect.vue'
import AppListCheckboxes from '../../common/AppListCheckboxes.vue'
import type HistoInterfaceSelectOption from '../../common/HistoInterfaceSelectOption'
import type { CheckboxGroup } from '../../common/AppListCheckboxes.types'
import type { CalendarFilterOption, CalendarFilters } from '../types'

interface Props {
  modelValue: CalendarFilters
  territories: CalendarFilterOption[]
  operators: CalendarFilterOption[]
}
const props: Props = defineProps<Props>()

// Valeur initiale seulement : ensuite, le JS du DSFR inverse lui-même aria-pressed au clic
// (une liaison réactive ferait double bascule avec celle du DSFR)
const initialUniquementMesDossiers = props.modelValue.uniquementMesDossiers

const emit = defineEmits<{
  'update:modelValue': [filters: CalendarFilters]
}>()

const territoryItems = computed<HistoInterfaceSelectOption[]>(() => {
  const items = props.territories.map((territory) => ({ Id: String(territory.id), Text: territory.label }))
  return [{ Id: '', Text: 'Tous les territoires' }, ...items]
})

const operatorGroups = computed<CheckboxGroup[]>(() => {
  const options = props.operators.map((operator) => ({ Id: String(operator.id), Text: operator.label }))
  return [{ options }]
})

const updateFilters = (changes: Partial<CalendarFilters>): void => {
  emit('update:modelValue', { ...props.modelValue, ...changes })
}

const onTerritoryChange = (territoire: string): void => {
  updateFilters({ territoire })
}

const onOperatorsChange = (operateurs: string[]): void => {
  updateFilters({ operateurs })
}

/**
 * Le JS du DSFR inverse aussi aria-pressed au clic : l'état Vue suit la même bascule
 */
const toggleUniquementMesDossiers = (): void => {
  updateFilters({ uniquementMesDossiers: !props.modelValue.uniquementMesDossiers })
}
</script>

<style>
  .calendar-filters {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 1.5rem;
  }

  .calendar-filters .fr-tag {
    margin: 0;
  }

  /* Label à gauche du champ */
  .calendar-filters .calendar-filters__field {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .calendar-filters .calendar-filters__field > .fr-label {
    margin: 0;
    white-space: nowrap;
  }

  /* Styles par défaut du fieldset neutralisés */
  .calendar-filters .calendar-filters__fieldset {
    min-width: 0;
    margin: 0;
    padding: 0;
    border: 0;
  }

  /* Legend flottant : affiché comme un élément ordinaire, à gauche du champ (et non incrusté dans la bordure) */
  .calendar-filters .calendar-filters__fieldset > legend {
    float: left;
    padding: 0;
  }

  /* AppListCheckboxes affiche un label vide quand on ne lui passe pas de slot */
  .calendar-filters .calendar-filters__field .app-list-checkboxes .fr-label:empty {
    display: none;
  }

  /* Styles scopés de AppListCheckboxes surchargés : pas de marge au-dessus du champ */
  .calendar-filters .calendar-filters__field .app-list-checkboxes-display {
    min-width: 16rem;
    margin-top: 0;
  }
</style>
