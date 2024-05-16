<template>
  <dialog aria-labelledby="fr-modal-title-modal-already-exists" role="dialog" id="fr-modal-already-exists" class="fr-modal signalement-form-modal-already-exists">
    <div class="fr-container fr-container--fluid fr-container-md">
      <div class="fr-grid-row fr-grid-row--center">
        <div class="fr-col-12 fr-col-md-8 fr-col-lg-6">
          <div class="fr-modal__body">
            <div class="fr-modal__header">
              <button class="fr-btn--close fr-btn" title="Fermer la fenêtre modale" aria-controls="fr-modal-already-exists" id="fr-modal-already-exists-close">Fermer</button>
            </div>
            <div class="fr-modal__content" v-if="formStore.alreadyExists.type==='signalement'">
              <h1 id="fr-modal-title-modal-already-exists" class="fr-modal__title"><span v-if="formStore.alreadyExists.signalements?.length === 1">Ce signalement existe déjà</span><span v-else>Ces signalements existent déjà</span></h1>
              <div v-if="formStore.data.profil === 'bailleur_occupant' || formStore.data.profil === 'locataire' || formStore.alreadyExists.signalements?.length === 1">
                Il semblerait que vous ayez déjà déposé un signalement pour le logement situé <strong>{{ formStore.data.adresse_logement_adresse }}</strong><div class=""></div>
                <span v-if="formStore.data.profil !== 'bailleur_occupant' && formStore.data.profil !== 'locataire' && formStore.alreadyExists.signalements" v-html="signalementLabel(formStore.alreadyExists.signalements[0])"></span>
                Ce signalement est en cours de traitement.<br>
                Vous pouvez le compléter depuis votre page de suivi ou créer un nouveau signalement.
                <br>
                <br>
                Souhaitez-vous compléter le signalement existant ou en créer un nouveau ?
              </div>
              <div v-else>
                Il semblerait que vous ayez déjà déposé plusieurs signalements pour le logement situé <strong>{{ formStore.data.adresse_logement_adresse }}</strong>.<br><br>
                <fieldset class="fr-fieldset" id="radio-hint-element" aria-labelledby="radio-hint-element-legend radio-hint-element-messages">
                  <legend class="fr-fieldset__legend--regular fr-fieldset__legend" id="radio-hint-element-legend">
                    Pour compléter un des signalements ci-dessous, sélectionnez le signalement puis cliquez sur le bouton "Recevoir mon lien de suivi".
                  </legend>
                  <div class="fr-fieldset__element" v-for="signalement in formStore.alreadyExists.signalements" :key="signalement.uuid">
                    <div class="fr-radio-group">
                      <input
                        class="fr-input"
                        type="radio"
                        :id="'selected-signalement-' + signalement.uuid" name="selectedSignalement"
                        :value="signalement.uuid"
                        @change="selectedSignalementUuid = signalement.uuid"
                      >
                      <label
                        class="fr-label"
                        :for="'selected-signalement-' + signalement.uuid"
                      >
                        <span v-html="signalementLabel(signalement)"></span>
                      </label>
                    </div>
                  </div>
                </fieldset>
                <SignalementFormWarning
                  v-if="errorMessage !== ''"
                  id="fr-modal-already-exists-warning"
                  :label="errorMessage"
                >
                </SignalementFormWarning>
                Souhaitez-vous compléter le signalement sélectionné ou en créer un nouveau ?
              </div>
              <SignalementFormWarning
                id="fr-modal-already-exists-info"
                label="Créer un nouveau signalement pour le même logement risque de ralentir la procédure."
              >
              </SignalementFormWarning>
            </div>
            <div class="fr-modal__content" v-else>
              <h1 id="fr-modal-title-modal-already-exists" class="fr-modal__title">Reprendre la saisie</h1>
              <p>
                Il semblerait que vous ayez commencé à remplir un signalement pour le logement situé <strong>{{ formStore.data.adresse_logement_adresse }}</strong>,
                le <strong>{{ formatDate(formStore.alreadyExists.createdAt) }}</strong>.
                <br>
                <br>
                Vous pouvez récupérer les infos de ce signalement et reprendre où vous en étiez.
                <br>
                Souhaitez-vous reprendre le signalement ?
              </p>
              <SignalementFormWarning
                v-if="errorMessage !== ''"
                id="fr-modal-already-exists-warning"
                :label="errorMessage"
              >
              </SignalementFormWarning>
            </div>
            <div class="fr-modal__footer" v-if="formStore.alreadyExists.type==='signalement'">
              <ul class="fr-btns-group fr-btns-group--center fr-btns-group--inline-reverse fr-btns-group--inline-lg fr-btns-group--icon-left">
                <li>
                  <button class="fr-btn" @click="getLienSuivi">
                    Recevoir mon lien de suivi
                  </button>
                </li>
                <li>
                  <button class="fr-btn fr-btn--secondary" aria-controls="fr-modal-already-exists" @click="makeNewSignalement">
                    Créer un nouveau signalement
                  </button>
                </li>
              </ul>
            </div>
            <div class="fr-modal__footer" v-else>
              <ul class="fr-btns-group fr-btns-group--center fr-btns-group--inline-reverse fr-btns-group--inline-lg fr-btns-group--icon-left">
                <li>
                  <button class="fr-btn" aria-controls="fr-modal-already-exists" @click="continueFromDraft">
                    Oui, reprendre le signalement
                  </button>
                </li>
                <li>
                  <button class="fr-btn fr-btn--secondary" aria-controls="fr-modal-already-exists" @click="makeNewSignalement">
                    Non, faire un nouveau signalement
                  </button>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </dialog>
  <button class="fr-btn fr-hidden" id="fr-modal-already-exists-button" data-fr-opened="false" aria-controls="fr-modal-already-exists"></button>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'
import { requests } from './../requests'
import SignalementFormWarning from './../components/SignalementFormWarning.vue'

export default defineComponent({
  name: 'SignalementFormModalAlreadyExists',
  components: {
    SignalementFormWarning
  },
  props: {
    mailSentEvent: Function,
    newClickEvent: Function
  },
  data () {
    return {
      formStore,
      errorMessage: '',
      selectedSignalementUuid: null as string | null
    }
  },
  methods: {
    continueFromDraft () {
      requests.sendMailContinueFromDraft(this.gotoValidationScreen)
    },
    getLienSuivi () {
      this.errorMessage = ''
      if (this.selectedSignalementUuid === null) {
        if (this.formStore.alreadyExists.signalements && formStore.alreadyExists.signalements?.length === 1) {
          this.selectedSignalementUuid = this.formStore.alreadyExists.signalements[0]?.uuid
        } else {
          this.errorMessage = 'Merci de sélectionner un signalement'
        }
      }
      if (this.selectedSignalementUuid !== null) {
        requests.sendMailGetLienSuivi(this.selectedSignalementUuid, this.gotoValidationScreen)
        const link = document.getElementById('fr-modal-already-exists-close')
        if (link) {
          link.click()
        }
      }
    },
    gotoValidationScreen (requestResponse: any) {
      if (requestResponse && requestResponse.success === true) {
        this.errorMessage = ''
        if (this.mailSentEvent !== undefined) {
          if (this.formStore.alreadyExists.type === 'draft') {
            this.mailSentEvent('draft_mail', false)
          } else {
            this.mailSentEvent('lien_suivi_mail', false)
          }
        }
      } else if (requestResponse && requestResponse.success === false) {
        this.errorMessage = requestResponse.message
        const link = document.getElementById('fr-modal-already-exists-button')
        if (link) {
          link.click()
        }
      }
    },
    makeNewSignalement () {
      if (formStore.alreadyExists.uuidDraft !== null && (
        formStore.alreadyExists.type === 'draft' || formStore.alreadyExists.type === 'signalement'
      )) {
        requests.archiveDraft(formStore.alreadyExists.uuidDraft, this.saveAndContinue)
      }
    },
    saveAndContinue () {
      if (this.newClickEvent !== undefined) {
        requests.saveSignalementDraft(this.newClickEvent)
      }
    },
    formatDate (dateTimeString: string | null) {
      if (dateTimeString !== null) {
        const date = new Date(dateTimeString)
        const day = ('0' + date.getDate()).slice(-2)
        const month = ('0' + (date.getMonth() + 1)).slice(-2)
        const year = date.getFullYear()
        const hours = ('0' + date.getHours()).slice(-2)
        const minutes = ('0' + date.getMinutes()).slice(-2)
        return `${day}/${month}/${year} à ${hours}:${minutes}`
      }
      return ''
    },
    signalementLabel (signalement: any) {
      let label = 'Signalement déposé le ' + this.formatDate(signalement.created_at) + ' pour le compte de <strong>'
      if (signalement.prenom_occupant !== null) {
        label += signalement.prenom_occupant + ' '
      }
      if (signalement.nom_occupant !== null) {
        label += signalement.nom_occupant
      }
      label += '</strong>'
      if (signalement.complement_adresse_occupant !== '') {
        label += ' (' + signalement.complement_adresse_occupant + ')'
      }
      label += '.'
      return label
    }
  }
})
</script>

<style>
  .signalement-form-modal-already-exists .fr-radio-group {
    width: 100%;
    max-width: 500px;
    padding: 1rem;
    border: 1px solid var(--border-disabled-grey);
    background-color: var(--grey-1000-50);
  }
  .signalement-form-modal-already-exists .fr-radio-group:hover {
    background-color: var(--grey-1000-50-hover);
  }
  .signalement-form-modal-already-exists .fr-radio-group.is-checked {
    border: 1px solid rgb(0, 0, 145);
  }
  @media (max-width: 48em) {
    .signalement-form-modal-already-exists .fr-fieldset__element.item-divided {
      flex-basis: content;
    }
  }
</style>
