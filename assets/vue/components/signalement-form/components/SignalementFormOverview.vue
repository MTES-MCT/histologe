<template>
  <div :id="id">
    <div class="fr-container">
      <h2>Récapitulatif</h2>

      <h3>Adresse du logement</h3>
      <a href="#" @click="handleEdit('adresse_logement')">Editer</a>
      <p v-html="getFormDataAdresse()"></p>

      <!-- TODO : changer intitulés ? -->
      <h3>Vos coordonnées</h3>
      <a v-if="formStore.data.profil === 'bailleur_occupant' || formStore.data.profil === 'locataire'" href="#" @click="handleEdit('vos_coordonnees_occupant')">Editer</a>
      <a v-else href="#" @click="handleEdit('vos_coordonnees_tiers')">Editer</a>
      <p v-html="getFormDataCoordonneesOccupant()"></p>

      <!-- TODO : si profil est bailleur ou bailleur_occupant que met-on ? -->
      <h3>Les coordonnées du bailleur</h3>
      <a href="#" @click="handleEdit('coordonnees_bailleur')">Editer</a>
      <p v-html="getFormDataCoordonneesBailleur()"></p>

      <h3>Type et composition du logement</h3>
      <a href="#" @click="handleEdit('ecran_intermediaire_type_composition')">Editer</a>
      <section class="fr-accordion">
        <h3 class="fr-accordion__title">
          <button class="fr-accordion__btn" aria-expanded="false" aria-controls="accordion-type-composition">Afficher les informations</button>
        </h3>
        <div class="fr-collapse" id="accordion-type-composition">
          <p v-html="getFormDataTypeComposition()"></p>
        </div>
      </section>

      <h3>Votre situation</h3>
      <a href="#" @click="handleEdit('ecran_intermediaire_situation_occupant')">Editer</a>
      <section class="fr-accordion">
        <h3 class="fr-accordion__title">
          <button class="fr-accordion__btn" aria-expanded="false" aria-controls="accordion-situation-occupant">Afficher les informations</button>
        </h3>
        <div class="fr-collapse" id="accordion-situation-occupant">
          <p v-html="getFormDataSituationOccupant()"></p>
        </div>
      </section>

      <h3>TODO : Les désordres</h3>
      <a href="#" @click="handleEdit('ecran_intermediaire_les_desordres')">Editer</a>

      <h3>TODO : La procédure</h3>
      <a href="#" @click="handleEdit('info_procedure')">Editer</a>

      <div v-if="formStore.data.profil === 'bailleur_occupant' || formStore.data.profil === 'locataire'">
        <h2>TODO : Informations complémentaires</h2>

        <p>
          Plus nous avons d'informations sur la situation,
          mieux nous pouvons vous accompagner.
          Cliquez sur le bouton pour ajouter des informations.
        <a href="#" @click="handleEdit('informations_complementaires')">Editer</a>
        </p>
      </div>

      Ma situation personnelle
      <br>
      Mon logement
      <br>
      <button
        type="button"
        class="fr-btn fr-secondary"
        @click="handleEdit('')"
        >
          Ajouter des informations
      </button>
      <br><br>

      <h2>Message à l'administration (facultatif)</h2>
      <p>Ce message sera joint à votre signalement.</p>
      <textarea placeholder="Votre message ici"></textarea>

    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'

export default defineComponent({
  name: 'SignalementFormOverview',
  props: {
    id: { type: String, default: null },
    clickEvent: Function
  },
  data () {
    return {
      formStore
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
