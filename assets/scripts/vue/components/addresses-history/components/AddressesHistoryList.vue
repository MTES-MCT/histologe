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
          >
            Passer directement à la liste des adresses
          </a>
        </div>
      </div>
      <AddressesHistoryListFilters @change="onChange" />
      <div id="list-addresses">
        Liste des adresses
        <!-- TODO : temporaire pour tester -->
        <div class="fr-grid-row fr-my-2w" v-for="(item, index) in sharedState.addresses.list" :key="index">
          <div class="fr-col-12 fr-col-md-6 fr-col-lg-4 fr-mb-2w">
            <div class="fr-card fr-card--xs">
              <div class="fr-card__body">
                <h3 class="fr-card__title">{{ item.addressForHuman }}</h3>
                <p class="fr-card__desc">Nb de signalements à l'adresse : {{ item.signalements.length }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { store } from '../store'
import AddressesHistoryListFilters from './AddressesHistoryListFilters.vue'

// Émissions
const emit = defineEmits<{
  change: []
}>()

// State
const sharedState = store.state

/**
 * Propage le changement au parent
 */
const onChange = (): void => {
  emit('change')
}
</script>

<style>
</style>

