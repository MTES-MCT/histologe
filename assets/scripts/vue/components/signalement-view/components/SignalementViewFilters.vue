<template>
  <div :class="[defineCssBloc1(), 'fr-p-1w']">
    <ul class="fr-col-12 fr-tags-group fr-mt-2w">
      <li v-if="sharedState.user.canSeeMySignalementsButton">
        <button class="fr-tag"
                ref="mySignalementsButton"
                :aria-pressed="ariaPressed.showMySignalementsOnly.toString()"
                @click="toggleCurrentUserSignalements">
          Afficher uniquement mes dossiers
        </button>
      </li>
      <!-- TODO: Remove button when FEATURE_NEW_DASHBOARD is removed -->
      <li v-if="sharedState.user.isResponsableTerritoire">
        <button class="fr-tag"
                ref="myAffectationButton"
                :aria-pressed="ariaPressed.showMyAffectationOnly.toString()"
                @click="toggleCurrentPartnerAffectation">
          Afficher mes affectations uniquement
        </button>
      </li>
      <li v-if="sharedState.user.canSeeFilterPartner">
        <button class="fr-tag"
                ref="withoutAffectationButton"
                :aria-pressed="ariaPressed.showWithoutAffectationOnly.toString()"
                @click="toggleWithoutAffectation">
          Afficher les signalements sans affectations uniquement
        </button>
      </li>
      <li v-if="sharedState.hasSignalementImported">
        <button
            ref="isImportedButton"
            class="fr-tag"
            :aria-pressed="ariaPressed.isImported.toString()"
            @click="toggleIsImported"
        >Afficher les signalements importés
        </button>
      </li>
      <li v-if="sharedState.zones.length > 0 && viewType === 'carto'">
        <button
            ref="isZonesDisplayedButton"
            class="fr-tag"
            :aria-pressed="ariaPressed.isZonesDisplayed.toString()"
            @click="toggleIsZonesDisplayed"
        >Afficher les zones du territoire
        </button>
      </li>
    </ul>
  </div>
  <div :class="defineCssBloc1()">
    <div v-if="sharedState.user.isAdmin || sharedState.user.isMultiTerritoire" :class="[defineCssBlocMultiTerritoire(2,2), 'grey-background']">
      <HistoSelect
        v-if="sharedState.territories.length > 0"
        id="filter-territoire"
        v-model="sharedState.input.filters.territoire"
        @update:modelValue="updateTerritory"
        title="Rechercher par territoire"
        :option-items=sharedState.territories
        :placeholder="'Tous'"
      >
        <template #label>Territoire</template>
      </HistoSelect>
    </div>
    <div :class="defineCssBlocMultiTerritoire(3,4)">
      <AppSearch
        id="filter-search-terms"
        v-model="sharedState.input.filters.searchTerms"
        :placeholder="'Taper un nom, référence ou email'"
        title="Taper un nom, référence ou email"
        :minLengthSearch=3
        @update:modelValue="onChange(false)"
      >
        <template #label>Recherche</template>
      </AppSearch>
    </div>
    <div :class="defineCssBlocMultiTerritoire(2,3)">
      <AppAutoComplete
        id="filter-communes"
        v-model="sharedState.input.filters.communes"
        :suggestions="sharedState.communes"
        :initSelectedSuggestions="sharedState.input.filters.communes"
        :multiple="true"
        @update:modelValue="onChange(false)"
        :reset="reset"
        :iconClass="'fr-icon-map-pin-2-line'"
      >
        <template #label>Commune ou code postal</template>
      </AppAutoComplete>
    </div>
    <div :class="defineCssBlocMultiTerritoire(3,3)">
      <AppAutoComplete
        id="filter-epci"
        v-model="sharedState.input.filters.epcis"
        :suggestions="sharedState.epcis"
        :initSelectedSuggestions="sharedState.input.filters.epcis"
        :multiple="true"
        :placeholder="'EPCI (Établissement public de coopération intercommunale)'"
        title="EPCI (Établissement public de coopération intercommunale)"
        @update:modelValue="onChange(false)"
        :reset="reset"
        :iconClass="'fr-icon-map-pin-2-line'"
      >
        <template #label>EPCI</template>
      </AppAutoComplete>
    </div>
    <div :class="[defineCssBlocMultiTerritoire(2,2), 'grey-background']">
      <HistoSelect
        id="filter-status"
        v-model="sharedState.input.filters.status"
        @update:modelValue="onChange(false)"
        :option-items=statusSignalementList
        title="Rechercher par statut"
        :placeholder="'Tous'"
      >
        <template #label>Statut</template>
      </HistoSelect>
    </div>
  </div>
  <div :class="defineCssBloc1()">
    <div :class="defineCssBlocMultiTerritoire(3,3)">
      <button class="fr-btn fr-btn--secondary" @click="sharedState.showOptions = !sharedState.showOptions">
        {{ sharedState.showOptions ? 'Masquer les options' : 'Plus d\'options de recherche' }}
      </button>
    </div>
    <div :class="defineCssBlocMultiTerritoire(7,7)">
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
    <div :class="defineCssBlocMultiTerritoire(2,2)">
      <button
          @click="resetFilters"
          class="fr-link fr-link--icon-left fr-icon-close-circle-line fr-text--sm">
        Réinitialiser les résultats
      </button>
    </div>
  </div>
  <div v-if="sharedState.showOptions">
    <div :class="defineCssBloc1()">
      <div :class="[defineCssBlocMultiTerritoire(3,3), 'grey-background']"
            v-if="sharedState.etiquettes.length > 0">
        <HistoMultiSelect
          id="filter-etiquettes"
          v-model="sharedState.input.filters.etiquettes"
          @update:modelValue="onChange(false)"
          :option-items=sharedState.etiquettes
          title="Rechercher par étiquettes"
          :active=true
          >
          <template #label>Etiquettes</template>
        </HistoMultiSelect>
      </div>
      <div :class="[defineCssBlocMultiTerritoire(3,3), 'grey-background']">
        <HistoDatePicker
          id="filter-date-depot"
          ref="filter-date-depot"
          v-model="sharedState.input.filters.dateDepot"
          title="Rechercher par date de dépot"
          @update:modelValue="onChange(false)"
          >
          <template #label>Date de dépot</template>
        </HistoDatePicker>
      </div>
      <div :class="[defineCssBlocMultiTerritoire(3,3), 'grey-background']">
        <HistoSelect
          id="filter-procedure"
          v-model="sharedState.input.filters.procedure"
          @update:modelValue="onChange(false)"
          :option-items=procedureList
          title="Rechercher par procédure suspectée"
          :placeholder="'Toutes'"
          >
          <template #label>Procédure suspectée</template>
        </HistoSelect>
      </div>
      <div :class="[defineCssBlocMultiTerritoire(3,3), 'grey-background']">
        <HistoSelect
          id="filter-procedure-constatee"
          v-model="sharedState.input.filters.procedureConstatee"
          @update:modelValue="onChange(false)"
          :option-items=procedureConstateeList
          title="Rechercher par procédure constatée"
          :placeholder="'Toutes'"
          >
          <template #label>Procédure constatée</template>
        </HistoSelect>
      </div>
      <div :class="defineCssBlocAgent(3, 3)"
            v-if="sharedState.user.canSeeFilterPartner && sharedState.partenaires.length > 0">
        <HistoMultiSelect
          id="filter-partenaires"
          v-model="sharedState.input.filters.partenaires"
          @update:modelValue="selectPartnerInList()"
          :option-items=sharedState.partenaires
          :isInnerLabelFemale="false"
          :active=true
          title="Rechercher par partenaire"
          >
          <template #label>Partenaires</template>
        </HistoMultiSelect>
      </div>
      <div :class="[defineCssBlocMultiTerritoire(3,3), 'grey-background']">
        <HistoSelect
          id="filter-status-visite"
          v-model="sharedState.input.filters.visiteStatus"
          @update:modelValue="onChange(false)"
          :option-items=statusVisiteList
          title="Rechercher par statut de visite"
          :placeholder="'Tous'"
          >
          <template #label>Statut de la visite</template>
        </HistoSelect>
      </div>
      <div :class="[defineCssBlocMultiTerritoire(3,3), 'grey-background']">
        <HistoSelect
          id="filter-type-derniersuivi"
          v-model="sharedState.input.filters.typeDernierSuivi"
          @update:modelValue="onChange(false)"
          :option-items=typeDernierSuiviList
          title="Rechercher par type dernier suivi"
          :placeholder="'Tous'"
          >
          <template #label>Type de dernier suivi</template>
        </HistoSelect>
      </div>
      <div :class="[defineCssBlocMultiTerritoire(3,3), 'grey-background']">
        <HistoDatePicker
          id="filter-date-dernier-suivi"
          ref="filter-date-dernier-suivi"
          v-model="sharedState.input.filters.dateDernierSuivi"
          title="Rechercher par date de dernier suivi"
          @update:modelValue="onChange(false)"
          >
          <template #label>Date de dernier suivi</template>
        </HistoDatePicker>
      </div>
      <div v-if="sharedState.user.canSeeStatusAffectation" :class="defineCssBlocAgent(3, 3)">
        <HistoSelect
          id="filter-statut-affectation"
          v-model="sharedState.input.filters.statusAffectation"
          @update:modelValue="onChange(false)"
          :option-items=statusAffectationList
          title="Rechercher par statut de l'affectation"
          :placeholder="'Tous'"
          >
          <template #label>Statut de l'affectation</template>
        </HistoSelect>
      </div>
      <div v-if="sharedState.user.canSeeScore"
          :class="['histo-score-range', defineCssBlocAgent(2, 2)]">
        <AppNumber
            id="filter-score-min"
            v-model="sharedState.input.filters.criticiteScoreMin"
            title="Rechercher par un score de criticité minimum"
            @update:modelValue="onChange(false)"
          >
          <template #label>Min Criticité</template>
        </AppNumber>
        <AppNumber
            id="filter-score-max"
            v-model="sharedState.input.filters.criticiteScoreMax"
            title="Rechercher par un score de criticité maximum"
            @update:modelValue="onChange(false)"
          >
          <template #label>Max Criticité</template>
        </AppNumber>
      </div>
      <div :class="defineCssBlocAgent(3, 2)">
        <HistoSelect
          id="filter-type-declarant"
          v-model="sharedState.input.filters.typeDeclarant"
          @update:modelValue="onChange(false)"
          :option-items=typeDeclarantList
          :placeholder="'Tous'"
          title="Rechercher par déclarant"
          >
          <template #label>Type de déclarant</template>
        </HistoSelect>
      </div>
      <div :class="defineCssBlocAgent(3, 2)">
        <HistoSelect
          id="filter-nature-parc"
          v-model="sharedState.input.filters.natureParc"
          @update:modelValue="onChange(false)"
          :option-items=natureParcList
          :placeholder="'Tous'"
          title="Rechercher par nature du parc"
          >
          <template #label>Nature du parc</template>
        </HistoSelect>
      </div>
      <div v-if="sharedState.user.canSeeBailleurSocial && sharedState.bailleursSociaux.length > 0"
        :class="defineCssBlocAgent(3, 3)">
        <HistoSelect
          id="filter-bailleur-social"
          v-model="sharedState.input.filters.bailleurSocial"
          @update:modelValue="onChange(false)"
          :option-items=sharedState.bailleursSociaux
          :placeholder="'Tous'"
          title="Rechercher par bailleur social"
          >
          <template #label>Bailleur social</template>
        </HistoSelect>
      </div>
      <div :class="defineCssBlocAgent(3, 2)">
        <HistoSelect
          id="filter-allocataire"
          v-model="sharedState.input.filters.allocataire"
          @update:modelValue="onChange(false)"
          :option-items=allocataireList
          :placeholder="'Tous'"
          title="Rechercher par allocataire"
          >
          <template #label>Statut d'allocataire</template>
        </HistoSelect>
      </div>
      <div :class="defineCssBlocAgent(3, 2)">
        <HistoSelect
          id="filter-enfants-m6"
          v-model="sharedState.input.filters.enfantsM6"
          @update:modelValue="onChange(false)"
          :option-items=enfantMoinsSixList
          :placeholder="'Tous'"
          title="Rechercher par enfant de moins de 6 ans"
          >
          <template #label>Enfant de moins 6 ans</template>
        </HistoSelect>
      </div>
      <div :class="defineCssBlocAgent(3, 2)">
        <HistoSelect
          id="filter-situation"
          v-model="sharedState.input.filters.situation"
          @update:modelValue="onChange(false)"
          :option-items=situationList
          :placeholder="'Tous'"
          title="Rechercher par situation"
          >
          <template #label>Type de situation</template>
        </HistoSelect>
      </div>
      <div :class="[defineCssBlocMultiTerritoire(3,3), 'grey-background']"
            v-if="sharedState.zones.length > 0">
        <HistoMultiSelect
          id="filter-zones"
          v-model="sharedState.input.filters.zones"
          @update:modelValue="onChange(false)"
          :option-items=sharedState.zones
          title="Rechercher par zones"
          :active=true
          >
          <template #label>Zones</template>
        </HistoMultiSelect>
      </div>
      <div :class="[defineCssBlocMultiTerritoire(3,3), 'grey-background']">
        <HistoSelect
          id="filter-motif-cloture"
          v-model="sharedState.input.filters.motifCloture"
          @update:modelValue="onChange(false)"
          :option-items=motifClotureList
          title="Rechercher par motif de clôture"
          :placeholder="'Tous'"
          >
          <template #label>Motif de clôture</template>
        </HistoSelect>
      </div>
    </div>
  </div>
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
import { removeLocalStorage } from '../utils/signalementUtils'


export default defineComponent({
  name: 'SignalementViewFilters',
  components: {
    AppNumber,
    AppAutoComplete,
    AppSearch,
    HistoMultiSelect,
    HistoDatePicker,
    HistoSelect
  },
  props: {
    layout: {
      type: String,
      required: false,
      default: 'horizontal'
    },
    viewType: {
      type: String,
      required: false,
      default: 'list'
    },
    onChange: { type: Function }
  },
  emits: ['changeTerritory', 'clickReset'],
  computed: {
    filtersSanitized () {
      const filters = Object.entries(this.sharedState.input.filters).filter(([key, value]) => {
        if (['isImported', 'isZonesDisplayed', 'showMyAffectationOnly', 'showMySignalementsOnly', 'showWithoutAffectationOnly', 'isMessagePostCloture'].includes(key)) {
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
    defineCssBloc1 () {
      if (this.layout === 'vertical'){
        return 'filters-blocs'
      } else {
        return 'fr-grid-row fr-grid-row--gutters'
      }
    },
    defineCssBlocMultiTerritoire (xlMultiTerritoire: number, xlOthers: number) {
      if (this.layout === 'vertical'){
        return 'filters-sidebar'
      } else {
        let css = 'fr-col-12 fr-col-lg-6 '
        if (this.sharedState.user.isAdmin || this.sharedState.user.isMultiTerritoire) {
          css += 'fr-col-xl-'+xlMultiTerritoire
        } else {
          css += 'fr-col-xl-'+xlOthers
        }
        return css
      }
    },  
    defineCssBlocAgent (xlAgent: number, xlOthers: number) {
      if (this.layout === 'vertical'){
        return 'filters-sidebar'
      } else {
        let css = 'fr-col-12 fr-col-lg-4 grey-background '
        if (this.sharedState.user.isAgent) {
          css += 'fr-col-xl-'+xlAgent
        } else {
          css += 'fr-col-xl-'+xlOthers
        }
        return css
      }
    },  
    toggleIsImported () {
      this.sharedState.input.filters.isImported = this.sharedState.input.filters.isImported !== 'oui'
        ? 'oui'
        : null
      if (typeof this.onChange === 'function') {
        this.onChange(false)
      }
    },
    toggleIsZonesDisplayed () {
      this.sharedState.input.filters.isZonesDisplayed = this.sharedState.input.filters.isZonesDisplayed !== 'oui'
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
        this.deactiveWithoutAffectationsOnly()
        this.deactiveMySignalementsOnly()
        const currentPartners = this.sharedState.partenaires.filter((partner: HistoInterfaceSelectOption) => {
          for (const partnerId of this.sharedState.user.partnerIds) {
            if (partner.Id?.toString() === partnerId.toString()) {
              return true
            }
          }
        })
        this.sharedState.input.filters.partenaires = currentPartners.map(partner => partner.Id)
      }

      if (typeof this.onChange === 'function') {
        this.onChange(false)
      }
    },
    toggleCurrentUserSignalements () {
      this.sharedState.input.filters.partenaires = []
      this.sharedState.input.filters.showMySignalementsOnly =
          this.sharedState.input.filters.showMySignalementsOnly !== 'oui' ? 'oui' : null

      if (this.sharedState.input.filters.showMySignalementsOnly === 'oui') {
        this.deactiveWithoutAffectationsOnly()
        this.deactiveMyAffectationsOnly()
      }

      if (typeof this.onChange === 'function') {
        this.onChange(false)
      }
    },
    toggleWithoutAffectation () {
      this.sharedState.input.filters.partenaires = []
      this.sharedState.input.filters.showWithoutAffectationOnly =
          this.sharedState.input.filters.showWithoutAffectationOnly !== 'oui' ? 'oui' : null

      if (this.sharedState.input.filters.showWithoutAffectationOnly === 'oui') {
        this.deactiveMyAffectationsOnly()
        this.deactiveMySignalementsOnly()
        this.sharedState.input.filters.partenaires = ['AUCUN']
      } else {
        delete this.sharedState.input.filters.partenaires[0]
      }

      if (typeof this.onChange === 'function') {
        this.onChange(false)
      }
    },
    deactiveMyAffectationsOnly () {
      this.sharedState.input.filters.showMyAffectationOnly = null
      if (this.$refs.myAffectationButton) {
        (this.$refs.myAffectationButton as HTMLElement).setAttribute('aria-pressed', 'false')
      }
    },
    deactiveMySignalementsOnly () {
      this.sharedState.input.filters.showMySignalementsOnly = null
      if (this.$refs.mySignalementsButton) {
        (this.$refs.mySignalementsButton as HTMLElement).setAttribute('aria-pressed', 'false')
      }
    },
    deactiveWithoutAffectationsOnly () {
      this.sharedState.input.filters.showWithoutAffectationOnly = null
      if (this.$refs.withoutAffectationButton) {
        (this.$refs.withoutAffectationButton as HTMLElement).setAttribute('aria-pressed', 'false')
      }
    },
    selectPartnerInList () {
      this.sharedState.input.filters.partenaires = this.sharedState.input.filters.partenaires.filter(partenaire => partenaire !== 'AUCUN')
      this.deactiveWithoutAffectationsOnly()
      if (typeof this.onChange === 'function') {
        this.onChange(false)
      }
    },
    removeFilter (key: string) {
      const showMyAffectationOnly = this.sharedState.input.filters.showMyAffectationOnly
      if (showMyAffectationOnly === 'oui' && key === 'partenaires') {
        this.deactiveMyAffectationsOnly()
      }
      if (this.sharedState.input.filters.showWithoutAffectationOnly === 'oui') {
        this.deactiveWithoutAffectationsOnly()
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
        zones: [],
        partenaires: [],
        communes: [],
        epcis: [],
        searchTerms: null,
        status: null,
        procedure: null,
        procedureConstatee: null,
        visiteStatus: null,
        typeDernierSuivi: null,
        typeDeclarant: null,
        natureParc: null,
        bailleurSocial: null,
        allocataire: null,
        enfantsM6: null,
        situation: null,
        dateDepot: null,
        dateDernierSuivi: null,
        isImported: null,
        isZonesDisplayed: null,
        showMyAffectationOnly: null,
        showMySignalementsOnly: null,
        isMessagePostCloture: null,
        isNouveauMessage: null,
        isMessageWithoutResponse: null,
        showWithoutAffectationOnly: null,
        statusAffectation: null,
        criticiteScoreMin: null,
        criticiteScoreMax: null,
        motifCloture: null,
        relanceUsagerSansReponse: null
      }
      this.sharedState.currentTerritoryId = ''

      if (this.$refs.myAffectationButton) {
        (this.$refs.myAffectationButton as HTMLElement).setAttribute('aria-pressed', 'false')
      }

      if (this.$refs.mySignalementsButton) {
        (this.$refs.mySignalementsButton as HTMLElement).setAttribute('aria-pressed', 'false')
      }

      if (this.$refs.withoutAffectationButton) {
        (this.$refs.withoutAffectationButton as HTMLElement).setAttribute('aria-pressed', 'false')
      }

      if (this.$refs.isImportedButton) {
        (this.$refs.isImportedButton as HTMLElement).setAttribute('aria-pressed', 'false')
      }

      if (this.$refs.isZonesDisplayedButton) {
        (this.$refs.isZonesDisplayedButton as HTMLElement).setAttribute('aria-pressed', 'false')
      }

      this.reset = !this.reset
      this.$emit('clickReset')
      removeLocalStorage(this)

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
      sharedProps: store.props,
      ariaPressed: {
        isImported: store.state.input.filters.isImported === 'oui',
        isZonesDisplayed: store.state.input.filters.isZonesDisplayed === 'oui',
        showMyAffectationOnly: store.state.input.filters.showMyAffectationOnly === 'oui',
        showMySignalementsOnly: store.state.input.filters.showMySignalementsOnly === 'oui',
        showWithoutAffectationOnly: store.state.input.filters.showWithoutAffectationOnly === 'oui'
      },
      statusSignalementList: store.state.statusSignalementList,
      statusAffectationList: store.state.statusAffectationList,
      statusVisiteList: store.state.statusVisiteList,
      situationList: store.state.situationList,
      procedureList: store.state.procedureList,
      procedureConstateeList: store.state.procedureConstateeList,
      typeDernierSuiviList: store.state.typeDernierSuiviList,
      typeDeclarantList: store.state.typeDeclarantList,
      natureParcList: store.state.natureParcList,
      allocataireList: store.state.allocataireList,
      enfantMoinsSixList: store.state.enfantMoinsSixList,
      motifClotureList: store.state.motifClotureList
    }
  }
})
</script>
<style>
  .grey-background {
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

  .filters-vertical {
    display: flex;
    flex-direction: column;
  }

  .filters-horizontal {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
  }

  .filters-sidebar {
    display: flex;
    flex-direction: column;
    width: 100%;
    background-color: #f6f6f6;
    padding: 0.25rem 0.75rem 0.5rem;
  }

  .filters-blocs {
    width: 100%;
    background-color: #f6f6f6;
  }
</style>
