<template>
  <section class="fr-container-sml">
    <div class="fr-col-12">
      <h1 class="fr-h2">Historique des événements par adresse</h1>
      <div class="fr-container--fluid" role="search">
        <div class="fr-skiplinks">
          <a
            class="fr-link fr-link--icon-right fr-icon-arrow-down-line"
            href="#list-addresses"
            aria-label="Passer directement à la liste des adresses"
            >Passer directement à la liste des adresses</a>
        </div>
      </div>
      <AddressesHistoryListFilters @change="onFiltersChange" />
      <div id="list-addresses" class="fr-mt-2w">
        <div v-if="sharedState.loadingList" class="fr-mt-2w">
          <p>Mise à jour de la liste...</p>
        </div>
        <div v-else>
          <h2 v-if="sharedState.addresses.pagination.total_items > 1"
            >{{ sharedState.addresses.pagination.total_items }} entrées trouvées</h2>
          <h2 v-else-if="sharedState.addresses.pagination.total_items === 1"
            >1 entrée trouvée</h2>
          <h2 v-else>Aucune entrée trouvée</h2>

          <div class="fr-container--fluid">
            <div class="fr-mb-2w" v-for="(item, index) in sharedState.addresses.list" :key="index">
              <div class="fr-grid-row fr-border fr-p-2w">
                <div class="fr-col-12 fr-col-md-12">
                  <h3 class="title-blue-france fr-h6"><span class="fr-icon-map-pin-2-line" aria-hidden="true"></span> {{ (item as any).addressForHuman }}</h3>
                </div>
                <div class="fr-col-12 fr-col-md-8">
                  <div class="fr-mb-1v">
                    <strong>Dossiers à cette adresse ({{ (item as any).signalements?.length ?? 0 }})</strong>
                  </div>
                  <ul v-if="(item as any).signalements && (item as any).signalements.length > 0" class="list-unstyled">
                    <li v-for="(signalement, signalementIndex) in getDisplayedSignalements(item, index)" :key="signalementIndex" class="fr-mb-3v">
                      <div>
                        <a :href="`${signalement.url}`" class="fr-link"
                          ># {{ signalement.ref }}</a> - {{ signalement.usager }}
                        <p :class="[getStatusBadgeFromLabel(signalement.statut), 'fr-badge--sm']">{{ signalement.statut }}</p>
                        <p class="fr-badge fr-badge--no-icon fr-badge--info fr-badge--sm fr-ml-1w">{{ signalement.declarant }}</p>
                      </div>
                      <div class="fr-mt-1v">
                        Bailleur : {{signalement.bailleurName ? signalement.bailleurName : 'Non renseigné'}}
                        <p class="fr-badge fr-badge--new fr-badge--no-icon fr-badge--sm ">Parc {{ signalement.isLogementSocial === null ? 'non renseigné' : (signalement.isLogementSocial ? 'public' : 'privé') }}</p>
                      </div>
                    </li>
                  </ul>
                  <button
                    v-if="(item as any).signalements?.length > MAX_SIGNALEMENTS_DISPLAYED"
                    type="button"
                    class="fr-link"
                    @click="toggleSignalements(index)"
                    >{{ expandedAddresses.has(index) ? 'Voir moins' : 'Voir tous les dossiers' }}</button>
                  <div v-else-if="!(item as any).signalements || (item as any).signalements.length === 0">
                    Aucun dossier enregistré à cette adresse
                  </div>
                </div>
                <div class="fr-col-12 fr-col-md-4">
                  <div class="fr-mb-1v">
                    <strong>Arrêtés pris à cette adresse</strong>
                  </div>
                  <ul v-if="(item as any).arretes && (item as any).arretes.length > 0" class="list-unstyled">
                    <li v-for="(arrete, index) in (item as any).arretes" :key="index">
                      <div class="fr-mb-3v"><span :class="getArreteClass(arrete.arreteType)" aria-hidden="true"></span> {{ arrete.arreteTypeLabel }} - {{ arrete.dateArrete }}</div>
                      <div v-if="arrete.dateMainLevee" class="fr-mb-3v"><span class="fr-icon-thumb-up-line fr-mr-2v" aria-hidden="true"></span> Main levée : {{ arrete.dateMainLevee }}</div>
                    </li>
                  </ul>
                  <div v-else>
                    Aucun arrêté trouvé à cette adresse
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <AddressesHistoryListPagination
        v-if="sharedState.addresses.pagination.total_pages > 1"
        :pagination="sharedState.addresses.pagination"
        @changePage="onPageChange"
      />
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { store } from '../composables/useAddressesHistoryStore'
import AddressesHistoryListFilters from './AddressesHistoryListFilters.vue'
import AddressesHistoryListPagination from './AddressesHistoryListPagination.vue'
import { getStatusBadgeFromLabel } from '../utils/displayHelpers'

// Émissions
const emit = defineEmits<{
  change: []
}>()

// Constantes
const MAX_SIGNALEMENTS_DISPLAYED = 5

// State
const sharedState = store.state
const expandedAddresses = ref<Set<number>>(new Set())

/**
 * Réinitialise les adresses dépliées lorsque la liste change (pagination, filtres)
 */
watch(() => sharedState.addresses.list, () => {
  expandedAddresses.value = new Set()
})

/**
 * Retourne les dossiers à afficher pour une adresse (limités sauf si l'adresse est dépliée)
 */
const getDisplayedSignalements = (item: any, index: number): any[] => {
  const signalements = item.signalements ?? []
  if (expandedAddresses.value.has(index)) {
    return signalements
  }
  return signalements.slice(0, MAX_SIGNALEMENTS_DISPLAYED)
}

/**
 * Affiche tous les dossiers d'une adresse ou revient à l'affichage limité
 */
const toggleSignalements = (index: number): void => {
  const expanded = new Set(expandedAddresses.value)
  if (expanded.has(index)) {
    expanded.delete(index)
  } else {
    expanded.add(index)
  }
  expandedAddresses.value = expanded
}

/**
 * Propage le changement au parent
 */
const onChange = (): void => {
  emit('change')
}

const onFiltersChange = (): void => {
  sharedState.addresses.pagination.current_page = 1
  onChange()
}

/**
 * Gère le changement de page avec scroll vers le haut
 */
const onPageChange = (page: number): void => {
  sharedState.addresses.pagination.current_page = page

  // Scroll vers le haut de la liste
  const listElement = document.getElementById('list-addresses')
  if (listElement) {
    listElement.scrollIntoView({ behavior: 'instant', block: 'start' })
  }

  onChange()
}

function getArreteClass(arreteType: string): string {
  let className = 'fr-icon fr-mr-2v '

  // Groupe "Mise en sécurité"
  if (arreteType.startsWith('MISE_EN_SECURITE')) {
    className += 'fr-icon-hexagon-fill fr-color-mise-en-securite'
  }
  // Groupe "Insalubrité"
  else if (arreteType.startsWith('ARRETE_L_511') || arreteType.startsWith('ARRETE_L_1331')) {
    className += 'fr-icon-square-fill fr-color-insalubrite'
  }
  // Groupe "Autres"
  else {
    className += 'fr-icon-circle-fill fr-color-autre'
  }

  return className
}
</script>

<style>
ul.list-unstyled {
  list-style-type: none;
  padding-left: 0;
}
span.fr-color-mise-en-securite {
  color: #6A6AF4;
}
span.fr-color-insalubrite {
  color: #31A7AE;
}
span.fr-color-autre {
  color: #9747FF;
}
</style>

