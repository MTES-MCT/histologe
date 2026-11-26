<template>
  <dialog aria-labelledby="panel-mass-affectation-title" id="panel-mass-affectation" class="side-panel">
    <div class="side-panel__inner">
      <div class="side-panel__header">
        <button type="button" class="fr-btn--close fr-btn js-close-panel" data-panel="panel-mass-affectation" aria-controls="panel-mass-affectation" aria-expanded="false">Fermer</button>
      </div>

      <div class="side-panel__content fr-mx-3w">
        <h1 id="panel-mass-affectation-title" class="fr-modal__title title-blue-france">
          Affecter ces signalements à un partenaire
        </h1>

        <div v-if="classNameNotice.length > 0" class="fr-notice fr-mb-3w" :class="classNameNotice">
          <div class="fr-container">
            <div class="fr-notice__body">
              <p>
                <span class="fr-notice__title">{{ titleNotice }}</span>
                <span class="fr-notice__desc">{{ messageNotice }}</span>
              </p>
            </div>
          </div>
        </div>

        <div v-if="affectablePartners.length > 0">
          <HistoSelect
            id="mass-affectation-partner"
            v-model="partnerId"
            @update:modelValue="onChoosePartner"
            :option-items="affectablePartners"
            title="Choisir le partenaire à affecter"
            :placeholder="'Choisir un partenaire'"
          >
            <template #label>Partenaire</template>
          </HistoSelect>
        </div>

        <p v-if="isAnalyzing" class="fr-mt-3w" role="status">Analyse des signalements en cours…</p>

        <div v-if="analysis !== null" class="fr-mt-3w" role="status">
          <div v-if="analysis.partnerInterconnection && analysis.signalementsToAffect.length > 0" class="fr-alert fr-alert--warning fr-alert--sm fr-mb-3w">
            <p>
              <strong>{{ selectedPartnerName }}</strong> est interconnecté :
              les signalements affectés lui seront transmis automatiquement via son interconnexion.
            </p>
          </div>

          <div v-if="analysis.signalementsToAffect.length === 0" class="fr-alert fr-alert--info fr-alert--sm fr-mb-3w">
            <p>Aucun signalement à affecter à <strong>{{ selectedPartnerName }}</strong> parmi les signalements filtrés.</p>
          </div>

          <div class="fr-accordions-group">
            <section v-for="group in analysisGroups" :key="group.id" class="fr-accordion">
              <h2 class="fr-accordion__title">
                <button
                  type="button"
                  class="fr-accordion__btn"
                  aria-expanded="false"
                  :aria-controls="group.id"
                >{{ group.title }}</button>
              </h2>
              <div class="fr-collapse" :id="group.id">
                <ul class="fr-raw-list">
                  <li v-for="signalement in group.signalements" :key="signalement.id" class="fr-mb-2w">
                    <strong>#{{ signalement.reference }}</strong> - {{ signalement.identite_occupant }} - 
                    <span class="fr-text--sm fr-mb-0">{{ signalement.adresse }}</span>
                  </li>
                </ul>
              </div>
            </section>
          </div>
        </div>
      </div>

      <div class="side-panel__footer">
        <ul class="fr-btns-group fr-btns-group--right fr-btns-group--inline-reverse fr-btns-group--inline-sm fr-btns-group--icon-left">
          <li>
            <button
              type="button"
              class="fr-btn fr-btn--icon-left fr-icon-check-line"
              :disabled="!canValidate"
              @click="validate"
            >Valider</button>
          </li>
          <li>
            <button
              type="button"
              class="fr-btn fr-btn--secondary fr-icon-close-line js-close-panel"
              data-panel="panel-mass-affectation"
              aria-controls="panel-mass-affectation"
              aria-expanded="false"
            >Annuler</button>
          </li>
        </ul>
      </div>
    </div>
  </dialog>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from '../store'
import { requests } from '../requests'
import HistoSelect from '../../common/HistoSelect.vue'
import HistoInterfaceSelectOption from '../../common/HistoInterfaceSelectOption'
import { MassAffectationAnalysis, MassAffectationSignalement, AnalysisGroup } from '../interfaces/massAffectationAnalysis'

const DEFAULT_ERROR_TITLE = 'Erreur'
const ANALYZE_ERROR_MESSAGE = 'Une erreur est survenue lors de l\'analyse des signalements. Veuillez réessayer.'

export default defineComponent({
  name: 'SignalementViewPanelMassAffectation',
  components: {
    HistoSelect
  },
  emits: ['massAffectationSuccess'],
  data () {
    return {
      sharedProps: store.props,
      sharedState: store.state,
      partnerId: '' as string,
      analysis: null as MassAffectationAnalysis | null,
      isAnalyzing: false,
      isSubmitting: false,
      titleNotice: '',
      messageNotice: '',
      classNameNotice: '',
      panelObserver: null as MutationObserver | null
    }
  },
  computed: {
    /**
     * Partenaires du filtre, sans l'option « Aucun » (propre au filtre de la liste)
     */
    affectablePartners (): HistoInterfaceSelectOption[] {
      return this.sharedState.partenaires.filter((partner: HistoInterfaceSelectOption) => partner.Id !== 'AUCUN')
    },
    /**
     * Nom du partenaire choisi, pour les messages du récapitulatif
     */
    selectedPartnerName (): string {
      const partner = this.affectablePartners.find((option: HistoInterfaceSelectOption) => option.Id === this.partnerId)
      return partner !== undefined ? partner.Text : ''
    },
    /**
     * Sections du récapitulatif (une par catégorie non vide), affichées en accordéon
     */
    analysisGroups (): AnalysisGroup[] {
      if (this.analysis === null) {
        return []
      }
      const toAffectCount = this.analysis.signalementsToAffect.length
      const alreadyAffectedCount = this.analysis.signalementsAlreadyAffected.length
      const outOfPerimeterCount = this.analysis.signalementsOutOfPerimeter.length

      const groups: AnalysisGroup[] = [
        {
          id: 'mass-affectation-to-affect',
          title: `${this.formatSignalementCount(toAffectCount)} ${toAffectCount > 1 ? 'seront affectés' : 'sera affecté'} à ${this.selectedPartnerName}`,
          signalements: this.analysis.signalementsToAffect
        },
        {
          id: 'mass-affectation-already-affected',
          title: `${this.formatSignalementCount(alreadyAffectedCount)} ${alreadyAffectedCount > 1 ? 'déjà affectés' : 'déjà affecté'} à ce partenaire (ignorés)`,
          signalements: this.analysis.signalementsAlreadyAffected
        },
        {
          id: 'mass-affectation-out-of-perimeter',
          title: `${this.formatSignalementCount(outOfPerimeterCount)} hors du périmètre du partenaire (ignorés)`,
          signalements: this.analysis.signalementsOutOfPerimeter
        }
      ]
      return groups.filter(group => group.signalements.length > 0)
    },
    canValidate (): boolean {
      if (this.isAnalyzing || this.isSubmitting || this.analysis === null) {
        return false
      }
      return this.analysis.signalementsToAffect.length > 0
    }
  },
  mounted () {
    const panel = document.getElementById('panel-mass-affectation')
    if (!panel) {
      return
    }
    this.panelObserver = new MutationObserver(mutations => {
      mutations.forEach(mutation => {
        if (mutation.attributeName === 'open') {
          this.resetPanelState()
        }
      })
    })
    this.panelObserver.observe(panel, { attributes: true })
  },
  beforeUnmount () {
    this.panelObserver?.disconnect()
    this.panelObserver = null
  },
  methods: {
    resetPanelState () {
      this.partnerId = ''
      this.analysis = null
      this.isAnalyzing = false
      this.isSubmitting = false
      this.clearNotice()
    },
    showErrorNotice (message: string, title: string = DEFAULT_ERROR_TITLE) {
      this.titleNotice = title
      this.messageNotice = message
      this.classNameNotice = 'fr-notice--alert'
    },
    clearNotice () {
      this.titleNotice = ''
      this.messageNotice = ''
      this.classNameNotice = ''
    },
    onChoosePartner (value: string) {
      this.partnerId = value
      this.analysis = null
      this.clearNotice()
      if (value === '') {
        return
      }
      this.isAnalyzing = true
      requests.massAffectationAnalyze(value, (responseData: any, errorMessage?: string) => {
        this.handleAnalyzeResponse(responseData, value, errorMessage)
      })
    },
    handleAnalyzeResponse (responseData: any, analyzedPartnerId: string, errorMessage?: string) {
      // Réponse obsolète : le partenaire a changé (ou le panel a été réinitialisé) pendant l'analyse
      if (analyzedPartnerId !== this.partnerId) {
        return
      }
      this.isAnalyzing = false
      if (responseData === 'error') {
        this.showErrorNotice(errorMessage ?? ANALYZE_ERROR_MESSAGE)
        return
      }
      if (!this.isValidAnalysis(responseData)) {
        this.showErrorNotice(ANALYZE_ERROR_MESSAGE)
        return
      }
      this.analysis = responseData
    },
    isValidAnalysis (responseData: any): responseData is MassAffectationAnalysis {
      if (responseData === null || typeof responseData !== 'object') {
        return false
      }
      return Array.isArray(responseData.signalementsToAffect) &&
        Array.isArray(responseData.signalementsAlreadyAffected) &&
        Array.isArray(responseData.signalementsOutOfPerimeter) &&
        typeof responseData.partnerInterconnection === 'boolean'
    },
    formatSignalementCount (count: number): string {
      return count > 1 ? `${count} signalements` : `${count} signalement`
    },
    validate () {
      if (!this.canValidate || !this.analysis) {
        return
      }
      this.isSubmitting = true
      requests.massAffectationValidate(
        { partnerId: Number(this.partnerId), ids: this.analysis.signalementsToAffect.map(signalement => signalement.id) }, 
        this.sharedProps.csrfMassAffectationValidate, 
        this.handleValidateResponse
      )
    },
    handleValidateResponse (requestResponse: any) {
      if (requestResponse.status === 200) {
        // Message affiché en haut de la liste par le parent (showNotice), puis fermeture du panel
        this.$emit(
          'massAffectationSuccess',
          'Affectation en cours',
          'Les affectations sont en cours, vous recevrez un mail récapitulatif à la fin.',
          'fr-notice--success'
        )
        this.closePanel()
      } else {
        this.isSubmitting = false
        this.showErrorNotice(requestResponse.data.message)
      }
    },
    /**
     * Ferme le panel (via son bouton Fermer, pour passer par le mécanisme habituel des side panels)
     */
    closePanel () {
      const closeButton = document.querySelector(
        'button[aria-controls="panel-mass-affectation"].fr-btn--close'
      ) as HTMLButtonElement | null
      closeButton?.click()
    }
  }
})
</script>
