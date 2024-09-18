<template>
  <dialog
    :class="{ 'fr-modal--opened': modelValue }"
    :aria-labelledby="label !== '' ? id + '-fr-modal-title' : undefined"
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
              <h1 :id="id + '-fr-modal-title'" v-if="label !== ''">{{ label }}</h1>
              <div v-html="description"></div>
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
    modelValue: Boolean,
    // les propriétés suivantes ne sont pas utilisées,
    // mais si on ne les met pas, elles apparaissent dans le DOM
    // et ça soulève des erreurs W3C
    handleClickComponent: Function,
    clickEvent: Function,
    hasError: { type: Boolean, default: undefined },
    access_name: { type: String, default: undefined },
    access_autocomplete: { type: String, default: undefined },
    access_focus: { type: Boolean, default: false },
    validOnEnter: { type: Boolean, default: false }
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
