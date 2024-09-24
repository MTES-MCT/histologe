<template>
  <div :id="id" :class="[ customCss ]">
    <h3>
      Qu'avez vous pensé de ce formulaire ?
    </h3>
    <p>
      Cliquez sur le bouton ci-dessous pour donner votre avis.
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
    id: { type: String, default: null },
    customCss: { type: String, default: '' },
    // les propriétés suivantes ne sont pas utilisées,
    // mais si on ne les met pas, elles apparaissent dans le DOM
    // et ça soulève des erreurs W3C
    hasError: { type: Boolean, default: false },
    clickEvent: Function,
    handleClickComponent: Function,
    access_name: { type: String, default: '' },
    access_autocomplete: { type: String, default: '' },
    access_focus: { type: Boolean, default: false }
  }
})
</script>

<style>
</style>
