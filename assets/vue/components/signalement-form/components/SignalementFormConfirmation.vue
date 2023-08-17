<template>
  <div :id="id">
    <div class="fr-container">
      <p>
        Votre signalement est bien enregistré.
        Vous allez recevoir un email de confirmation avec un lien vers votre page de suivi : <strong>gardez-le précieusement</strong> !
      </p>
      <p>
        Bon à savoir : en fonction du type de problèmes rencontrés, la procédure peut prendre du temps.
        Nos services font le maximum pour traiter votre demande au plus vite.
      </p>
    </div>
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
