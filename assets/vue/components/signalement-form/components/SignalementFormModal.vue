<template>
  <dialog aria-labelledby="fr-modal-title-modal-back-home" role="dialog" :id="id" class="fr-modal">
    <div class="fr-container fr-container--fluid fr-container-md">
      <div class="fr-grid-row fr-grid-row--center">
        <div class="fr-col-12 fr-col-md-8 fr-col-lg-6">
          <div class="fr-modal__body">
            <div class="fr-modal__header">
              <button class="fr-btn--close fr-btn fr-btn--icon-right fr-icon-close-line" title="Fermer la fenêtre modale" :aria-controls="id">Fermer</button>
            </div>
            <div class="fr-modal__content">
              <h1>{{ label }}</h1>
              <p v-html="description"></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </dialog>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'SignalementFormModal',
  props: {
    id: String,
    label: String,
    description: String
  }
})
</script>

<style>
</style>
