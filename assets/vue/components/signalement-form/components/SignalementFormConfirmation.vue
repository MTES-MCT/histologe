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
      <a href="https://jedonnemonavis.numerique.gouv.fr/Demarches/3742?&view-mode=formulaire-avis&nd_source=button&key=743c44c1973b9adfe59223f5ecb7c4b3">
        <img src="https://jedonnemonavis.numerique.gouv.fr/static/bouton-bleu.svg" alt="Je donne mon avis" />
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
