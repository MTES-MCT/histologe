<template>
  <dialog aria-labelledby="fr-modal-title-modal-continue-draft" role="dialog" id="fr-modal-continue-draft" class="fr-modal">
    <div class="fr-container fr-container--fluid fr-container-md">
      <div class="fr-grid-row fr-grid-row--center">
        <div class="fr-col-12 fr-col-md-8 fr-col-lg-6">
          <div class="fr-modal__body">
            <div class="fr-modal__header">
              <button class="fr-btn--close fr-btn" title="Fermer la fenêtre modale" aria-controls="fr-modal-continue-draft">Fermer</button>
            </div>
            <div class="fr-modal__content">
              <h1 id="fr-modal-title-modal-continue-draft" class="fr-modal__title">Reprendre la saisie</h1>
              <p>
                Il semblerait que vous avez commencé à remplir un signalement pour le logement situé {{adresse}}, le {{date}} à {{updated}}.
                Vous pouvez récupérer les infos de ce signalement et reprendre ou vous vous êtes arrêté.
                Souhaitez-vous reprendre le signalement ?
              </p>
            </div>
            <div class="fr-modal__footer">
              <ul class="fr-btns-group fr-btns-group--right fr-btns-group--inline-reverse fr-btns-group--inline-lg fr-btns-group--icon-left">
                <li>
                  <a class="fr-btn" aria-controls="fr-modal-continue-draft">
                    Oui, reprendre le signalement
                  </a>
                </li>
                <li>
                  <button class="fr-btn fr-btn--secondary" aria-controls="fr-modal-continue-draft">
                    Non, faire un nouveau signalement
                  </button>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </dialog>
  <a class="" href="#" id="fr-modal-continue-draft-button" aria-controls="fr-modal-continue-draft">Bouton de contrôle de la modale</a>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'SignalementFormModalContinueFromDraft',
  props: {
    adresse: String,
    date: String,
    updated: String
  }
})
</script>

<style>
</style>
