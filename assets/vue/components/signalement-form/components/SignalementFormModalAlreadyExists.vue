<template>
  <dialog aria-labelledby="fr-modal-title-modal-already-exists" role="dialog" id="fr-modal-already-exists" class="fr-modal">
    <div class="fr-container fr-container--fluid fr-container-md">
      <div class="fr-grid-row fr-grid-row--center">
        <div class="fr-col-12 fr-col-md-8 fr-col-lg-6">
          <div class="fr-modal__body">
            <div class="fr-modal__header">
              <button class="fr-btn--close fr-btn" title="Fermer la fenêtre modale" aria-controls="fr-modal-already-exists">Fermer</button>
            </div>
            <div class="fr-modal__content" v-if="formStore.alreadyExists.type==='signalement'">
              <h1 id="fr-modal-title-modal-already-exists" class="fr-modal__title">Ce signalement existe déjà</h1>
              <p>
                Il semblerait que vous ayez déjà déposé un signalement pour le logement situé <strong>{{ formStore.data.adresse_logement_adresse }}</strong>
                <span v-if="formStore.data.coordonnees_occupant_nom || formStore.data.coordonnees_occupant_prenom"> pour le compte de {{ formStore.data.coordonnees_occupant_nom }} {{ formStore.data.coordonnees_occupant_prenom }}</span>.
                Ce signalement est en cours de traitement.<br>
                Vous pouvez le compléter depuis votre page de suivi ou créer un nouveau signalement.
                <br>
                <br>
                Souhaitez-vous compléter le signalement existant ou en créer un nouveau ?
              </p>
              <SignalementFormWarning
                id="fr-modal-already-exists-warning"
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
                  <button class="fr-btn" aria-controls="fr-modal-already-exists" @click="getLienSuivi">
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
      errorMessage: ''
    }
  },
  methods: {
    continueFromDraft () {
      requests.sendMailContinueFromDraft(this.gotoValidationScreen)
    },
    getLienSuivi () {
      requests.sendMailGetLienSuivi(this.gotoValidationScreen)
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
      requests.archiveDraft(this.saveAndContinue)
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
    }
  }
})
</script>

<style>
</style>
