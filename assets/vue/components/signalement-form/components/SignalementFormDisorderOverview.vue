<template>
  <div :id="id" class="signalement-form-disorder-overview fr-container--fluid fr-my-3v">
    <div v-if="formStore.data.categorieDisorders.batiment.length > 0">
      <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--middle">
        <div class="fr-col-2"><img :src="icons ? icons[0].src : ''" :alt="icons ? icons[0].alt : ''"></div>
        <div class="fr-col-10 fr-h2">Le bâtiment</div>
      </div>
      <div class="fr-accordions-group">
        <section
          v-for="(disorder, index) in formStore.data.categorieDisorders.batiment"
          v-bind:key="disorder"
          class="fr-accordion"
          >
          <h3 class="fr-accordion__title">
            <button class="fr-accordion__btn" aria-expanded="false" :aria-controls="'accordion-disorder-batiment-' + index">{{ disorder }}</button>
          </h3>
          <div class="fr-collapse" :id="'accordion-disorder-batiment-' + index">
            <h4 class="fr-h4">Contenu</h4>
          </div>
        </section>
      </div>
    </div>
    <div v-if="formStore.data.categorieDisorders.logement.length > 0">
      <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--middle">
        <div class="fr-col-2"><img :src="icons ? icons[1].src : ''" :alt="icons ? icons[1].alt : ''"></div>
        <div class="fr-col-10 fr-h2">Le logement</div>
      </div>
      <div class="fr-accordions-group">
        <section
          v-for="(disorder, index) in formStore.data.categorieDisorders.logement"
          v-bind:key="disorder"
          class="fr-accordion"
          >
          <h3 class="fr-accordion__title">
            <button class="fr-accordion__btn" aria-expanded="false" :aria-controls="'accordion-disorder-logement-' + index">{{ disorder }}</button>
          </h3>
          <div class="fr-collapse" :id="'accordion-disorder-logement-' + index">
            <h4 class="fr-h4">Contenu</h4>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'

export default defineComponent({
  name: 'SignalementFormDisorderOverview',
  props: {
    id: { type: String, default: null },
    icons: { type: Object }
  },
  data () {
    return {
      formStore
    }
  }
})
</script>

<style>
</style>
