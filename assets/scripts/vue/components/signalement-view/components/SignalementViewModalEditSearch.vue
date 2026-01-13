<template>
  <dialog aria-labelledby="modal-edit-search-title" id="modal-edit-search" class="fr-modal">
    <div class="fr-container fr-container--fluid fr-container-lg">
      <div class="fr-grid-row fr-grid-row--center">
        <div class="fr-col-12 fr-col-md-10 fr-col-lg-8">
          <div class="fr-modal__body">
            <div class="fr-modal__header">
              <button type="button" class="fr-btn--close fr-btn" aria-controls="modal-edit-search">Fermer</button>
            </div>
            <div class="fr-modal__content">
              <h1 id="modal-edit-search-title" class="fr-modal__title">
                Mes recherches sauvegardées
              </h1>
              <div v-if="classNameEditConfirmation.length > 0" class="fr-notice" :class="classNameEditConfirmation">
                <div class="fr-container">
                  <div class="fr-notice__body">
                    <p>
                      <span class="fr-notice__title">{{ messageEditConfirmation }}</span>
                    </p>
                  </div>
                </div>
              </div>

              <div v-if="sharedState.savedSearches.length === 0">
                <p>Aucune recherche sauvegardée</p>
              </div>

              <div v-else>
                <div v-for="(search, index) in sharedState.savedSearches" :key="search.Id">
                  <h2 class="fr-h4 fr-mb-2v">{{ search.Text }}</h2>

                  <div class="fr-grid-row fr-grid-row--bottom fr-mb-2v">
                    <div class="fr-col-9">
                      <div class="fr-input-group fr-mr-2v">
                        <label class="fr-label" :for="`saved-search-name-${search.Id}`">Nom de la recherche :</label>
                        <input
                          type="text"
                          class="fr-input"
                          v-model="search.NewName"
                          :disabled="!search.IsEditing"
                          :placeholder="search.Text"
                          :maxlength="50"
                          :id="`saved-search-name-${search.Id}`"
                        />
                      </div>
                    </div>
                    <div class="fr-col-3 fr-text-right">
                      <button
                        class="fr-btn fr-btn--icon-left fr-btn--secondary fr-icon-edit-line"
                        @click="toggleEdit(search)"
                      >
                        {{ search.IsEditing ? 'Valider' : 'Modifier le nom' }}
                      </button>
                    </div>
                  </div>

                  <div class="fr-mb-2v">
                    <label class="fr-label" :for="`saved-search-filters-${search.Id}`">Filtres de la recherche :</label>
                    <div class="fr-tags-group" :id="`saved-search-filters-${search.Id}`">
                      <span v-for="(value, key) in search.Params" :key="key" class="fr-tag fr-tag--sm">
                        {{ getBadgeFilterLabel(key, value) }}
                      </span>
                    </div>
                  </div>

                  <button class="fr-btn fr-btn--icon-left fr-btn--tertiary fr-icon-delete-line fr-mb-5v"
                          @click="deleteSavedSearch(search.Id)">
                    Supprimer la recherche
                  </button>
                  <hr v-if="index < sharedState.savedSearches.length - 1">
                </div>
              </div>

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
import { requests } from '../requests'
import { buildBadge } from '../services/badgeFilterLabelBuilder'
import SearchInterfaceSelectOption from '../interfaces/SearchInterfaceSelectOption';

export default defineComponent({
  name: 'SignalementViewModalEditSearch',
  components: {
  },
  data () {
    return {
      sharedProps: store.props,
      sharedState: store.state,
      messageEditConfirmation: '',
      classNameEditConfirmation: '',
      modalObserver: null as MutationObserver | null
    }
  },
  mounted() {
    const modal = document.getElementById('modal-edit-search') as HTMLDialogElement
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
  methods: {
    resetModalState() {
      this.messageEditConfirmation = ''
      this.classNameEditConfirmation = ''

      for (const search of this.sharedState.savedSearches) {
          search.NewName = (search.Text as string)
          search.IsEditing = false 
      }
    },
    getBadgeFilterLabel (key: string, value: any) {
      return buildBadge(key, value)
    },
    toggleEdit(search: SearchInterfaceSelectOption) {
      if (!search.IsEditing) {
        search.IsEditing = true
      } else {
        this.editSavedSearch(search.Id, search.NewName)

        search.IsEditing = false
      }
    },
    editSavedSearch(id: string, newName: string) {
      requests.editSearch(id, newName, this.sharedProps.csrfEditSearch, this.handleSearchEdited)
    },
    handleSearchEdited (requestResponse: any, id: string = '', newName: string = '') {
      this.messageEditConfirmation = requestResponse.data.message
      this.classNameEditConfirmation =
          requestResponse.status === 200
            ? 'fr-notice--success'
            : 'fr-notice--alert'
      if (requestResponse.status === 200) {
        const item = this.sharedState.savedSearches.find(s => s.Id === id)
        if (item) {
          item.Text = newName
          item.NewName = newName
        }

        this.sharedState.savedSearchSelectKey++
      }
    },
    deleteSavedSearch(id: string) {
      requests.deleteSearch(id, this.sharedProps.csrfDeleteSearch, this.handleSearchDeleted)
    },
    handleSearchDeleted (requestResponse: any, id: string = '') {
      this.messageEditConfirmation = requestResponse.data.message
      this.classNameEditConfirmation =
          requestResponse.status === 200
            ? 'fr-notice--success'
            : 'fr-notice--alert'
      if (requestResponse.status === 200) {
        const updatedSearches = this.sharedState.savedSearches.filter(s => s.Id !== id);
        const selectedId = this.sharedState.selectedSavedSearchId === id ? '' : this.sharedState.selectedSavedSearchId;
        this.sharedState.savedSearches = updatedSearches;
        this.sharedState.selectedSavedSearchId = selectedId;
        this.sharedState.savedSearchSelectKey++
      }
    },
  }
})
</script>