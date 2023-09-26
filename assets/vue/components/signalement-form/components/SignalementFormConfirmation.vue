<template>
  <div :id="id">
    <p>
      Votre signalement est bien enregistré.
      Vous allez recevoir un email de confirmation avec un lien vers votre page de suivi : <strong>gardez-le précieusement</strong> !
    </p>
    <p>
      Bon à savoir : en fonction du type de problèmes rencontrés, la procédure peut prendre du temps.
      Nos services font le maximum pour traiter votre demande au plus vite.
    </p>
    <p class="fr-text--center">
      <a href="https://voxusagers.numerique.gouv.fr/Demarches/3104?&amp;view-mode=formulaire-avis&amp;nd_mode=en-ligne-enti%C3%A8rement&amp;nd_source=button&amp;key=8a6945494dbe7dd6e52d3dd28d667c1d">
        <img src="https://voxusagers.numerique.gouv.fr/static/bouton-bleu.svg" alt="Je donne mon avis">
      </a>
    </p>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'SignalementFormConfirmation',
  props: {
    id: { type: String, default: null }
  }
})
</script>

<style>
</style>
