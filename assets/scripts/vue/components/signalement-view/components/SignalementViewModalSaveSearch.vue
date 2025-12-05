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
              <div v-if="classNameSaveConfirmation.length > 0" class="fr-alert fr-alert--sm" :class="classNameSaveConfirmation">
                <p>{{ messageSaveConfirmation }}</p>
              </div>
              <strong>NB : vous pouvez sauvegarder 5 recherches favorites au maximum</strong>
              <div class="fr-my-4v">
                <div class="fr-input-group fr-mr-2v">
                  <label class="fr-label" for="search-name">
                    Saisissez un nom pour votre recherche <span class="text-required">*</span>
                    <span class="fr-hint-text">Le nom sera affiché dans votre liste de recherches favorites. 50 caractères maximum.</span>
                  </label>
                  <input type="text" class="fr-input" v-model="nameSearch" :maxlength="50" id="search-name"/>
                </div>

                <div class="fr-mb-2v">
                  <label class="fr-label" for="search-filters">Votre recherche a les filtres suivants :</label>
                  <div class="fr-tags-group" id="search-filters">
                    <span v-for="(value, key) in filtersSanitized" :key="key" class="fr-tag fr-tag--sm">
                      {{ getBadgeFilterLabel(key as string, value) }}
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
import { sanitizeFilters } from '../utils/signalementUtils'
import { requests } from '../requests'
import SearchInterfaceSelectOption from '../interfaces/SearchInterfaceSelectOption'

export default defineComponent({
  name: 'SignalementViewModalSaveSearch',
  components: {
  },
  data () {
    return {
      sharedProps: store.props,
      sharedState: store.state,
      messageSaveConfirmation: '',
      classNameSaveConfirmation: '',
      nameSearch: '',
      modalObserver: null as MutationObserver | null
    }
  },
  mounted() {
    const modal = document.getElementById('modal-save-search') as HTMLDialogElement
    if (!modal) return

    const observer = new MutationObserver(mutations => {
    mutations.forEach(m => {
        if (m.attributeName === 'open') {
            this.resetModalState()
        }
      })
    })

    observer.observe(modal, { attributes: true })
    this.modalObserver = observer
  },
  beforeUnmount() {
    if (this.modalObserver) {
      this.modalObserver.disconnect()
      this.modalObserver = null
    }
  },
  computed: {
    filtersSanitized () {
      return sanitizeFilters(this.sharedState.input.filters)
    }
  },
  methods: {
    resetModalState() {
      this.messageSaveConfirmation = ''
      this.classNameSaveConfirmation = ''
    },
    getBadgeFilterLabel (key: string, value: any) {
      return buildBadge(key, value)
    },
    saveSearch () {
      const payload = {
        name: this.nameSearch,
        params: this.filtersSanitized
      }
      requests.saveSearch(
        payload, 
        this.sharedProps.csrfSaveSearch, 
        this.handleSearchSaved
      )
    },
    handleSearchSaved (requestResponse: any) {
      const message = requestResponse.data?.message || 'Erreur inconnue'
      const isSuccess = requestResponse.status === 200
      const className = isSuccess ? 'fr-alert--success' : 'fr-alert--error'

      if (isSuccess && requestResponse.data?.data?.savedSearch) {
        const saved = requestResponse.data.data.savedSearch
        const newOption = new SearchInterfaceSelectOption()
        newOption.Id = saved.id.toString()
        newOption.Text = saved.name
        newOption.NewName = saved.name
        newOption.Params = saved.params
        this.sharedState.savedSearches.push(newOption)
        this.sharedState.selectedSavedSearchId = newOption.Id
        this.sharedState.savedSearchSelectKey++
        this.nameSearch = ''
        this.$emit('savedSearchSuccess', message, className)
        const closeBtn = document.querySelector(
          'button[aria-controls="modal-save-search"].fr-btn--close'
        ) as HTMLButtonElement | null
        closeBtn?.click()
      } else {
        this.messageSaveConfirmation = message
        this.classNameSaveConfirmation = className
      }
    }
  }
})
</script>
