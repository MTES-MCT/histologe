<template>
  <dialog aria-labelledby="fr-modal-title-modal-back-home" id="fr-modal-back-home" class="fr-modal">
    <div class="fr-container fr-container--fluid fr-container-md">
      <div class="fr-grid-row fr-grid-row--center">
        <div class="fr-col-12 fr-col-md-8 fr-col-lg-6">
          <div class="fr-modal__body">
            <div class="fr-modal__header">
              <button type="button" class="fr-btn--close fr-btn" title="Fermer la fenêtre modale" aria-controls="fr-modal-back-home">Fermer</button>
            </div>
            <div class="fr-modal__content">
              <h1 id="fr-modal-title-modal-back-home" class="fr-modal__title">Retour à l'accueil</h1>
              <p>
                Attention, en retournant à l'accueil de {{ formStore.props.platformName }}, vous allez quitter le signalement.
                <br>
                Les données saisies seront perdues !
                <br><br>
                Pour continuer votre signalement, cliquez sur "Continuer ma saisie".
              </p>
            </div>
            <div class="fr-modal__footer">
              <ul class="fr-btns-group fr-btns-group--right fr-btns-group--inline-reverse fr-btns-group--inline-lg fr-btns-group--icon-left">
                <li>
                  <a class="fr-btn fr-icon-check-line" href="/">
                    Quitter le signalement
                  </a>
                </li>
                <li>
                  <button class="fr-btn fr-btn--secondary fr-icon-close-line" aria-controls="fr-modal-back-home" type="button">
                    Continuer ma saisie
                  </button>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </dialog>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from '../store'

export default defineComponent({
  name: 'SignalementFormModalBackHome',
  data () {
    return {
      formStore
    }
  },
})
</script>

<style>
</style>
