<template>
  <div :id="id">
    <div>
      <h2>Récapitulatif</h2>

      <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--middle">
        <div class="fr-col-12 fr-col-md-8">
          <h3>Adresse du logement</h3>
        </div>
        <div class="fr-col-12 fr-col-md-4">
          <a href="#" class="btn-link fr-btn--icon-left fr-icon-edit-line" @click="handleEdit('adresse_logement')">Editer</a>
        </div>
      </div>
      <p v-html="getFormDataAdresse()"></p>

      <!-- TODO : changer intitulés ? -->
      <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--middle">
        <div class="fr-col-12 fr-col-md-8">
          <h3>Vos coordonnées</h3>
        </div>
        <div class="fr-col-12 fr-col-md-4">
          <a v-if="formStore.data.profil === 'bailleur_occupant' || formStore.data.profil === 'locataire'" href="#" @click="handleEdit('vos_coordonnees_occupant')" class="btn-link fr-btn--icon-left fr-icon-edit-line" >Editer</a>
          <a v-else href="#" @click="handleEdit('vos_coordonnees_tiers')"  class="btn-link fr-btn--icon-left fr-icon-edit-line" >Editer</a>
        </div>
      </div>
      <p v-html="getFormDataCoordonneesOccupant()"></p>

      <!-- TODO : si profil est bailleur ou bailleur_occupant que met-on ? -->
      <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--middle">
        <div class="fr-col-12 fr-col-md-8">
          <h3>Les coordonnées du bailleur</h3>
        </div>
        <div class="fr-col-12 fr-col-md-4">
          <a v-if="formStore.data.profil !== 'bailleur_occupant'" href="#" @click="handleEdit('coordonnees_bailleur')" class="btn-link fr-btn--icon-left fr-icon-edit-line" >Editer</a>
          <a v-else href="#" @click="handleEdit('vos_coordonnees_tiers')"  class="btn-link fr-btn--icon-left fr-icon-edit-line" >Editer</a>
        </div>
      </div>
      <p v-html="getFormDataCoordonneesBailleur()"></p>

      <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--middle">
        <div class="fr-col-12 fr-col-md-8">
          <h3>Type et composition du logement</h3>
        </div>
        <div class="fr-col-12 fr-col-md-4">
          <a href="#" class="btn-link fr-btn--icon-left fr-icon-edit-line" @click="handleEdit('ecran_intermediaire_type_composition')">Editer</a>
        </div>
      </div>
      <section class="fr-accordion">
        <h3 class="fr-accordion__title">
          <button class="fr-accordion__btn" aria-expanded="false" aria-controls="accordion-type-composition">Afficher les informations</button>
        </h3>
        <div class="fr-collapse" id="accordion-type-composition">
          <p v-html="getFormDataTypeComposition()"></p>
        </div>
      </section>

      <!-- TODO quels profils, quel intitulé -->
      <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--middle">
        <div class="fr-col-12 fr-col-md-8">
          <h3>Votre situation</h3>
        </div>
        <div class="fr-col-12 fr-col-md-4">
          <a href="#" class="btn-link fr-btn--icon-left fr-icon-edit-line" @click="handleEdit('ecran_intermediaire_situation_occupant')">Editer</a>
        </div>
      </div>
      <section class="fr-accordion">
        <h3 class="fr-accordion__title">
          <button class="fr-accordion__btn" aria-expanded="false" aria-controls="accordion-situation-occupant">Afficher les informations</button>
        </h3>
        <div class="fr-collapse" id="accordion-situation-occupant">
          <p v-html="getFormDataSituationOccupant()"></p>
        </div>
      </section>

      <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--middle">
        <div class="fr-col-12 fr-col-md-8">
          <h3>Les désordres</h3>
        </div>
        <div class="fr-col-12 fr-col-md-4">
          <a href="#" class="btn-link fr-btn--icon-left fr-icon-edit-line" @click="handleEdit('ecran_intermediaire_les_desordres')">Editer</a>
        </div>
      </div>
      <SignalementFormDisorderOverview
        :id="idDisorderOverview"
        :icons="disorderIcons"
        />

      <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--middle">
        <div class="fr-col-12 fr-col-md-8">
          <h3>TODO : La procédure</h3>
        </div>
        <div class="fr-col-12 fr-col-md-4">
          <a href="#" class="btn-link fr-btn--icon-left fr-icon-edit-line" @click="handleEdit('info_procedure')">Editer</a>
        </div>
      </div>
        <div class="fr-collapse" id="accordion-procedure">
          <p v-html="getFormDataProcedure()"></p>
        </div>

      <div v-if="formStore.data.profil === 'bailleur_occupant' || formStore.data.profil === 'locataire'">
        <h2>Informations complémentaires</h2>

        <p>
          Plus nous avons d'informations sur la situation,
          mieux nous pouvons vous accompagner.
          Cliquez sur le bouton pour ajouter des informations.
        </p>
        <button
          type="button"
          class="fr-btn fr-btn--secondary fr-btn--icon-left fr-icon-edit-line"
          @click="handleEdit('informations_complementaires')"
          >
          Ajouter des informations
        </button>
      </div>

      <h2>Message à l'administration (facultatif)</h2>
      <p>Ce message sera joint à votre signalement.</p>
      <textarea placeholder="Votre message ici"></textarea>
      <!-- <SignalementFormTextarea></SignalementFormTextarea> -->
      <!-- TODO utiliser composant SignalementFormTextarea -->

    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'
import SignalementFormDisorderOverview from './SignalementFormDisorderOverview.vue'

export default defineComponent({
  name: 'SignalementFormOverview',
  components: {
    SignalementFormDisorderOverview
  },
  props: {
    id: { type: String, default: null },
    clickEvent: Function
  },
  data () {
    return {
      formStore,
      idDisorderOverview: this.id + '_disorder_overview',
      disorderIcons: [{ src: '/img/form/BATIMENT/Picto-batiment.svg', alt: '' }, { src: '/img/form/LOGEMENT/Picto-logement.svg', alt: '' }]
    }
  },
  methods: {
    getFormDataAdresse (): string {
      let result = ''
      result += this.formStore.data.adresse_logement_adresse + '<br>'
      if (this.isFormDataSet('adresse_logement_complement_adresse_etage')) {
        result += 'Etage : ' + this.formStore.data.adresse_logement_complement_adresse_etage + '<br>'
      }
      if (this.isFormDataSet('adresse_logement_complement_adresse_escalier')) {
        result += 'Escalier : ' + this.formStore.data.adresse_logement_complement_adresse_escalier + '<br>'
      }
      if (this.isFormDataSet('adresse_logement_complement_adresse_numero_appartement')) {
        result += 'Numéro d\'appartement : ' + this.formStore.data.adresse_logement_complement_adresse_numero_appartement + '<br>'
      }
      if (this.isFormDataSet('adresse_logement_complement_adresse_autre')) {
        result += 'Autre : ' + this.formStore.data.adresse_logement_complement_adresse_autre
      }
      return result
    },
    getFormDataCoordonneesOccupant (): string {
      let result = ''
      result += this.formStore.data.vos_coordonnees_occupant_civilite + ' '
      result += this.formStore.data.vos_coordonnees_occupant_prenom + ' '
      result += this.formStore.data.vos_coordonnees_occupant_nom + '<br>'
      result += this.formStore.data.vos_coordonnees_occupant_email + '<br>'
      result += this.formStore.data.vos_coordonnees_occupant_tel
      return result
    },
    getFormDataCoordonneesBailleur (): string {
      let result = ''
      if (this.isFormDataSet('coordonnees_bailleur_prenom')) {
        result += this.formStore.data.coordonnees_bailleur_prenom + ' '
      }
      result += this.formStore.data.coordonnees_bailleur_nom
      return result
    },
    getFormDataTypeComposition (): string {
      let result = ''
      result += 'Nature du logement : ' + this.formStore.data.type_logement_nature + '<br>'
      if (this.formStore.data.type_logement_nature === 'appartement') {
        result += 'Au RDC ? ' + this.formStore.data.type_logement_rdc + '<br>'
        if (this.formStore.data.type_logement_rdc === 'non') {
          result += 'Au dernier étage ? ' + this.formStore.data.type_logement_dernier_etage + '<br>'
          if (this.formStore.data.type_logement_dernier_etage === 'oui') {
            result += 'Sous les combles et sans fenêtre ? ' + this.formStore.data.type_logement_sous_comble_sans_fenetre + '<br>'
          }
          if (this.formStore.data.type_logement_dernier_etage === 'non') {
            result += 'En sous-sol et sans fenêtre ? ' + this.formStore.data.type_logement_sous_sol_sans_fenetre + '<br>'
          }
        }
      }
      result += 'Une seule ou plusieurs pièces ? ' + this.formStore.data.composition_logement_piece_unique + '<br>'
      if (this.formStore.data.composition_logement_piece_unique === 'plusieurs_pieces') {
        result += 'Nombre de pièces à vivre : ' + this.formStore.data.composition_logement_nb_pieces + '<br>'
      }
      result += 'Superficie en m² : ' + this.formStore.data.composition_logement_superficie + '<br>'
      result += 'Nombre de personnes : ' + this.formStore.data.composition_logement_nombre_personnes + '<br>'
      result += 'Enfants de moins de 6 ans ? ' + this.formStore.data.composition_logement_enfants + '<br>'
      return result
    },
    getFormDataSituationOccupant (): string {
      let result = ''
      result += 'Demande de relogement ? ' + this.formStore.data.logement_social_demande_relogement + '<br>'
      result += 'Aide ou allocation ? ' + this.formStore.data.logement_social_allocation + '<br>'
      if (this.formStore.data.logement_social_allocation === 'oui') {
        result += 'Caisse : ' + this.formStore.data.logement_social_allocation_caisse + '<br>'
        result += 'Date de naissance : ' + this.formStore.data.logement_social_date_naissance + '<br>'
        result += 'Numéro allocataire : ' + this.formStore.data.logement_social_numero_allocataire + '<br>'
        result += 'Montant allocation : ' + this.formStore.data.logement_social_allocation + '€<br>'
      }
      return result
    },
    getFormDataProcedure (): string {
      let result = ''
      // TODO : conditionner en fonction des réponses et profils
      result += 'Bailleur (propriétaire) prévenu ? ' + this.formStore.data.info_procedure_bailleur_prevenu + '<br>'
      result += 'Assurance contactée ? ' + this.formStore.data.info_procedure_assurance_contactee + '<br>'
      result += 'Réponse de l\'assurance ' + this.formStore.data.info_procedure_reponse_assurance + '<br>'
      result += 'Si des travaux sont faits, voulez-vous rester dans le logement  ' + this.formStore.data.info_procedure_depart_apres_travaux + '<br>'

      return result
    },
    isFormDataSet (formSlug: string) {
      return (this.formStore.data[formSlug] !== '' && this.formStore.data[formSlug] !== undefined)
    },
    handleEdit (screenSlug: string) {
      if (this.clickEvent !== undefined) {
        this.clickEvent('goto', screenSlug, '')
      }
    }
  }
})
</script>

<style>
</style>
