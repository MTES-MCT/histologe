<template>
  <section class="fr-background--white" :style="{ display: isVisible ? 'block' : 'none' }">
    <div class="fr-grid-row fr-p-3w fr-pb-6w">
      <div class="fr-col-12">
        <h1 class="fr-mb-2w fr-h2">Liste des signalements</h1>
        <div class="fr-container--fluid">
          <div class="fr-grid-row fr-grid-row--gutters">
            <ul class="fr-col-12 fr-tags-group fr-mt-2w">
              <li>
                <button class="fr-tag" aria-pressed="false" v-if="showWorkInProgress">Afficher mes affectations uniquement</button>
              </li>
              <li>
                <button
                    class="fr-tag"
                    aria-pressed="sharedState.input.filters.isImported === 'oui'"
                    @click="toggleIsImported"
                >Afficher les signalements importés
                </button>
              </li>
            </ul>
          </div>
          <div class="fr-grid-row fr-grid-row--gutters">
            <div v-if="sharedState.user.isAdmin" class="fr-col-12 fr-col-lg-6 fr-col-xl-3 grey-background">
              <HistoSelect
                  v-if="sharedState.territories.length > 0"
                  id="filter-territoires"
                  v-model="sharedState.input.filters.territoires"
                  @update:modelValue="updateTerritory"
                  inner-label="Territoire"
                  :option-items=sharedState.territories
              />
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-6">
              <HistoSearch
                  id="filter-search-terms"
                  v-model="sharedState.input.filters.searchTerms"
                  :placeholder="'Taper un nom, référence ou email'"
                  :minLengthSearch=3
                  @update:modelValue="onChange(false)"
              />
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3 grey-background">
              <HistoSelect
                  id="filter-status"
                  v-model="sharedState.input.filters.status"
                  @update:modelValue="onChange(false)"
                  :option-items=statusSignalementList
                  :placeholder="'Statut'"
              />
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-4">
              <HistoAutoComplete
                  id="filter-communes"
                  v-model="sharedState.input.filters.communes"
                  :suggestions="sharedState.communes"
                  :multiple="true"
                  :placeholder="'Commune ou code postal'"
                  @update:modelValue="onChange(false)"
              />
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-4">
              <HistoAutoComplete
                  id="filter-epci"
                  v-model="sharedState.input.filters.epcis"
                  :suggestions="sharedState.epcis"
                  :multiple="true"
                  :placeholder="'EPCI'"
                  @update:modelValue="onChange(false)"
              />
            </div>
          </div>
          <div class="fr-grid-row fr-grid-row--gutters">
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-2">
              <button class="fr-btn fr-btn--secondary" @click="showOptions = !showOptions">
                {{ showOptions ? 'Masquer les options' : 'Afficher les options' }}
              </button>
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-8">
              <ul class="fr-tags-group">
                <li>
                  <button
                      class="fr-tag fr-tag--sm fr-tag--dismiss"
                      v-for="(filter, key) in filtersSanitized"
                      :aria-label="`Retirer ${key}`"
                      :key="key"
                      @click="removeFilter(filter, key)"
                  >
                    {{ getTextFromList(filter, key) }}
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

          <div v-if="showOptions">
            <div class="fr-grid-row fr-grid-row--gutters">
              <div class="fr-col-12 fr-col-lg-4 fr-col-xl-3 grey-background"
                   v-if="showWorkInProgress &&  sharedState.partenaires.length > 0">
                <HistoMultiSelect
                    v-if="showWorkInProgress"
                    id="filter-partenaires"
                    v-model="sharedState.input.filters.partenaires"
                    @update:modelValue="onChange(false)"
                    inner-label="Partenaires"
                    :option-items=sharedState.partenaires
                    :isInnerLabelFemale="false"
                    :active=true
                />
              </div>
              <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3 grey-background"
                   v-if="showWorkInProgress && sharedState.etiquettes.length > 0">
                <HistoMultiSelect
                    id="filter-etiquettes"
                    v-model="sharedState.input.filters.etiquettes"
                    @update:modelValue="onChange(false)"
                    inner-label="Etiquettes"
                    :option-items=sharedState.etiquettes
                    :active=true
                />
              </div>
              <div v-if="sharedState.user.canSeeStatusAffectation" class="fr-col-12 fr-col-lg-4 fr-col-xl-3 grey-background">
                <HistoSelect
                    id="filter-statut-affectation"
                    v-model="sharedState.input.filters.statusAffectation"
                    @update:modelValue="onChange(false)"
                    :option-items=statusAffectationList
                    :placeholder="'Statut de l\'affectation'"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3 grey-background">
                <HistoDatePicker
                    id="histofiltersdatepicker"
                    ref="histofiltersdatepicker"
                    v-model="sharedState.input.filters.dateDepot"
                    :placeholder="'Date de dépot'"
                    @update:modelValue="onChange(false)"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-4 fr-col-xl-3 grey-background">
                <HistoSelect
                    id="filter-type-declarant"
                    v-model="sharedState.input.filters.typeDeclarant"
                    @update:modelValue="onChange(false)"
                    :option-items=typeDeclarantList
                    :placeholder="'Déclarant'"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3 grey-background">
                <HistoSelect
                    id="filter-procedure"
                    v-model="sharedState.input.filters.procedure"
                    @update:modelValue="onChange(false)"
                    :option-items=procedureList
                    :placeholder="'Procédure suspectée'"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3 grey-background">
                <HistoSelect
                    id="filter-status-visite"
                    v-model="sharedState.input.filters.visiteStatus"
                    @update:modelValue="onChange(false)"
                    :option-items=statusVisiteList
                    :placeholder="'Statut de la visite'"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3 grey-background">
                <HistoSelect
                    id="filter-type-derniersuivi"
                    v-model="sharedState.input.filters.typeDernierSuivi"
                    @update:modelValue="onChange(false)"
                    :option-items=typeDernierSuiviList
                    :placeholder="'Type dernier suivi'"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3 grey-background">
                <HistoDatePicker
                    id="filter-date-dernier-suivi"
                    ref="filter-date-dernier-suivi"
                    v-model="sharedState.input.filters.dateDernierSuivi"
                    :placeholder="'Date de dernier suivi'"
                    @update:modelValue="onChange(false)"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-4 fr-col-xl-2 grey-background">
                <HistoSelect
                    id="filter-type-declarant"
                    v-model="sharedState.input.filters.natureParc"
                    @update:modelValue="onChange(false)"
                    :option-items=natureParcList
                    :placeholder="'Nature du parc'"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-4 fr-col-xl-2 grey-background">
                <HistoSelect
                    id="filter-allocataire"
                    v-model="sharedState.input.filters.allocataire"
                    @update:modelValue="onChange(false)"
                    :option-items=allocataireList
                    :placeholder="'Allocataire'"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-4 fr-col-xl-2 grey-background">
                <HistoSelect
                    id="filter-enfants-m6"
                    v-model="sharedState.input.filters.enfantsM6"
                    @update:modelValue="onChange(false)"
                    :option-items=enfantMoinsSixList
                    :placeholder="'Enfant de - 6 ans'"
                />
              </div>
              <div class="fr-col-12 fr-col-lg-4 fr-col-xl-2 grey-background">
                <HistoSelect
                    id="filter-situation"
                    v-model="sharedState.input.filters.situation"
                    @update:modelValue="onChange(false)"
                    :option-items=situationList
                    :placeholder="'Situation'"
                />
              </div>
              <div v-if="sharedState.user.canSeeScore"
                   class="histo-score-range fr-col-12 fr-col-lg-4 fr-col-xl-4 grey-background">
                <HistoNumber
                    id="filter-score-min"
                    v-model="sharedState.input.filters.criticiteScoreMin"
                    placeholder="Score de criticité min"
                    @update:modelValue="onChange(false)"

                />
                <HistoNumber
                    id="filter-score-max"
                    v-model="sharedState.input.filters.criticiteScoreMax"
                    placeholder="Score de criticité max"
                    @update:modelValue="onChange(false)"
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
import HistoSelect from '../common/HistoSelect.vue'
import { store } from './store'
import HistoDatePicker from '../common/external/HistoDatePicker.vue'
import HistoSearch from '../common/HistoSearch.vue'
import HistoMultiSelect from '../common/HistoMultiSelect.vue'
import HistoAutoComplete from '../common/HistoAutoComplete.vue'
import HistoNumber from '../common/HistoNumber.vue'

export default defineComponent({
  name: 'TheHistoSignalementListFilter',
  components: {
    HistoNumber,
    HistoAutoComplete,
    HistoMultiSelect,
    HistoSearch,
    HistoDatePicker,
    HistoSelect
  },
  props: {
    onChange: { type: Function }
  },
  emits: ['changeTerritory'],
  computed: {
    isImportedPressed () {
      return this.sharedState.input.filters.isImported === 'oui'
    },
    filtersSanitized () {
      const filters = Object.entries(this.sharedState.input.filters).filter(([key, value]) => {
        if (key === 'isImported') {
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
        : ''
      if (typeof this.onChange === 'function') {
        this.onChange(false)
      }
    },
    removeFilter (filter: string, index: string) {
      delete (this.sharedState.input.filters as any)[index]
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
      this.sharedState.input.filters = {
        territoires: [],
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
        statusAffectation: null,
        criticiteScoreMin: null,
        criticiteScoreMax: null
      }
      if (typeof this.onChange === 'function') {
        this.onChange(false)
      }
    },
    getTextFromList (id: any, context: string|null) {
      return store.getTextFromList(id, context)
    }
  },
  data () {
    return {
      isVisible: true,
      showOptions: false,
      showWorkInProgress: false,
      sharedState: store.state,
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
    .histo-multi-select .selector {
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
