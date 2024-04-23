<template>
  <TheHistoSignalementListFilter/>
  <section class="fr-col-12 fr-background-alt--blue-france fr-mt-0">
    <div class="fr-px-3w">
      <TheHistoSignalementListHeader/>
      <div class="fr-grid-row fr-my-2w" v-for="count in 25" :key="count">
        <TheHistoSignalementListCard/>
      </div>
      <TheHistoSignalementPagination/>
    </div>
  </section>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import { requests } from './requests'
import TheHistoSignalementListFilter from "../signalement-list/TheHistoSignalementListFilter.vue";
import TheHistoSignalementListHeader from "../signalement-list/TheHistoSignalementListHeader.vue";
import TheHistoSignalementListCard from "../signalement-list/TheHistoSignalementListCard.vue";
import TheHistoSignalementListPagination from "../signalement-list/TheHistoSignalementListPagination.vue";

export default defineComponent({
  name: "TheHistoAppSignalementList",
  components: {
    TheHistoSignalementListFilter,
    TheHistoSignalementListHeader,
    TheHistoSignalementListCard,
    TheHistoSignalementListPagination
  },
})
</script>


<style scoped>

</style>
