<template>
  <div class="fr-p-2w">
    <span class="fr-text--sm fr-mb-1v fr-text-label--blue-france">
      <strong>Evènement(s) à cette adresse</strong>
    </span>
    <p class="fr-text--sm">
      <span class="fr-icon-map-pin-2-line" aria-hidden="true"></span>
      {{ addressForHuman || 'Adresse inconnue' }}
    </p>

    <!-- Arrêtés -->
    <template v-if="arretes.length > 0">
      <div v-for="(arrete, index) in arretes" :key="index">
        <hr>
        <p class="fr-text--sm fr-mb-2v fr-text-label--blue-france">
          <strong>{{ arrete.arreteTypeLabel || 'Arrêté' }}</strong>
        </p>
        <p class="fr-text--sm" :class="arrete.dateMainLevee ? 'fr-mb-2v' : 'fr-mb-5v'">
          <span class="fr-icon-calendar-line" aria-hidden="true"></span>
          Date de l'arrêté : {{ arrete.dateArrete || 'Non renseignée' }}
        </p>
        <p v-if="arrete.dateMainLevee" class="fr-text--sm fr-mb-5v">
          <span class="fr-icon-thumb-up-line" aria-hidden="true"></span>
          Main levée : {{ arrete.dateMainLevee }}
        </p>
      </div>
    </template>

    <!-- Signalements -->
    <template v-if="signalements.length > 0">
      <hr>
      <p class="fr-text--sm fr-mb-5v fr-text-label--blue-france">
        <span class="fr-picto-dossiers-multiples"></span>
        <strong>Dossiers à l'adresse</strong>
      </p>
      <ul class="list-unstyled fr-mb-0">
        <li v-for="signalement in signalements" :key="signalement.ref" class="fr-mb-2v">
          <a :href="signalement.url" class="fr-link fr-text--sm">#{{ signalement.ref }}</a>
          <span class="fr-text--sm"> - {{ signalement.usager }}</span>
          <p :class="getStatusBadgeClass(signalement.statut)" class="fr-text--xs">
            {{ signalement.statut }}
          </p>
        </li>
      </ul>
    </template>
  </div>
</template>

<script setup lang="ts">
import { getStatusBadgeFromLabel } from '../utils/badgeHelpers'

interface Arrete {
  arreteTypeLabel?: string
  dateArrete?: string
  dateMainLevee?: string
}

interface Signalement {
  ref: string
  url: string
  usager: string
  statut: string
}

interface Props {
  addressForHuman?: string
  arretes: Arrete[]
  signalements: Signalement[]
}

defineProps<Props>()

function getStatusBadgeClass(status: string): string {
  return getStatusBadgeFromLabel(status)
}
</script>

<style scoped>
ul.list-unstyled {
  list-style-type: none;
  padding-left: 0;
}
</style>
