<template>
  <section class="fr-background--white" :style="{ display: isVisible ? 'block' : 'none' }">
    <div class="fr-grid-row fr-p-3w fr-pb-6w">
      <div class="fr-col-12">
        <h1 class="fr-mb-2w fr-h2">Liste des signalements</h1>
        <div class="fr-container--fluid" role="search">
          <div class="fr-grid-row fr-grid-row--gutters">
            <ul class="fr-col-12 fr-tags-group fr-mt-2w">
              <li v-if="sharedState.user.isResponsableTerritoire">
                <button class="fr-tag"
                        ref="myAffectationButton"
                        :aria-pressed="ariaPressed.showMyAffectationOnly.toString()"
                        @click="toggleCurrentPartnerAffectation">
                  Afficher mes affectations uniquement
                </button>
              </li>
              <li>
                <button
                    v-if="sharedState.hasSignalementImported"
                    ref="isImportedButton"
                    class="fr-tag"
                    :aria-pressed="ariaPressed.isImported.toString()"
                    @click="toggleIsImported"
                >Afficher les signalements importés
                </button>
              </li>
            </ul>
          </div>
          <div class="fr-grid-row fr-grid-row--gutters">
            <div v-if="sharedState.user.isAdmin" class="fr-col-12 fr-col-lg-6 fr-col-xl-2 grey-background">
              <HistoSelect
                  v-if="sharedState.territories.length > 0"
                  id="filter-territoire"
                  v-model="sharedState.input.filters.territoire"
                  @update:modelValue="updateTerritory"
                  inner-label="Territoire"
                  title="Rechercher par territoire"
                  :option-items=sharedState.territories
                  :placeholder="'Territoire'"
              />
            </div>
            <div class="fr-col-12 fr-col-lg-6" :class="sharedState.user.isAdmin ? 'fr-col-xl-3' : 'fr-col-xl-4'">
              <AppSearch
                  id="filter-search-terms"
                  v-model="sharedState.input.filters.searchTerms"
                  :placeholder="'Taper un nom, référence ou email'"
                  :minLengthSearch=3
                  title="Rechercher par nom, référence ou email"
                  @update:modelValue="onChange(false)"
              />
            </div>
            <div class="fr-col-12 fr-col-lg-6" :class="sharedState.user.isAdmin ? 'fr-col-xl-2' : 'fr-col-xl-3'">
              <AppAutoComplete
                  id="filter-communes"
                  v-model="sharedState.input.filters.communes"
                  :suggestions="sharedState.communes"
                  :initSelectedSuggestions="sharedState.input.filters.communes"
                  :multiple="true"
                  :placeholder="'Commune ou code postal'"
                  @update:modelValue="onChange(false)"
                  :reset="reset"
                  class="fr-input-wrap fr-icon-map-pin-2-line"
              />
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
              <AppAutoComplete
                  id="filter-epci"
                  v-model="sharedState.input.filters.epcis"
                  :suggestions="sharedState.epcis"
                  :initSelectedSuggestions="sharedState.input.filters.epcis"
                  :multiple="true"
                  :placeholder="'EPCI (Établissement public de coopération intercommunale)'"
                  @update:modelValue="onChange(false)"
                  :reset="reset"
                  title="Rechercher par EPCI (Établissement public de coopération intercommunale)"
                  class="fr-input-wrap fr-icon-map-pin-2-line"
              />
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-2 grey-background">
              <HistoSelect
                  id="filter-status"
                  v-model="sharedState.input.filters.status"
                  @update:modelValue="onChange(false)"
                  :option-items=statusSignalementList
                  title="Rechercher par statut"
                  :placeholder="'Statut'"
              />
            </div>
          </div>
          <div class="fr-grid-row fr-grid-row--gutters">
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
              <button class="fr-btn fr-btn--secondary" @click="sharedState.showOptions = !sharedState.showOptions">
                {{ sharedState.showOptions ? 'Masquer les options' : 'Plus d\'options de recherche' }}
              </button>
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-7">
              <ul class="fr-tags-group">
                <li v-for="(value, key) in filtersSanitized"  :key="key">
                  <button
                      v-if="value.length > 0"
                      class="fr-tag fr-tag--sm fr-tag--dismiss"
                      :aria-label="`Retirer ${key}`"
                      @click="removeFilter(key)"
                  >
                    {{ getBadgeFilterLabel(key, value) }}
                  </button>
                </li>
              </ul>
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-2">
              <button
                  @click="resetFilters"
                 class="fr-link fr-link--icon-left fr-icon-close-circle-line fr-text--sm">
                Réinitialiser les résultats
              </button>
            </div>
          </div>
          <div v-if="sharedState.showOptions">
            <div class="fr-grid-row fr-grid-row--gutters">
              <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3 grey-background"
                   v-if="sharedState.etiquettes.length > 0">
                <HistoMultiSelect
                    id="filter-etiquettes"
                    v-model="sharedState.input.filters.etiquettes"
                    @update:modelValue="onChange(false)"
                    inner-label="Etiquettes"
                    :option-items=sharedState.etiquettes
                    title="Rechercher par étiquettes"
                    :active=true
                />
              </div>
              <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3 grey-background">
                <HistoDatePicker
                    id="filter-date-depot"
                    ref="filter-date-depot"
                    v-model="sharedState.input.filters.dateDepot"
                    :placeholder="'Date de dépot'"
                    title="Rechercher par date de dépot"
                    @update:modelValue="onChange(false)"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3 grey-background">
                <HistoSelect
                    id="filter-procedure"
                    v-model="sharedState.input.filters.procedure"
                    @update:modelValue="onChange(false)"
                    :option-items=procedureList
                    title="Rechercher par procédure suspectée"
                    :placeholder="'Procédure suspectée'"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-4 fr-col-xl-3 grey-background"
                   v-if="sharedState.partenaires.length > 0">
                <HistoMultiSelect
                    id="filter-partenaires"
                    v-model="sharedState.input.filters.partenaires"
                    @update:modelValue="onChange(false)"
                    inner-label="Partenaires"
                    :option-items=sharedState.partenaires
                    :isInnerLabelFemale="false"
                    :active=true
                    title="Rechercher par partenaire"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3 grey-background">
                <HistoSelect
                    id="filter-status-visite"
                    v-model="sharedState.input.filters.visiteStatus"
                    @update:modelValue="onChange(false)"
                    :option-items=statusVisiteList
                    :placeholder="'Statut de la visite'"
                    title="Rechercher par statut de visite"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3 grey-background">
                <HistoSelect
                    id="filter-type-derniersuivi"
                    v-model="sharedState.input.filters.typeDernierSuivi"
                    @update:modelValue="onChange(false)"
                    :option-items=typeDernierSuiviList
                    :placeholder="'Type dernier suivi'"
                    title="Rechercher par type dernier suivi"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3 grey-background">
                <HistoDatePicker
                    id="filter-date-dernier-suivi"
                    ref="filter-date-dernier-suivi"
                    v-model="sharedState.input.filters.dateDernierSuivi"
                    :placeholder="'Date de dernier suivi'"
                    title="Rechercher par date de dernier suivi"
                    @update:modelValue="onChange(false)"
                />
              </div>
              <div v-if="sharedState.user.canSeeStatusAffectation" class="fr-col-12 fr-col-lg-4 fr-col-xl-3 grey-background">
                <HistoSelect
                    id="filter-statut-affectation"
                    v-model="sharedState.input.filters.statusAffectation"
                    @update:modelValue="onChange(false)"
                    :option-items=statusAffectationList
                    :placeholder="'Statut de l\'affectation'"
                    title="Rechercher par statut de l'affectation"
                />
              </div>
              <div v-if="sharedState.user.canSeeScore"
                   class="histo-score-range fr-col-12 fr-col-lg-4 fr-col-xl-2 grey-background">
                <AppNumber
                    id="filter-score-min"
                    v-model="sharedState.input.filters.criticiteScoreMin"
                    placeholder="Min Criticité"
                    title="Rechercher par un score de criticité minimum"
                    @update:modelValue="onChange(false)"
                />
                <AppNumber
                    id="filter-score-max"
                    v-model="sharedState.input.filters.criticiteScoreMax"
                    placeholder="Max Criticité"
                    title="Rechercher par un score de criticité maximum"
                    @update:modelValue="onChange(false)"
                />
              </div>
              <div
                  class="fr-col-12 fr-col-lg-4 grey-background"
                  :class="sharedState.user.isAgent ? 'fr-col-xl-3' : 'fr-col-xl-2'">
                <HistoSelect
                    id="filter-type-declarant"
                    v-model="sharedState.input.filters.typeDeclarant"
                    @update:modelValue="onChange(false)"
                    :option-items=typeDeclarantList
                    :placeholder="'Déclarant'"
                    title="Rechercher par déclarant"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-4 grey-background"
                   :class="sharedState.user.isAgent ? 'fr-col-xl-3' : 'fr-col-xl-2'">
                <HistoSelect
                    id="filter-nature-parc"
                    v-model="sharedState.input.filters.natureParc"
                    @update:modelValue="onChange(false)"
                    :option-items=natureParcList
                    :placeholder="'Nature du parc'"
                    title="Rechercher par nature du parc"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-4 grey-background"
                   :class="sharedState.user.isAgent ? 'fr-col-xl-3' : 'fr-col-xl-2'">
                <HistoSelect
                    id="filter-allocataire"
                    v-model="sharedState.input.filters.allocataire"
                    @update:modelValue="onChange(false)"
                    :option-items=allocataireList
                    :placeholder="'Allocataire'"
                    title="Rechercher par allocataire"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-4 grey-background"
                   :class="sharedState.user.isAgent ? 'fr-col-xl-3' : 'fr-col-xl-2'">
                <HistoSelect
                    id="filter-enfants-m6"
                    v-model="sharedState.input.filters.enfantsM6"
                    @update:modelValue="onChange(false)"
                    :option-items=enfantMoinsSixList
                    :placeholder="'Enfant de moins 6 ans'"
                    title="Rechercher par enfant de moins de 6 ans"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-4 grey-background"
                   :class="sharedState.user.isAgent ? 'fr-col-xl-3' : 'fr-col-xl-2'">
                <HistoSelect
                    id="filter-situation"
                    v-model="sharedState.input.filters.situation"
                    @update:modelValue="onChange(false)"
                    :option-items=situationList
                    :placeholder="'Situation'"
                    title="Rechercher par situation"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import AppAutoComplete from '../../common/AppAutoComplete.vue'
import AppNumber from '../../common/AppNumber.vue'
import AppSearch from '../../common/AppSearch.vue'
import HistoSelect from '../../common/HistoSelect.vue'
import HistoDatePicker from '../../common/external/HistoDatePicker.vue'
import HistoMultiSelect from '../../common/HistoMultiSelect.vue'
import { store } from '../store'
import { buildBadge } from '../services/badgeFilterLabelBuilder'
import HistoInterfaceSelectOption from '../../common/HistoInterfaceSelectOption'

export default defineComponent({
  name: 'SignalementListFilters',
  components: {
    AppNumber,
    AppAutoComplete,
    AppSearch,
    HistoMultiSelect,
    HistoDatePicker,
    HistoSelect
  },
  props: {
    onChange: { type: Function }
  },
  emits: ['changeTerritory', 'clickReset'],
  mounted () {
    const container = document.querySelector('.fr-container--fluid') as HTMLElement
    if (container) {
      container.style.overflow = 'visible'
    }
  },
  computed: {
    filtersSanitized () {
      const filters = Object.entries(this.sharedState.input.filters).filter(([key, value]) => {
        if (key === 'isImported' || key === 'showMyAffectationOnly') {
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
    toggleIsImported () {
      this.sharedState.input.filters.isImported = this.sharedState.input.filters.isImported !== 'oui'
        ? 'oui'
        : null
      if (typeof this.onChange === 'function') {
        this.onChange(false)
      }
    },
    toggleCurrentPartnerAffectation () {
      this.sharedState.input.filters.partenaires = []
      this.sharedState.input.filters.showMyAffectationOnly =
          this.sharedState.input.filters.showMyAffectationOnly !== 'oui' ? 'oui' : null

      if (this.sharedState.input.filters.showMyAffectationOnly === 'oui') {
        const currentPartner = this.sharedState.partenaires.filter((partner: HistoInterfaceSelectOption) => {
          return partner.Id === this.sharedState.user.partnerId?.toString() || ''
        })
        this.sharedState.input.filters.partenaires = [currentPartner[0].Id]
      } else {
        delete this.sharedState.input.filters.partenaires[0]
      }

      if (typeof this.onChange === 'function') {
        this.onChange(false)
      }
    },
    removeFilter (key: string) {
      const currentMyAffectationOnly = (this.sharedState.input.filters as any)[key][0]
      const showMyAffectationOnly = this.sharedState.input.filters.showMyAffectationOnly
      if (showMyAffectationOnly === 'oui' && currentMyAffectationOnly === this.sharedState.user.partnerId?.toString()) {
        this.sharedState.input.filters.showMyAffectationOnly = null
        if (this.$refs.myAffectationButton) {
          (this.$refs.myAffectationButton as HTMLElement).setAttribute('aria-pressed', 'false')
        }
      }

      delete (this.sharedState.input.filters as any)[key]
      if (typeof this.onChange === 'function') {
        this.onChange(false)
      }
    },
    updateTerritory (value: any) {
      if (typeof this.onChange === 'function') {
        this.onChange(false)
      }
      this.$emit('changeTerritory', value)
    },
    resetFilters () {
      this.sharedState.showOptions = false
      this.sharedState.input.filters = {
        territoire: null,
        etiquettes: [],
        partenaires: [],
        communes: [],
        epcis: [],
        searchTerms: null,
        status: null,
        procedure: null,
        visiteStatus: null,
        typeDernierSuivi: null,
        typeDeclarant: null,
        natureParc: null,
        allocataire: null,
        enfantsM6: null,
        situation: null,
        dateDepot: null,
        dateDernierSuivi: null,
        isImported: null,
        showMyAffectationOnly: null,
        statusAffectation: null,
        criticiteScoreMin: null,
        criticiteScoreMax: null
      }
      this.sharedState.currentTerritoryId = ''

      if (this.$refs.myAffectationButton) {
        (this.$refs.myAffectationButton as HTMLElement).setAttribute('aria-pressed', 'false')
      }

      if (this.$refs.isImportedButton) {
        (this.$refs.isImportedButton as HTMLElement).setAttribute('aria-pressed', 'false')
      }

      this.reset = !this.reset
      this.$emit('clickReset')

      if (typeof this.onChange === 'function') {
        this.onChange(false)
      }
    },
    getBadgeFilterLabel (key: string, value: any) {
      return buildBadge(key, value)
    }
  },
  data () {
    return {
      isVisible: true,
      showOptions: store.state.showOptions,
      reset: false,
      sharedState: store.state,
      ariaPressed: {
        isImported: store.state.input.filters.isImported === 'oui',
        showMyAffectationOnly: store.state.input.filters.showMyAffectationOnly === 'oui'
      },
      statusSignalementList: store.state.statusSignalementList,
      statusAffectationList: store.state.statusAffectationList,
      statusVisiteList: store.state.statusVisiteList,
      situationList: store.state.situationList,
      procedureList: store.state.procedureList,
      typeDernierSuiviList: store.state.typeDernierSuiviList,
      typeDeclarantList: store.state.typeDeclarantList,
      natureParcList: store.state.natureParcList,
      allocataireList: store.state.allocataireList,
      enfantMoinsSixList: store.state.enfantMoinsSixList
    }
  }
})
</script>
<style>
  .grey-background {
    .histo-select select {
      background-color: var(--background-contrast-grey);
    }
    .histo-date-picker input {
      background-color: var(--background-contrast-grey);
      box-shadow: inset 0 -2px 0 0 var(--border-plain-grey);
      border-radius: .25rem .25rem 0 0;
    }
  }

  .histo-score-range {
    display: flex;
    gap: 1rem;
  }
</style>
