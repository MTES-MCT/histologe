<template>
  <!-- TODO : au-dessus du fil d'ariane, il y a un indice de progression avec des rectangles pour chaque grande étape
  qui se colorent au fur et à mesure . Il faudra donc une notion de nombre d'écrans-->
  <nav role="navigation" class="fr-breadcrumb" aria-label="vous êtes ici :">
      <button class="fr-breadcrumb__button" aria-expanded="false" aria-controls="breadcrumb-1">Voir le fil d’Ariane</button>
      <div class="fr-collapse" id="breadcrumb-1">
          <ol class="fr-breadcrumb__list">
              <li>
                  <a class="fr-breadcrumb__link" href="/">Accueil</a>
              </li>
              <li>
                  <a class="fr-breadcrumb__link" href="/nouveau-formulaire/signalement/">Signalement</a>
              </li>
              <li>
                  <a class="fr-breadcrumb__link" aria-current="page">{{ currentStep }}</a>
              </li>
          </ol>
      </div>
  </nav>
  <!-- TODO : au clic sur accueil, une fenêtre d'avertissement s'ouvre par dessus-tout avant de valider le retour à l'accueil -->
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './store'

export default defineComponent({
  name: 'SignalementFormScreen',
  components: {
  },
  props: {
    currentStep: String
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
