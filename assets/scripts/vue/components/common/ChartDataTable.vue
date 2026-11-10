<template>
  <div class="chart-data-table fr-table fr-table--sm fr-table--multiline fr-mb-0">
    <div class="fr-table__wrapper">
      <div class="fr-table__container">
        <div class="fr-table__content">
          <table :aria-labelledby="labelledBy">
            <thead>
              <tr>
                <th scope="col">Libellé</th>
                <th scope="col">Nombre</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, index) in rows" :key="index">
                <th scope="row">{{ row.label }}</th>
                <td>{{ row.value }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  labels: unknown[]
  values: unknown[]
  labelledBy: string
}

interface Row {
  label: string
  value: string
}

const props: Props = defineProps<Props>()

/**
 * Les libellés longs sont découpés en lignes par les graphiques : on les recompose
 */
const formatLabel = (label: unknown): string => {
  if (Array.isArray(label)) {
    return label.join(' ')
  }
  return String(label)
}

const formatValue = (value: unknown): string => {
  if (typeof value === 'number') {
    return value.toLocaleString('fr-FR')
  }
  return String(value)
}

const rows = computed((): Row[] => {
  return props.labels.map((label: unknown, index: number) => ({
    label: formatLabel(label),
    value: formatValue(props.values[index])
  }))
})
</script>

<style>
  .chart-data-table {
    text-align: left;
  }
</style>
