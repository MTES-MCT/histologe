<template>
  <div class="fr-col-12">
    <div class="fr-card">
      <div class="fr-card__body">
        <div class="fr-card__content fr-mb-1v">
          <div class="fr-grid-row">
            <div class="fr-col-xl-8 fr-col-lg-6 fr-col-12">
              <h3 class="fr-card__title fr-my-1v">#2023-674 - DUPONT Philippe</h3>
              <p class="fr-my-1v fr-text--bold fr-text--lg">17 boulevard saade - quai joliette, 13005 Marseille</p>
            </div>
            <div class="fr-col-xl-4 fr-col-lg-6 fr-col-12">
              <p class="fr-my-1v fr-text--right">Dossier déposé le : 29/03/2023</p>
              <p class="fr-my-1v fr-text--right">Déclarant : Occupant</p>
            </div>
          </div>
          <div class="fr-grid-row fr-mt-1w">
            <div class="fr-col-xl-8 fr-col-12">
              <p class="fr-my-1w"><span class="fr-icon-lightbulb-line" aria-hidden="true"></span>
                Procédure(s) suspectée(s):
                <span class="fr-badge fr-badge--sm fr-badge--warning fr-badge--no-icon fr-mx-1w">Manquement à la salubrité</span>
                <span class="fr-badge fr-badge--sm fr-badge--info fr-badge--no-icon fr-mx-1w">RSD / NON DÉCENCE</span>
              </p>
              <p class="fr-my-1w"><span class="fr-icon-briefcase-line" aria-hidden="true"></span>
                Partenaire(s) affecté(s) : EHMA - Métropole, ADIL, SOLIHA-DALO, SCHS de
                la commune</p>
              <p class="fr-my-1w"><span class="fr-icon-discuss-line" aria-hidden="true"></span>
                Dernier suivi par EMHA - Métropole le 30/03/2023
                <span class="fr-badge fr-badge--sm fr-badge--no-icon">Suivi interne</span>
              </p>
            </div>
            <div class="fr-col-xl-4 fr-col-12 fr-text--right">
              <p class="fr-badge fr-badge--error fr-badge--no-icon">A VALIDER</p>
            </div>
          </div>
        </div>
        <div class="fr-card__footer">
          <div class="fr-grid-row">
            <div class="fr-col-12 fr-text--right">
              <div class="fr-display-inline-flex">
                <button class="fr-btn fr-btn--icon-left fr-icon-delete-line fr-btn--secondary fr-mx-1w">
                  Supprimer le signalement
                </button>
                <button class="fr-btn fr-btn--icon-right fr-icon-arrow-right-line fr-mx-1w">
                  Accéder au dossier
                </button>
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

export default defineComponent({
  name: 'TheHistoSignalementListCard'
})
</script>

<style scoped>

</style>
