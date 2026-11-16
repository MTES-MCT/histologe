<template>
  <div class="calendar-filters">
    <!-- Territoire : SA et utilisateurs multi-territoires -->
    <!-- Labels rendus ici, à gauche des champs, plutôt que dans les composants (au-dessus) -->
    <div v-if="territories.length > 0" class="calendar-filters__field">
      <label class="fr-label" for="calendar-filter-territory">Territoire</label>
      <HistoSelect
        id="calendar-filter-territory"
        :model-value="modelValue.territoryId"
        :option-items="territoryItems"
        @update:model-value="onTerritoryChange"
      />
    </div>

    <!-- Opérateur : RT -->
    <!-- Le champ d'AppListCheckboxes n'est pas un contrôle de formulaire : le label nomme le groupe de cases -->
    <div
      v-else-if="operators.length > 0"
      class="calendar-filters__field"
      role="group"
      aria-labelledby="calendar-filter-operators-label"
    >
      <span id="calendar-filter-operators-label" class="fr-label">Opérateur</span>
      <AppListCheckboxes
        id="calendar-filter-operators"
        :model-value="modelValue.operatorIds"
        :option-groups="operatorGroups"
        placeholder="Tous les opérateurs"
        @update:model-value="onOperatorsChange"
      />
    </div>

    <button
      type="button"
      class="fr-tag"
      :aria-pressed="modelValue.onlyMySignalements ? 'true' : 'false'"
      @click="toggleOnlyMySignalements"
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

// Props
// (v-model déclaré à la main : le code généré par defineModel ne passe pas la vérification de ts-loader)
interface Props {
  modelValue: CalendarFilters
  territories: CalendarFilterOption[]
  operators: CalendarFilterOption[]
}
const props: Props = defineProps<Props>()

// Émissions
const emit = defineEmits<{
  'update:modelValue': [filters: CalendarFilters]
  change: []
}>()

// Options au format attendu par HistoSelect, avec "Tous les territoires" en premier
const territoryItems = computed<HistoInterfaceSelectOption[]>(() => {
  const items = props.territories.map((territory) => ({ Id: String(territory.id), Text: territory.label }))
  return [{ Id: '', Text: 'Tous les territoires' }, ...items]
})

// Options au format attendu par AppListCheckboxes (un seul groupe, sans titre)
const operatorGroups = computed<CheckboxGroup[]>(() => {
  const options = props.operators.map((operator) => ({ Id: String(operator.id), Text: operator.label }))
  return [{ options }]
})

/**
 * Met à jour les filtres puis notifie le parent
 */
const updateFilters = (changes: Partial<CalendarFilters>): void => {
  emit('update:modelValue', { ...props.modelValue, ...changes })
  emit('change')
}

const onTerritoryChange = (territoryId: string): void => {
  updateFilters({ territoryId })
}

const onOperatorsChange = (operatorIds: string[]): void => {
  updateFilters({ operatorIds })
}

/**
 * Le JS du DSFR inverse aussi aria-pressed au clic : l'état Vue suit la même bascule
 */
const toggleOnlyMySignalements = (): void => {
  updateFilters({ onlyMySignalements: !props.modelValue.onlyMySignalements })
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
