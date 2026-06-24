<template>
    <section class="fr-container-sml">
        <div class="fr-col-12">
            <h1 class="fr-h2">Historique des événements par adresse</h1>
            <div class="fr-container--fluid" role="search">
                <div class="fr-skiplinks">
                    <a class="fr-link fr-link--icon-right fr-icon-arrow-down-line" href="#list-addresses" aria-label="Passer directement à la liste des adresses">Passer directement à la liste des adresses</a>
                </div>
            </div>
            <AddressesHistoryListFilters @change="handleFilterChanges"/>
            <div id="list-addresses">
              Liste des adresses
            </div>
        </div>
    </section>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from '../store'
import AddressesHistoryListFilters from './AddressesHistoryListFilters.vue'

export default defineComponent({
  name: 'AddressesHistoryList',
  components: {
    AddressesHistoryListFilters
  },
  emits: ['filterChange'],
  data () {
    return {
      sharedState: store.state,
      sharedProps: store.props
    }
  },
  computed: {
  },
  methods: {
    handleFilterChanges (refresh: boolean) {
      this.$emit('filterChange', refresh)
    }
  }
})
</script>
<style>
</style>
