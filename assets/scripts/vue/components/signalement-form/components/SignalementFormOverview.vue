<template>
  <div :id="id" class="text-word-break-all">
    <div>
      <br>
      <h3 class="fr-h4">Récapitulatif</h3>

      <!-- ADRESSE DU LOGEMENT -->
      <div>
        <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
          <div class="fr-col-12 fr-col-md-8">
            <h4 class="fr-h6 fr-mb-0 fr-mb-md-8v">Adresse du logement</h4>
          </div>
          <div class="fr-col-12 fr-col-md-4 fr-text--right">
            <button
              @click="handleEdit('adresse_logement')"
              class="fr-btn fr-btn--tertiary fr-btn--icon-left fr-icon-edit-line"
              ref="firstbutton"
              aria-label="Editer l'adresse du logement"
              >Editer</button>
          </div>
        </div>
        <p v-html="getFormDataAdresse()"></p>
      </div>

      <!-- VOS COORDONNES SI OCCUPANT -->
      <div v-if="formStore.data.profil === 'bailleur_occupant' || formStore.data.profil === 'locataire'">
        <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
          <div class="fr-col-12 fr-col-md-8">
            <h4 class="fr-h6 fr-mb-0 fr-mb-md-8v">Vos coordonnées</h4>
          </div>
          <div class="fr-col-12 fr-col-md-4 fr-text--right">
            <button
              @click="handleEdit('vos_coordonnees_occupant')"
              class="fr-btn fr-btn--tertiary fr-btn--icon-left fr-icon-edit-line"
              aria-label="Editer vos coordonnées"
              >Editer</button>
          </div>
        </div>
        <p v-html="getFormDataCoordonneesOccupant()"></p>
      </div>

      <!-- VOS COORDONNEES SI TIERS -->
      <div v-else>
        <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
          <div class="fr-col-12 fr-col-md-8">
            <h4 class="fr-h6 fr-mb-0 fr-mb-md-8v">Vos coordonnées</h4>
          </div>
          <div class="fr-col-12 fr-col-md-4 fr-text--right">
            <button
              @click="handleEdit('vos_coordonnees_tiers')"
              class="fr-btn fr-btn--tertiary fr-btn--icon-left fr-icon-edit-line"
              aria-label="Editer vos coordonnées"
              >Editer</button>
          </div>
        </div>
        <p v-html="getFormDataCoordonneesDeclarant()"></p>
      </div>

      <!-- LES COORDONNEES DU BAILLEUR -->
      <div v-if="formStore.data.profil !== 'bailleur_occupant' && formStore.data.profil !== 'bailleur'">
        <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
          <div class="fr-col-12 fr-col-md-8">
            <h4 class="fr-h6 fr-mb-0 fr-mb-md-8v">Les coordonnées du bailleur</h4>
          </div>
          <div class="fr-col-12 fr-col-md-4 fr-text--right">
            <button
              @click="handleEdit('coordonnees_bailleur')"
              class="fr-btn fr-btn--tertiary fr-btn--icon-left fr-icon-edit-line"
              aria-label="Editer les coordonnées du bailleur"
              >Editer</button>
          </div>
        </div>
        <p v-html="getFormDataCoordonneesBailleur()"></p>
      </div>

      <!-- LES COORDONNEES DU FOYER -->
      <div v-if="formStore.data.profil !== 'bailleur_occupant' && formStore.data.profil !== 'locataire' && formStore.data.profil !== 'service_secours'">
        <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
          <div class="fr-col-12 fr-col-md-8">
            <h4 class="fr-h6 fr-mb-0 fr-mb-md-8v">Les coordonnées du foyer</h4>
          </div>
          <div class="fr-col-12 fr-col-md-4 fr-text--right">
            <button
              @click="handleEdit('coordonnees_occupant')"
              class="fr-btn fr-btn--tertiary fr-btn--icon-left fr-icon-edit-line"
              aria-label="Editer les coordonnées du foyer">Editer</button>
          </div>
        </div>
        <p v-html="getFormDataCoordonneesOccupantSiTiers()"></p>
      </div>

      <!-- TYPE ET COMPOSITION DU LOGEMENT -->
      <div>
        <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
          <div class="fr-col-12 fr-col-md-8">
            <h4 class="fr-h6 fr-mb-0 fr-mb-md-8v">Type et composition du logement</h4>
          </div>
          <div class="fr-col-12 fr-col-md-4 fr-text--right">
            <button
              @click="handleEdit('ecran_intermediaire_type_composition')"
              class="fr-btn fr-btn--tertiary fr-btn--icon-left fr-icon-edit-line"
              aria-label="Editer le type et la composition du logement"
              >Editer</button>
          </div>
        </div>
        <section class="fr-accordion fr-mb-3w">
          <h5 class="fr-accordion__title">
            <button
              class="fr-accordion__btn"
              aria-expanded="false"
              aria-controls="accordion-type-composition"
              >Afficher les informations</button>
          </h5>
          <div class="fr-collapse" id="accordion-type-composition">
            <p v-html="getFormDataTypeComposition()"></p>
          </div>
        </section>
      </div>

      <!-- SITUATION OCCUPANT -->
      <div v-if="formStore.data.profil !== 'service_secours'">
        <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
          <div class="fr-col-12 fr-col-md-8">
            <h4 class="fr-h6 fr-mb-0 fr-mb-md-8v" v-if="formStore.data.profil === 'bailleur_occupant' || formStore.data.profil === 'locataire'">Votre situation</h4>
            <h4 class="fr-h6 fr-mb-0 fr-mb-md-8v" v-else>La situation du foyer</h4>
          </div>
          <div class="fr-col-12 fr-col-md-4 fr-text--right">
            <button
              @click="handleEdit('ecran_intermediaire_situation_occupant')"
              class="fr-btn fr-btn--tertiary fr-btn--icon-left fr-icon-edit-line"
              :aria-label="formStore.data.profil === 'bailleur_occupant' || formStore.data.profil === 'locataire' ? 'Editer votre situation' : 'Editer la situation du foyer'"
              >Editer</button>
          </div>
        </div>
        <section class="fr-accordion fr-mb-3w">
          <h5 class="fr-accordion__title">
            <button
              class="fr-accordion__btn"
              aria-expanded="false"
              aria-controls="accordion-situation-occupant"
              >Afficher les informations</button>
          </h5>
          <div class="fr-collapse" id="accordion-situation-occupant">
            <p v-html="getFormDataSituationOccupant()"></p>
          </div>
        </section>
      </div>

      <!-- LES DESORDRES -->
      <div>
        <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
          <div class="fr-col-12 fr-col-md-8">
            <h4 class="fr-h6 fr-mb-0 fr-mb-md-8v">Les désordres</h4>
          </div>
          <div class="fr-col-12 fr-col-md-4 fr-text--right">
            <button
              @click="handleEdit('ecran_intermediaire_les_desordres')"
              class="fr-btn fr-btn--tertiary fr-btn--icon-left fr-icon-edit-line"
              aria-label="Editer les désordres">Editer</button>
          </div>
        </div>
        <SignalementFormWarning
          v-if="formStore.hasDesordre('desordres_') === false"
          :id="idDisorderOverview+'_warning'"
          label="Vous n'avez renseigné aucun désordre. Pour vous assurer du bon traitement de votre signalement, veuillez sélectionner au moins un désordre."
        />
        <SignalementFormDisorderOverview
          :id="idDisorderOverview"
          :icons="disorderIcons"
          :isValidationScreen="true"
          />
        <p v-html="getFormDataInfosDesordres()"></p>
      </div>

      <!-- LA PROCEDURE  -->
      <div>
        <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
          <div class="fr-col-12 fr-col-md-8">
            <h4 class="fr-h6 fr-mb-0 fr-mb-md-8v">La procédure</h4>
          </div>
          <div class="fr-col-12 fr-col-md-4 fr-text--right">
            <button
              @click="handleEdit('info_procedure')"
              class="fr-btn fr-btn--tertiary fr-btn--icon-left fr-icon-edit-line"
              aria-label="Editer la procédure">Editer</button>
          </div>
        </div>
        <section class="fr-accordion fr-mb-3w">
          <h5 class="fr-accordion__title">
            <button class="fr-accordion__btn" aria-expanded="false" aria-controls="accordion-procedure">Afficher les informations</button>
          </h5>
          <div class="fr-collapse" id="accordion-procedure">
            <p v-html="getFormDataProcedure()"></p>
          </div>
        </section>
      </div>

      <!-- INFORMATIONS COMPLEMENTAIRES  -->
      <div v-if="formStore.data.profil !== 'service_secours'">
        <div v-if="hasInformationsComplementaires()">
          <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--top">
            <div class="fr-col-12 fr-col-md-8">
              <h3 class="fr-h6 fr-mb-0 fr-mb-md-8v">Informations complémentaires</h3>
            </div>
            <div class="fr-col-12 fr-col-md-4 fr-text--right">
              <button
                @click="handleEdit('informations_complementaires')"
                class="fr-btn fr-btn--tertiary fr-btn--icon-left fr-icon-edit-line"
                aria-label="Editer les informations complémentaires"
                >Editer</button>
            </div>
          </div>
          <p v-html="getFormDataInformationsComplementaires()"></p>
        </div>
        <div v-else>
          <h3 class="fr-h4">Informations complémentaires</h3>
          <p>
            Plus nous avons d'informations sur la situation,
            mieux nous pouvons vous accompagner.
            Cliquez sur le bouton pour ajouter des informations.
          </p>
          <button
            type="button"
            class="fr-btn fr-btn--secondary fr-btn--icon-left fr-icon-edit-line"
            @click="handleEdit('informations_complementaires')"
            aria-label="Ajouter des informations complémentaires"
            >
            Ajouter des informations
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'
import dictionaryStore from './../dictionary-store'
import SignalementFormDisorderOverview from './SignalementFormDisorderOverview.vue'
import SignalementFormWarning from './SignalementFormWarning.vue'
import { dictionaryManager } from './../services/dictionaryManager'

export default defineComponent({
  name: 'SignalementFormOverview',
  components: {
    SignalementFormDisorderOverview,
    SignalementFormWarning
  },
  props: {
    id: { type: String, default: null },
    clickEvent: Function,
    // les propriétés suivantes ne sont pas utilisées,
    // mais si on ne les met pas, elles apparaissent dans le DOM
    // et ça soulève des erreurs W3C
    hasError: { type: Boolean, default: false },
    handleClickComponent: Function,
    access_name: { type: String, default: '' },
    access_autocomplete: { type: String, default: '' },
    access_focus: { type: Boolean, default: false }
  },
  data () {
    return {
      formStore,
      dictionaryStore,
      idDisorderOverview: this.id + '_disorder_overview',
      disorderIcons: [{ src: '/img/form/BATIMENT/Picto-batiment.svg', alt: '' }, { src: '/img/form/LOGEMENT/Picto-logement.svg', alt: '' }]
    }
  },
  mounted () {
    this.focusInput()
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
      result += this.addLineIfNeeded('coordonnees_bailleur_nom')
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
      } else if (this.formStore.data.type_logement_nature === 'autre') {
        result += this.addLineIfNeeded('type_logement_nature_autre_precision', 'De type : ')
      }
      result += this.addLineIfNeeded('composition_logement_superficie', 'Superficie en m² : ')
      result += this.addLineIfNeeded('composition_logement_hauteur', 'La hauteur jusqu\'au plafond est de 2m (200cm) ou plus ? ')
      result += this.addLineIfNeeded('composition_logement_piece_unique', 'Une seule ou plusieurs pièces ? ')
      if (this.formStore.data.composition_logement_piece_unique === 'plusieurs_pieces') {
        result += this.addLineIfNeeded('composition_logement_nb_pieces', 'Nombre de pièces à vivre : ')
      }
      result += this.addLineIfNeeded('type_logement_commodites_piece_a_vivre_9m', 'Est-ce qu\'au moins une des pièces à vivre (salon, chambre) fait 9m² ou plus ? ')
      result += this.addLineIfNeeded('type_logement_commodites_cuisine', 'Cuisine ou coin cuisine ? ')
      if (this.formStore.data.type_logement_commodites_cuisine === 'non') {
        result += this.addLineIfNeeded('type_logement_commodites_cuisine_collective', 'Accès à une cuisine collective ? ')
      }
      result += this.addLineIfNeeded('type_logement_commodites_salle_de_bain', 'Salle de bain, salle d\'eau avec douche ou baignoire ? ')
      if (this.formStore.data.type_logement_commodites_salle_de_bain === 'non') {
        result += this.addLineIfNeeded('type_logement_commodites_salle_de_bain_collective', 'Accès à une salle de bain ou des douches collectives ? ')
      }
      result += this.addLineIfNeeded('type_logement_commodites_wc', 'Toilettes (WC) ? ')
      if (this.formStore.data.type_logement_commodites_wc === 'non') {
        result += this.addLineIfNeeded('type_logement_commodites_wc_collective', 'Accès à des toilettes (WC) collectives ? ')
      }
      if (this.formStore.data.type_logement_commodites_cuisine === 'oui' && this.formStore.data.type_logement_commodites_wc === 'oui') {
        result += this.addLineIfNeeded('type_logement_commodites_wc_cuisine', 'Toilettes (WC) et cuisine dans la même pièce ? ')
      }
      result += this.addLineIfNeeded('composition_logement_nombre_personnes', 'Nombre de personnes : ')
      result += this.addLineIfNeeded('composition_logement_nombre_enfants', 'Nombre d\'enfants :  ')
      result += this.addLineIfNeeded('composition_logement_enfants', 'Enfants de moins de 6 ans ? ')
      result += this.addLineIfNeeded('bail_dpe_bail', 'Bail établi ? ')
      result += this.addLineIfNeeded('bail_dpe_invariant', 'Invariant fiscal : ')
      result += this.addLineIfNeeded('bail_dpe_etat_des_lieux', 'Etat des lieux réalisé ? ')
      result += this.addLineIfNeeded('bail_dpe_dpe', 'DPE réalisé ? ')
      result += this.addLineIfNeeded('bail_dpe_classe_energetique', 'Classe énergétique du logement : ')
      result += this.addLineIfNeeded('desordres_logement_chauffage_details_dpe_annee', 'Date du DPE : ')
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
    getFormDataInfosDesordres (): string {
      let result = ''
      result += this.addLineIfNeeded('zone_concernee_debut_desordres', 'Les désordres ont commencé il y a : ')
      if (this.formStore.data.profil !== 'bailleur_occupant' && this.formStore.data.profil !== 'locataire') {
        result += this.addLineIfNeeded('zone_concernee_constatation_desordres', 'Désordres constatés ? ')
      }
      return result
    },
    getFormDataProcedure (): string {
      let result = ''
      result += this.addLineIfNeeded('info_procedure_bailleur_prevenu', 'Bailleur (propriétaire) prévenu ? ')
      if (this.formStore.data.info_procedure_bailleur_prevenu === 'oui') {
        result += this.addLineIfNeeded('info_procedure_bail_moyen', 'Moyen d\'information du bailleur : ')
        result += this.addLineIfNeeded('info_procedure_bail_date', 'Date d\'information du bailleur : ')
        result += this.addLineIfNeeded('info_procedure_bail_reponse', 'Réponse du bailleur : ')
        result += this.addLineIfNeeded('info_procedure_bail_numero', 'Numéro de réclamation fourni par le bailleur : ')
      }
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
      result += this.addLineIfNeeded('informations_complementaires_logement_montant_loyer', 'Montant du loyer sans les charges : ', ' €')
      result += this.addLineIfNeeded('informations_complementaires_logement_nombre_etages', 'Nombre d\'étages du logement : ')
      result += this.addLineIfNeeded('informations_complementaires_logement_annee_construction', 'Année de construction du logement : ')
      result += this.addLineIfNeeded('informations_complementaires_situation_occupants_demande_relogement', 'Demande de relogement ou de logement social : ')
      result += this.addLineIfNeeded('informations_complementaires_situation_occupants_date_emmenagement', 'Date d\'emménagement : ')
      result += this.addLineIfNeeded('informations_complementaires_situation_bailleur_date_effet_bail', 'Date d\'effet du bail : ')
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
          this.isFormDataSet('informations_complementaires_logement_montant_loyer') ||
          this.isFormDataSet('informations_complementaires_logement_nombre_etages') ||
          this.isFormDataSet('informations_complementaires_logement_annee_construction') ||
          this.isFormDataSet('informations_complementaires_situation_occupants_demande_relogement') ||
          this.isFormDataSet('informations_complementaires_situation_occupants_date_emmenagement') ||
          this.isFormDataSet('informations_complementaires_situation_bailleur_date_effet_bail') ||
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
    focusInput () {
      const focusableElement = (this.$refs.firstbutton) as HTMLElement
      if (focusableElement) {
        focusableElement.focus()
      }
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
