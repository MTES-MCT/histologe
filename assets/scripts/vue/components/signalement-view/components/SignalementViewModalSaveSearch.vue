<template>
  <dialog aria-labelledby="modal-save-search-title" id="modal-save-search" class="fr-modal">
    <div class="fr-container fr-container--fluid fr-container-lg">
      <div class="fr-grid-row fr-grid-row--center">
        <div class="fr-col-12 fr-col-md-10 fr-col-lg-8">
          <div class="fr-modal__body">
            <div class="fr-modal__header">
              <button type="button" class="fr-btn--close fr-btn" aria-controls="modal-save-search">Fermer</button>
            </div>
            <div class="fr-modal__content">
              <h1 id="modal-save-search-title" class="fr-modal__title">
                Sauvegarder ma recherche
              </h1>
              <div v-if="classNameEditConfirmation.length > 0" class="fr-alert fr-alert--sm" :class="classNameEditConfirmation">
                <p>{{ messageEditConfirmation }}</p>
              </div>
              <strong>NB : vous pouvez sauvegarder 5 recherches favorites au maximum</strong>
              <div class="fr-my-4v">
                <div class="fr-input-group fr-mr-2v">
                  <label class="fr-label">
                    Saisissez un nom pour votre recherche <span class="text-required">*</span>
                    <span class="fr-hint-text">Le nom sera affiché dans votre liste de recherches favorites. 50 caractères maximum.</span>
                  </label>
                  <input type="text" class="fr-input" v-model="nameSearch" :maxlength="50"/>
                </div>

                <div class="fr-mb-2v">
                  <label class="fr-label">Votre recherche a les filtres suivants :</label>
                  <div class="fr-tags-group">
                    <span v-for="(value, key) in filtersSanitized" :key="key" class="fr-tag fr-tag--sm fr-tag--dismiss">
                      {{ getBadgeFilterLabel(key, value) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <div class="fr-modal__footer">
              <ul class="fr-btns-group fr-btns-group--right fr-btns-group--inline-reverse fr-btns-group--inline-lg fr-btns-group--icon-left">
                <li>
                  <button class="fr-btn fr-icon-check-line" @click="saveSearch()">
                    Valider
                  </button>
                </li>
                <li>
                  <button type="button" class="fr-btn fr-btn--secondary fr-icon-close-line" aria-controls="modal-save-search">
                    Annuler
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
import { store } from '../store'
import { buildBadge } from '../services/badgeFilterLabelBuilder'

export default defineComponent({
  name: 'SignalementViewModalSaveSearch',
  components: {
  },
  data () {
    return {
      sharedProps: store.props,
      sharedState: store.state,
      messageEditConfirmation: '',
      classNameEditConfirmation: '',
      nameSearch: ''
    }
  },
  props: {
  },
  emits: ['clickSaveSearch'],
  computed: {
    filtersSanitized () {
      const filters = Object.entries(this.sharedState.input.filters).filter(([key, value]) => {
        if (['isImported', 'isZonesDisplayed', 'showMyAffectationOnly', 'showMySignalementsOnly', 'showWithoutAffectationOnly'].includes(key)) {
          return false
        }
        if (value !== null) {
          if (Array.isArray(value)) {
            return value.length > 0
          }
          return true
        }
        return false
      })

      return Object.fromEntries(filters)
    }
  },
  methods: {
    getBadgeFilterLabel (key: string, value: any) {
      return buildBadge(key, value)
    },
    saveSearch () {
      // TODO : gérer l'appel et le retour ici pour gérer les messages d'erreur / succès
      this.$emit('clickSaveSearch', {
        name: this.nameSearch,
        params: this.filtersSanitized
      })
    },
  }
})
</script>
<style>
</style>
