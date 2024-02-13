<template>
  <dialog
    :class="{ 'fr-modal--opened': modelValue }"
    aria-labelledby="fr-modal-title-modal-back-home"
    role="dialog"
    :id="id"
    class="fr-modal"
    ref="modalDialog"
    >
    <div class="fr-container fr-container--fluid fr-container-md">
      <div class="fr-grid-row fr-grid-row--center">
        <div class="fr-col-12 fr-col-md-8 fr-col-lg-6">
          <div class="fr-modal__body">
            <div class="fr-modal__header">
              <button
                class="fr-btn--close fr-btn fr-btn--icon-right fr-icon-close-line"
                title="Fermer la fenêtre modale"
                :aria-controls="id"
                @click="closeModal"
                >
                Fermer
              </button>
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
import { defineComponent, Ref } from 'vue'

export default defineComponent({
  name: 'SignalementFormModal',
  props: {
    id: String,
    label: String,
    description: String,
    modelValue: Boolean
  },
  methods: {
    closeModal () {
      this.$emit('update:modelValue', false)
    },
    handleEscapeKey (event: any) {
      if (event.key === 'Escape') {
        this.closeModal()
      }
    },
    handleClickOutside (event: any) {
      const modalDialog = this.$refs.modalDialog as Ref<HTMLElement>
      if (modalDialog && event.target === modalDialog) {
        this.closeModal()
      }
    }
  },
  mounted () {
    document.addEventListener('keyup', this.handleEscapeKey)
    document.addEventListener('click', this.handleClickOutside)
  },
  beforeUnmount () {
    document.removeEventListener('keyup', this.handleEscapeKey)
    document.removeEventListener('click', this.handleClickOutside)
  }
})
</script>

<style>
</style>
