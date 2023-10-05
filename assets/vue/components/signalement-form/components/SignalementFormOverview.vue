<template>
  <div :id="id">
    <div>
      <br>
      <h2 class="fr-h4">Récapitulatif</h2>

      <!-- ADRESSE DU LOGEMENT -->
      <div>
        <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
          <div class="fr-col-8">
            <h3 class="fr-h6">Adresse du logement</h3>
          </div>
          <div class="fr-col-4 fr-text--right">
            <a href="#" class="btn-link fr-btn--icon-left fr-icon-edit-line" @click="handleEdit('adresse_logement')">Editer</a>
          </div>
        </div>
        <p v-html="getFormDataAdresse()"></p>
      </div>

      <!-- VOS COORDONNES SI OCCUPANT -->
      <div v-if="formStore.data.profil === 'bailleur_occupant' || formStore.data.profil === 'locataire'">
        <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
          <div class="fr-col-8">
            <h3 class="fr-h6">Vos coordonnées</h3>
          </div>
          <div class="fr-col-4 fr-text--right">
            <a href="#" @click="handleEdit('vos_coordonnees_occupant')" class="btn-link fr-btn--icon-left fr-icon-edit-line" >Editer</a>
          </div>
        </div>
        <p v-html="getFormDataCoordonneesOccupant()"></p>
      </div>

      <!-- VOS COORDONNEES SI TIERS -->
      <div v-else>
        <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
          <div class="fr-col-8">
            <h3 class="fr-h6">Vos coordonnées</h3>
          </div>
          <div class="fr-col-4 fr-text--right">
            <a href="#" @click="handleEdit('vos_coordonnees_tiers')"  class="btn-link fr-btn--icon-left fr-icon-edit-line" >Editer</a>
          </div>
        </div>
        <p v-html="getFormDataCoordonneesDeclarant()"></p>
      </div>

      <!-- LES COORDONNEES DU BAILLEUR -->
      <div v-if="formStore.data.profil !== 'bailleur_occupant' && formStore.data.profil !== 'bailleur'">
        <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
          <div class="fr-col-8">
            <h3 class="fr-h6">Les coordonnées du bailleur</h3>
          </div>
          <div class="fr-col-4 fr-text--right">
            <a href="#" @click="handleEdit('coordonnees_bailleur')" class="btn-link fr-btn--icon-left fr-icon-edit-line" >Editer</a>
          </div>
        </div>
        <p v-html="getFormDataCoordonneesBailleur()"></p>
      </div>

      <!-- LES COORDONNEES DU FOYER -->
      <div v-if="formStore.data.profil !== 'bailleur_occupant' && formStore.data.profil !== 'locataire' && formStore.data.profil !== 'service_secours'">
        <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
          <div class="fr-col-8">
            <h3 class="fr-h6">Les coordonnées du foyer</h3>
          </div>
          <div class="fr-col-4 fr-text--right">
            <a href="#" @click="handleEdit('coordonnees_occupant')" class="btn-link fr-btn--icon-left fr-icon-edit-line" >Editer</a>
          </div>
        </div>
        <p v-html="getFormDataCoordonneesOccupantSiTiers()"></p>
      </div>

      <!-- TYPE ET COMPOSITION DU LOGEMENT -->
      <div>
        <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
          <div class="fr-col-8">
            <h3 class="fr-h6">Type et composition du logement</h3>
          </div>
          <div class="fr-col-4 fr-text--right">
            <a href="#" class="btn-link fr-btn--icon-left fr-icon-edit-line" @click="handleEdit('ecran_intermediaire_type_composition')">Editer</a>
          </div>
        </div>
        <section class="fr-accordion fr-mb-3w">
          <h3 class="fr-accordion__title">
            <button class="fr-accordion__btn" aria-expanded="false" aria-controls="accordion-type-composition">Afficher les informations</button>
          </h3>
          <div class="fr-collapse" id="accordion-type-composition">
            <p v-html="getFormDataTypeComposition()"></p>
          </div>
        </section>
      </div>

      <!-- SITUATION OCCUPANT -->
      <div>
        <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
          <div class="fr-col-8">
            <h3 class="fr-h6" v-if="formStore.data.profil === 'bailleur_occupant' || formStore.data.profil === 'locataire'">Votre situation</h3>
            <h3 class="fr-h6" v-else>La situation du foyer</h3>
          </div>
          <div class="fr-col-4 fr-text--right">
            <a href="#" class="btn-link fr-btn--icon-left fr-icon-edit-line" @click="handleEdit('ecran_intermediaire_situation_occupant')">Editer</a>
          </div>
        </div>
        <section class="fr-accordion fr-mb-3w">
          <h3 class="fr-accordion__title">
            <button class="fr-accordion__btn" aria-expanded="false" aria-controls="accordion-situation-occupant">Afficher les informations</button>
          </h3>
          <div class="fr-collapse" id="accordion-situation-occupant">
            <p v-html="getFormDataSituationOccupant()"></p>
          </div>
        </section>
      </div>

      <!-- LES DESORDRES -->
      <div>
        <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
          <div class="fr-col-8">
            <h3 class="fr-h6">Les désordres</h3>
          </div>
          <div class="fr-col-4 fr-text--right">
            <a href="#" class="btn-link fr-btn--icon-left fr-icon-edit-line" @click="handleEdit('ecran_intermediaire_les_desordres')">Editer</a>
          </div>
        </div>
        <SignalementFormDisorderOverview
          :id="idDisorderOverview"
          :icons="disorderIcons"
          />
      </div>

      <!-- LA PROCEDURE  -->
      <div v-if="formStore.data.profil === 'bailleur_occupant' || formStore.data.profil === 'locataire' || formStore.data.profil === 'bailleur'">
        <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
          <div class="fr-col-8">
            <h3 class="fr-h6">La procédure</h3>
          </div>
          <div class="fr-col-4 fr-text--right">
            <a href="#" class="btn-link fr-btn--icon-left fr-icon-edit-line" @click="handleEdit('info_procedure')">Editer</a>
          </div>
        </div>
        <section class="fr-accordion fr-mb-3w">
          <h3 class="fr-accordion__title">
            <button class="fr-accordion__btn" aria-expanded="false" aria-controls="accordion-procedure">Afficher les informations</button>
          </h3>
          <div class="fr-collapse" id="accordion-procedure">
            <p v-html="getFormDataProcedure()"></p>
          </div>
        </section>
      </div>

      <!-- INFORMATIONS COMPLEMENTAIRES  -->
      <div v-if="formStore.data.profil !== 'service_secours'">
        <div v-if="hasInformationsComplementaires()">
          <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
            <div class="fr-col-8">
              <h2 class="fr-h4">Informations complémentaires</h2>
            </div>
            <div class="fr-col-4 fr-text--right">
              <a href="#" class="btn-link fr-btn--icon-left fr-icon-edit-line" @click="handleEdit('informations_complementaires')">Editer</a>
            </div>
          </div>
          <p v-html="getFormDataInformationsComplementaires()"></p>
        </div>
        <div v-else>
          <h2 class="fr-h4">Informations complémentaires</h2>
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
      </div>

      <!-- MESSAGE A L'ADMINISTRATION -->
      <br>
      <h2 class="fr-h4">Message à l'administration (facultatif)</h2>
      <p>Ce message sera joint à votre signalement.</p>
      <SignalementFormTextarea
        :id="idMessageAdministration"
        description="Votre message ici"
        @input="updateValue($event)"
        :modelValue="formStore.data[idMessageAdministration]"
        />
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'
import dictionaryStore from './../dictionary-store'
import SignalementFormDisorderOverview from './SignalementFormDisorderOverview.vue'
import SignalementFormTextarea from './SignalementFormTextarea.vue'
import { dictionaryManager } from './../services/dictionaryManager'

export default defineComponent({
  name: 'SignalementFormOverview',
  components: {
    SignalementFormDisorderOverview,
    SignalementFormTextarea
  },
  props: {
    id: { type: String, default: null },
    clickEvent: Function
  },
  data () {
    return {
      formStore,
      dictionaryStore,
      idDisorderOverview: this.id + '_disorder_overview',
      idMessageAdministration: this.id + '_message_administration',
      disorderIcons: [{ src: '/img/form/BATIMENT/Picto-batiment.svg', alt: '' }, { src: '/img/form/LOGEMENT/Picto-logement.svg', alt: '' }]
    }
  },
  methods: {
    getFormDataAdresse (): string {
      let result = ''
      result += this.formStore.data.adresse_logement_adresse + '<br>'
      result += this.addLineIfNeeded('adresse_logement_complement_adresse_etage', 'Etage : ')
      result += this.addLineIfNeeded('adresse_logement_complement_adresse_escalier', 'Escalier : ')
      result += this.addLineIfNeeded('adresse_logement_complement_adresse_numero_appartement', 'Numéro d\'appartement : ')
      result += this.addLineIfNeeded('adresse_logement_complement_adresse_autre', 'Autre : ')
      return result
    },
    getFormDataCoordonneesOccupant (): string {
      let result = ''
      result += this.formStore.data.vos_coordonnees_occupant_civilite + ' '
      result += this.formStore.data.vos_coordonnees_occupant_prenom + ' '
      result += this.formStore.data.vos_coordonnees_occupant_nom + '<br>'
      result += this.addLineIfNeeded('vos_coordonnees_occupant_email', 'Adresse email : ')
      result += this.addLineIfNeeded('vos_coordonnees_occupant_tel', 'Numéro de téléphone : ')
      return result
    },
    getFormDataCoordonneesOccupantSiTiers (): string {
      let result = ''
      if (this.isFormDataSet('coordonnees_occupant_prenom')) {
        result += this.formStore.data.coordonnees_occupant_prenom + ' '
      }
      if (this.isFormDataSet('coordonnees_occupant_nom')) {
        result += this.formStore.data.coordonnees_occupant_nom + '<br>'
      }
      result += this.addLineIfNeeded('coordonnees_occupant_email', 'Adresse email : ')
      result += this.addLineIfNeeded('coordonnees_occupant_tel', 'Numéro de téléphone : ')
      return result
    },
    getFormDataCoordonneesDeclarant (): string {
      let result = ''
      result += this.addLineIfNeeded('vos_coordonnees_tiers_nom_organisme')
      result += this.formStore.data.vos_coordonnees_tiers_prenom + ' '
      result += this.formStore.data.vos_coordonnees_tiers_nom + '<br>'
      result += this.addLineIfNeeded('vos_coordonnees_tiers_lien', 'Lien avec l\'occupant : ')
      result += this.addLineIfNeeded('vos_coordonnees_tiers_email', 'Adresse email : ')
      result += this.addLineIfNeeded('vos_coordonnees_tiers_tel', 'Numéro de téléphone : ')
      result += this.addLineIfNeeded('vos_coordonnees_tiers_tel_secondaire', 'Numéro de téléphone secondaire : ')
      return result
    },
    getFormDataCoordonneesBailleur (): string {
      let result = ''
      if (this.isFormDataSet('coordonnees_bailleur_prenom')) {
        result += this.formStore.data.coordonnees_bailleur_prenom + ' '
      }
      result += this.formStore.data.coordonnees_bailleur_nom
      result += this.addLineIfNeeded('coordonnees_bailleur_email', 'Adresse email : ')
      result += this.addLineIfNeeded('coordonnees_bailleur_tel', 'Numéro de téléphone : ')
      result += this.addLineIfNeeded('coordonnees_bailleur_tel_secondaire', 'Numéro de téléphone secondaire : ')
      result += this.addLineIfNeeded('coordonnees_bailleur_adresse', 'Adresse: ')
      return result
    },
    getFormDataTypeComposition (): string {
      let result = ''
      result += this.addLineIfNeeded('type_logement_nature', 'Nature du logement  : ')
      if (this.formStore.data.type_logement_nature === 'appartement') {
        result += this.addLineIfNeeded('type_logement_rdc', 'Au RDC ? ')
        if (this.formStore.data.type_logement_rdc === 'non') {
          result += this.addLineIfNeeded('type_logement_dernier_etage', 'Au dernier étage ? ')
          if (this.formStore.data.type_logement_dernier_etage === 'oui') {
            result += this.addLineIfNeeded('type_logement_sous_comble_sans_fenetre', 'Sous les combles et sans fenêtre ? ')
          }
          if (this.formStore.data.type_logement_dernier_etage === 'non') {
            result += this.addLineIfNeeded('type_logement_sous_sol_sans_fenetre', 'En sous-sol et sans fenêtre ? ')
          }
        }
      }
      result += this.addLineIfNeeded('composition_logement_superficie', 'Superficie en m² : ')
      result += this.addLineIfNeeded('composition_logement_piece_unique', 'Une seule ou plusieurs pièces ? ')
      if (this.formStore.data.composition_logement_piece_unique === 'plusieurs_pieces') {
        result += this.addLineIfNeeded('composition_logement_nb_pieces', 'Nombre de pièces à vivre : ')
      }
      for (let i = 1; i <= this.formStore.data.composition_logement_nb_pieces; i++) {
        result += this.addLineIfNeeded('type_logement_pieces_a_vivre_superficie_piece_' + i, 'Superficie de la pièce ' + i + ' : ')
        result += this.addLineIfNeeded('type_logement_pieces_a_vivre_hauteur_piece_' + i, 'La hauteur jusqu\'au plafond de la pièce ' + i + ' est de 2,20m (220cm) ou plus ? ')
      }
      result += this.addLineIfNeeded('type_logement_commodites_cuisine', 'Cuisine ou coin cuisine ? ')
      if (this.formStore.data.type_logement_commodites_cuisine === 'non') {
        result += this.addLineIfNeeded('type_logement_commodites_cuisine_collective', 'Accès à une cuisine collective ? ')
      } else {
        result += this.addLineIfNeeded('type_logement_commodites_cuisine_hauteur_plafond', 'La hauteur jusqu\'au plafond est de 2m (200cm) ou plus ? ')
      }
      result += this.addLineIfNeeded('type_logement_commodites_salle_de_bain', 'Salle de bain, salle d\'eau avec douche ou baignoire ? ')
      if (this.formStore.data.type_logement_commodites_salle_de_bain === 'non') {
        result += this.addLineIfNeeded('type_logement_commodites_salle_de_bain_collective', 'Accès à une salle de bain ou des douches collectives ? ')
      } else {
        result += this.addLineIfNeeded('type_logement_commodites_salle_de_bain_hauteur_plafond', 'La hauteur jusqu\'au plafond est de 2m (200cm) ou plus ? ')
      }
      result += this.addLineIfNeeded('type_logement_commodites_wc', 'Toilettes (WC) ? ')
      if (this.formStore.data.type_logement_commodites_wc === 'non') {
        result += this.addLineIfNeeded('type_logement_commodites_wc_collective', 'Accès à des toilettes (WC) collectives ? ')
      } else {
        result += this.addLineIfNeeded('type_logement_commodites_wc_hauteur_plafond', 'La hauteur jusqu\'au plafond est de 2m (200cm) ou plus ? ')
      }
      if (this.formStore.data.type_logement_commodites_cuisine === 'oui' && this.formStore.data.type_logement_commodites_wc === 'oui') {
        result += this.addLineIfNeeded('type_logement_commodites_wc_cuisine', 'Toilettes (WC) et cuisine dans la même pièce ? ')
      }
      result += this.addLineIfNeeded('composition_logement_nombre_personnes', 'Nombre de personnes : ')
      result += this.addLineIfNeeded('composition_logement_enfants', 'Enfants de moins de 6 ans ? ')
      return result
    },
    getFormDataSituationOccupant (): string {
      let result = ''
      result += this.addLineIfNeeded('logement_social_demande_relogement', 'Demande de relogement ? ')
      result += this.addLineIfNeeded('logement_social_allocation', 'Aide ou allocation logement ? ')
      if (this.formStore.data.logement_social_allocation === 'oui') {
        result += this.addLineIfNeeded('logement_social_allocation_caisse', 'Caisse : ')
        result += this.addLineIfNeeded('logement_social_date_naissance', 'Date de naissance : ')
        result += this.addLineIfNeeded('logement_social_numero_allocataire', 'Numéro allocataire : ')
        result += this.addLineIfNeeded('logement_social_montant_allocation', 'Montant allocation : ', ' €')
      }
      return result
    },
    getFormDataProcedure (): string {
      let result = ''
      result += this.addLineIfNeeded('info_procedure_bailleur_prevenu', 'Bailleur (propriétaire) prévenu ? ')
      result += this.addLineIfNeeded('info_procedure_assurance_contactee', 'Assurance contactée ? ')
      if (this.formStore.data.info_procedure_assurance_contactee === 'oui') {
        result += this.addLineIfNeeded('info_procedure_reponse_assurance', 'Réponse de l\'assurance : ')
      }
      result += this.addLineIfNeeded('info_procedure_depart_apres_travaux', 'Si des travaux sont faits, voulez-vous rester dans le logement ? ')
      return result
    },
    getFormDataInformationsComplementaires (): string {
      let result = ''
      result += this.addLineIfNeeded('informations_complementaires_situation_occupants_beneficiaire_rsa', 'Bénéficiaire RSA : ')
      result += this.addLineIfNeeded('informations_complementaires_situation_occupants_beneficiaire_fsl', 'Bénéficiaire FSL : ')
      result += this.addLineIfNeeded('informations_complementaires_situation_occupants_revenu_fiscal', 'Revenu fiscal de référence : ', ' €')
      result += this.addLineIfNeeded('informations_complementaires_situation_occupants_date_naissance', 'Date de naissance : ')
      result += this.addLineIfNeeded('informations_complementaires_situation_occupants_loyers_payes', 'Paiement des loyers à jour : ')
      result += this.addLineIfNeeded('informations_complementaires_situation_occupants_preavis_depart', 'Préavis de départ : ')
      result += this.addLineIfNeeded('informations_complementaires_logement_montant_loyer', 'Montant du loyer sans les charges : ', ' €')
      result += this.addLineIfNeeded('informations_complementaires_logement_nombre_etages', 'Nombre d\'étages du logement : ')
      result += this.addLineIfNeeded('informations_complementaires_logement_annee_construction', 'Année de construction du logement : ')
      result += this.addLineIfNeeded('informations_complementaires_situation_occupants_demande_relogement', 'Demande de relogement ou de logement social : ')
      result += this.addLineIfNeeded('informations_complementaires_situation_occupants_date_emmenagement', 'Date d\'emménagement : ')
      result += this.addLineIfNeeded('informations_complementaires_situation_bailleur_beneficiaire_rsa', 'Bénéficiaire RSA : ')
      result += this.addLineIfNeeded('informations_complementaires_situation_bailleur_beneficiaire_fsl', 'Bénéficiaire FSL : ')
      result += this.addLineIfNeeded('informations_complementaires_situation_bailleur_revenu_fiscal', 'Revenu fiscal de référence : ', ' €')
      result += this.addLineIfNeeded('informations_complementaires_situation_bailleur_date_naissance', 'Date de naissance : ')
      return result
    },
    hasInformationsComplementaires (): boolean {
      let result = false
      if (this.isFormDataSet('informations_complementaires_situation_occupants_beneficiaire_rsa') ||
          this.isFormDataSet('informations_complementaires_situation_occupants_beneficiaire_fsl') ||
          this.isFormDataSet('informations_complementaires_situation_occupants_revenu_fiscal') ||
          this.isFormDataSet('informations_complementaires_situation_occupants_date_naissance') ||
          this.isFormDataSet('informations_complementaires_situation_occupants_loyers_payes') ||
          this.isFormDataSet('informations_complementaires_situation_occupants_preavis_depart') ||
          this.isFormDataSet('informations_complementaires_logement_montant_loyer') ||
          this.isFormDataSet('informations_complementaires_logement_nombre_etages') ||
          this.isFormDataSet('informations_complementaires_logement_annee_construction') ||
          this.isFormDataSet('informations_complementaires_situation_occupants_demande_relogement') ||
          this.isFormDataSet('informations_complementaires_situation_occupants_date_emmenagement') ||
          this.isFormDataSet('informations_complementaires_situation_bailleur_beneficiaire_rsa') ||
          this.isFormDataSet('informations_complementaires_situation_bailleur_beneficiaire_fsl') ||
          this.isFormDataSet('informations_complementaires_situation_bailleur_revenu_fiscal') ||
          this.isFormDataSet('informations_complementaires_situation_bailleur_date_naissance')) {
        result = true
      }
      return result
    },
    isFormDataSet (formSlug: string) {
      return (this.formStore.data[formSlug] !== '' && this.formStore.data[formSlug] !== undefined && this.formStore.data[formSlug] !== null)
    },
    addLineIfNeeded (formSlug: string, questionTitle?: string, suffixe?: string): string {
      let result = ''
      if (this.isFormDataSet(formSlug)) {
        if (questionTitle !== null && questionTitle !== undefined) {
          result += questionTitle
        }
        result += dictionaryManager.translate(this.formStore.data[formSlug], 'default')
        if (suffixe !== null && suffixe !== undefined) {
          result += suffixe
        }
        result += '<br>'
      }
      return result
    },
    handleEdit (screenSlug: string) {
      if (this.clickEvent !== undefined) {
        this.clickEvent('goto', screenSlug, '')
      }
    },
    updateValue (event: Event) {
      const value = (event.target as HTMLInputElement).value
      this.formStore.data[this.idMessageAdministration] = value
    }
  }
})
</script>

<style>
#validation_signalement_overview_disorder_overview .fr-disorder-overview-title {
  font-size: 20px !important;
  line-height: 28px !important;
  margin: 0;
}
#validation_signalement_overview_disorder_overview .fr-disorder-overview-image {
  width: 40px;
}
</style>
