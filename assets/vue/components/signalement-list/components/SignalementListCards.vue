<template>
  <div class="fr-grid-row fr-my-2w" v-for=" (item, index) in list" :key="index">
    <div class="fr-col-12">
      <div class="fr-card">
        <div class="fr-card__body">
          <div class="fr-card__content">
            <div class="fr-grid-row">
              <div class="fr-col-xl-8 fr-col-lg-6 fr-col-12">
                <h3 class="fr-card__title fr-my-1v">#{{ item.reference }} - {{ item.nomOccupant && item.nomOccupant.toUpperCase()  + ' ' + item.prenomOccupant }}</h3>
                <p class="fr-my-1v fr-text--bold fr-text--lg">{{ item.adresseOccupant }}, {{ item.codepostalOccupant }} {{ item.villeOccupant }}</p>
              </div>
              <div class="fr-col-xl-4 fr-col-lg-6 fr-col-12">
                <p class="fr-my-1v fr-text--right">Dossier déposé le : {{ formatDate(item.createdAt )}}</p>
                <p class="fr-my-1v fr-text--right">Déclarant : {{ item.profileDeclarant }}</p>
              </div>
            </div>
            <div class="fr-grid-row fr-mt-1w">
              <div class="fr-col-xl-10 fr-col-12">
                <p class="fr-my-1w" v-if="item.conclusionsProcedure === null"><span class="fr-icon-lightbulb-line" aria-hidden="true"></span>
                  Procédure(s) suspectée(s) :
                  <span v-if="item.qualificationsStatusesLabels.length === 0" class="fr-text--sm">Aucune</span>
                  <span
                      v-else
                      v-for="(procedure, index) in item.qualificationsStatusesLabels"
                      :key="index"
                      class="fr-badge fr-badge--sm fr-badge--no-icon fr-mx-1v"
                      :class="getBadgeClassNameQualification(procedure)"
                      >{{ getBadgeLabelQualification(procedure) }}</span>
                </p>
                <p v-else class="fr-my-1w"><span class="fr-icon-lightbulb-line" aria-hidden="true"></span>
                  Procédure(s) constatée(s) :
                  <span
                      v-for="(procedure, index) in item.conclusionsProcedure"
                      :key="index"
                      class="fr-badge fr-badge--sm fr-badge--no-icon fr-mx-1v"
                      :class="getBadgeClassNameProcedure(procedure)"
                  >{{ procedure }}</span>
                </p>
                <p class="fr-my-1w"><span class="fr-icon-briefcase-line" aria-hidden="true"></span>
                  Partenaire(s) affecté(s) :
                  <span v-if="Object.keys(item.affectations).length === 0" class="fr-text--sm">Aucun</span>
                  <span
                      v-else
                      class="fr-mx-1v"
                      :class="getBadgeStyleAffectation(affectation)"
                      v-for="(affectation, index) in item.affectations"
                      :key="index">{{ affectation.partner }}</span>
                </p>
                <p v-if="item.lastSuiviBy !== null">
                  <span class="fr-icon-discuss-line" aria-hidden="true"></span>
                  Dernier suivi par {{ getSuiviLabel(item.lastSuiviBy) }} le {{ formatDate(item.lastSuiviAt) }}
                  <span :class="getBadgeSuivi(item.lastSuiviBy)">{{ getSuiviVisibility(item.lastSuiviIsPublic) }}</span>
                </p>
                <p v-else>
                  <span class="fr-icon-discuss-line" aria-hidden="true"></span> Aucun suivi effectué
                </p>
              </div>
              <div class="fr-col-xl-2 fr-col-12 fr-text--right">
                <p :class="getStatusLabel(item.statut).className">{{ getStatusLabel(item.statut).label}}</p>
              </div>
            </div>
          </div>
          <div class="fr-card__footer">
            <div class="fr-grid-row">
              <div class="fr-col-12 fr-text--right">
                <div class="fr-display-inline-flex">
                  <button v-if="sharedState.user.canDeleteSignalement" @click="deleteSignalementItem(item)"
                          class="fr-btn fr-btn--icon-left fr-btn--secondary fr-mx-1w fr-icon-delete-line">
                    Supprimer le signalement
                  </button>
                  <a :href="`/bo/signalements/${item.uuid}`" class="fr-btn fr-btn--icon-right fr-icon-arrow-right-line fr-mx-1w">
                    Accéder au dossier
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { SignalementItem } from '../interfaces/signalementItem'
import { store } from '../store'

export default defineComponent({
  name: 'SignalementListCards',
  props: {
    list: {
      type: Object
    }
  },
  emits: ['deleteSignalementItem'],
  data () {
    return {
      sharedState: store.state
    }
  },
  methods: {
    formatDate (dateString: string | null): string {
      if (!dateString) {
        return ''
      }

      const date = new Date(dateString)
      const day = date.getDate().toString().padStart(2, '0')
      const month = (date.getMonth() + 1).toString().padStart(2, '0')
      const year = date.getFullYear()

      return `${day}/${month}/${year}`
    },
    getBadgeStyleAffectation (affectation: any): string {
      let className = 'fr-badge'
      if (!this.sharedState.user.canSeeStatusAffectation) {
        return ''
      }
      switch (affectation.statut) {
        case 0:
          className += ' fr-badge--info'
          break
        case 1:
          className += ' fr-badge--success'
          break
        case 2:
          className += ' fr-text-label--red-marianne fr-background-contrast--red-marianne fr-fi-close-line'
          break
        case 3:
          className += ' fr-badge--error'
          break
      }

      return className
    },
    getStatusLabel (status: number): Object {
      const statusSignalement = { className: 'fr-badge fr-badge--no-icon ', label: '' }
      switch (status) {
        case 1:
          statusSignalement.className += 'fr-badge--error'
          statusSignalement.label = 'A valider'
          break
        case 2:
          statusSignalement.className += 'fr-badge--success'
          statusSignalement.label = 'En cours'
          break
        case 3:
          statusSignalement.className += 'fr-badge--info'
          statusSignalement.label = 'En attente'
          break
        case 6:
          statusSignalement.className += 'fr-badge--blue-france-975'
          statusSignalement.label = 'Fermé'
          break
        case 8:
          statusSignalement.className += 'fr-badge-grey'
          statusSignalement.label = 'Refusé'
          break
      }

      return statusSignalement
    },
    getBadgeClassNameQualification (label: string): string {
      let className = 'fr-badge--info'

      if (['Suspicion Danger occupant', 'Mise en sécurité/Péril', 'Suspicion Mise en sécurité/Péril'].includes(label)) {
        className = 'fr-badge--error'
      } else if (['Suspicion Insalubrité', 'Insalubrité', 'Suspicion Manquement à la salubrité'].includes(label)) {
        className = 'fr-badge--warning'
      }
      return className
    },
    getBadgeLabelQualification (label: string) {
      let labelText = label
      labelText = labelText.replace('Suspicion ', '')
      labelText = labelText.replace(' à vérifier', '')
      labelText = labelText.replace(' avérée', '')
      return labelText
    },
    getBadgeClassNameProcedure (label: string): string {
      let className = 'fr-badge--'

      if (label === 'Mise en sécurité / Péril') {
        className += 'error'
      } else if (label === 'Insalubrité') {
        className += 'warning'
      } else if (label === 'Logement décent / Pas d\'infraction') {
        className += 'success'
      } else if (label === 'Responsabilité occupant / Assurantiel') {
        className += 'blue-ecume'
      } else if (label === 'Non décence' || label === 'Infraction RSD') {
        className += 'info'
      }

      return className
    },
    getBadgeSuivi (label: string): string {
      let className = 'fr-badge fr-badge--sm fr-badge--no-icon'

      if (label === 'OCCUPANT' || label === 'DECLARANT' || label === 'Aucun') {
        className += ' fr-badge--warning'
      }

      return className
    },
    getSuiviLabel (label: string): string {
      let suiviLabel = label
      if (label === 'Aucun') {
        suiviLabel = 'occupant ou déclarant'
      }
      return suiviLabel
    },
    getSuiviVisibility (label: boolean): string {
      return label ? 'Visible par l\'usager' : 'Suivi interne'
    },
    deleteSignalementItem (item: SignalementItem) {
      const confirmation = confirm('Êtes-vous sûr de vouloir supprimer ce signalement ?')
      if (confirmation) {
        this.$emit('deleteSignalementItem', item)
      }
    }
  }
})
</script>
<style scoped>
  .fr-card__content {
    margin: 0 -2rem -3rem -2rem;
  }

  .fr-card__footer {
    margin: 0 -2.5rem;
  }

  @media (max-width: 1250px) {
    .fr-card__footer {
      margin: 2rem -0.5rem;
      padding: 0;
    }
  }
</style>
