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
      <AddressesHistoryListFilters @change="onChange" />
      <div id="list-addresses" class="fr-mt-2w">
        <h2 v-if="sharedState.addresses.list.length > 1"
          >{{ sharedState.addresses.list.length }} entrées trouvées</h2>
        <h2 v-else-if="sharedState.addresses.list.length === 1"
          >1 entrée trouvée</h2>
        <h2 v-else>Aucune entrée trouvée</h2>

        <div class="fr-container--fluid">
          <div class="fr-mb-2w" v-for="(item, index) in sharedState.addresses.list" :key="index">
            <div class="fr-grid-row fr-border fr-p-2w">
              <div class="fr-col-12 fr-col-md-8">
                <h3 class="title-blue-france"><span class="fr-icon-map-pin-2-line" aria-hidden="true"></span> {{ (item as any).addressForHuman }}</h3>
              </div>
              <div class="fr-col-12 fr-col-md-4 fr-text--md-right">
                <div>
                  <p class="fr-badge fr-badge--new fr-badge--no-icon" v-if="(item as any).hasLogementSocial">Parc public</p>
                  <p class="fr-badge fr-badge--new fr-badge--no-icon" v-if="(item as any).hasLogementPrive">Parc privé</p>
                </div>
                <div v-if="(item as any).bailleurNames && (item as any).bailleurNames.length > 0" class="fr-mt-1w">
                  Bailleur : {{ (item as any).bailleurNames.join(', ') }}
                </div>
              </div>
              <div class="fr-col-12 fr-col-md-6">
                <div class="fr-mb-1v">
                  <strong>Dossiers à cette adresse</strong>
                </div>
                <ul v-if="(item as any).signalements && (item as any).signalements.length > 0" class="list-unstyled">
                  <li v-for="(signalement, index) in (item as any).signalements" :key="index" class="fr-mb-3v">
                    <a :href="`${signalement.url}`" class="fr-link"
                      ># {{ signalement.ref }}</a> - {{ signalement.usager }}
                    <p :class="getStatusLabel(signalement.statut)">{{ signalement.statut }}</p>
                  </li>
                </ul>
                <div v-else>
                  Aucun dossier enregistré à cette adresse
                </div>
              </div>
              <div class="fr-col-12 fr-col-md-6">
                <div class="fr-mb-1v">
                  <strong>Arrêtés pris à cette adresse</strong>
                </div>
                <ul v-if="(item as any).arretes && (item as any).arretes.length > 0" class="list-unstyled">
                  <li v-for="(arrete, index) in (item as any).arretes" :key="index">
                    <div class="fr-mb-3v"><span :class="getArreteClass(arrete.typeArrete)" aria-hidden="true"></span> {{ arrete.typeArreteLabel }} - {{ arrete.dateArrete }}</div>
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
  </section>
</template>

<script setup lang="ts">
import { store } from '../composables/useAddressesHistoryStore'
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

function getStatusLabel(status: string): string {
  let className = 'fr-badge fr-badge--no-icon '
  switch (status) {
    case 'nouveau':
      className += 'fr-badge--error'
      break
    case 'en cours':
      className += 'fr-badge--success'
      break
    case 'fermé':
      className += 'fr-badge-grey'
      break
  }

  return className 
}

function getArreteClass(typeArrete: string): string {
  let className = 'fr-icon fr-mr-2v '

  // Groupe "Mise en sécurité"
  if (typeArrete.startsWith('MISE_EN_SECURITE')) {
    className += 'fr-icon-hexagon-fill fr-color-mise-en-securite'
  }
  // Groupe "Insalubrité"
  else if (typeArrete.startsWith('ARRETE_L_511') || typeArrete.startsWith('ARRETE_L_1331')) {
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

