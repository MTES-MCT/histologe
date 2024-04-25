<template>
  <div class="fr-grid-row fr-mb-1w">
    <div class="fr-col fr-col-md-9"><h2>956 signalements trouvés</h2>
    </div>
  </div>
  <div class="fr-grid-row fr-mb-1w">
    <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
      <select class="fr-select" id="">
        <option value="" selected disabled hidden>Ordre décroissant</option>
        <option value="">Ordre décroissant</option>
        <option value="">Ordre croissant : par référence</option>
        <option value="">Ordre alphabétique (A -> Z)</option>
        <option value="">Ordre alphabétique inversé (Z -> A)</option>
        <option value="">Le plus récent</option>
        <option value="">Le plus ancien</option>
      </select>
    </div>
    <div class="fr-col-12 fr-col-lg-6 fr-col-xl-9 fr-text--right">
      <button class="fr-btn fr-btn--secondary fr-btn--icon-left fr-icon-download-fill">
        Exporter les résultats
      </button>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: "TheHistoSignalementListHeader"
})
</script>

<style scoped>

</style>
