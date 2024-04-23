<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: "TheHistoSignalementListPagination"
})
</script>

<template>
  <div class="fr-grid-row fr-mb-1w fr-grid-row--center">
    <nav role="navigation" class="fr-pagination" aria-label="Pagination">
      <ul class="fr-pagination__list">
        <li><a class="fr-pagination__link fr-pagination__link--first" aria-disabled="true" role="link">
          Première page </a></li>
        <li><a class="fr-pagination__link fr-pagination__link--prev fr-pagination__link--lg-label"
               aria-disabled="true" role="link"> Page précédente </a></li>
        <li><a class="fr-pagination__link" aria-current="page" title="Page 1"> 1 </a></li>
        <li><a class="fr-pagination__link" href="#" title="Page 2"> 2 </a></li>
        <li><a class="fr-pagination__link fr-displayed-lg" href="#" title="Page 3"> 3 </a></li>
        <li><a class="fr-pagination__link fr-displayed-lg"> … </a></li>
        <li><a class="fr-pagination__link fr-displayed-lg" href="#" title="Page 130"> 130 </a></li>
        <li><a class="fr-pagination__link fr-displayed-lg" href="#" title="Page 131"> 131 </a></li>
        <li><a class="fr-pagination__link" href="#" title="Page 132"> 132 </a></li>
        <li><a class="fr-pagination__link fr-pagination__link--next fr-pagination__link--lg-label"
               href="#">
          Page suivante </a></li>
        <li><a class="fr-pagination__link fr-pagination__link--last" href="#"> Dernière page </a></li>
      </ul>
    </nav>
  </div>
</template>

<style scoped>

</style>
