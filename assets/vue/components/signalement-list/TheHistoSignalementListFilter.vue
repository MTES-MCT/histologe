<template>
  <section class="fr-background--white" :style="{ display: isVisible ? 'block' : 'none' }">
    <div class="fr-grid-row fr-p-3w fr-pb-6w">
      <div class="fr-col-12">
        <h1 class="fr-mb-2w fr-h2">Liste des signalements</h1>
        <div class="fr-container--fluid">
          <div class="fr-grid-row fr-grid-row--gutters">
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
              <select class="fr-select" aria-describedby="country-select-1-messages" id="country-select-1"
                      autocomplete="country" name="country">
                <option value="" selected disabled hidden>Territoire</option>
                <option value="01">01 - Ain</option>
                <option value="02">02 - Aisne</option>
                <option value="03">03 - Alpes de Haute Provence</option>
                <option value="04">04 - Alpes Maritime</option>
              </select>
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
              <input class="fr-input" placeholder="Taper un nom, référence ou email" type="search"
                     id="search-784-input" name="search-784-input">
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
              <input class="fr-input" placeholder="Commune ou code postal" spellcheck="false" name=""
                     id="" type="text">
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
              <select class="fr-select" aria-describedby="country-select-1-messages" id="country-select-1"
                      autocomplete="country" name="country">
                <option value="" selected disabled hidden>Statut</option>
                <option value="FR">Nouveau</option>
                <option value="DE">En cours</option>
                <option value="IT">Fermé</option>
                <option value="ES">Refusé</option>
              </select>
            </div>
          </div>
          <div class="fr-grid-row fr-grid-row--gutters">
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-2">
              <button class="fr-btn fr-btn--secondary">
                Masquer les options
              </button>
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-8">
              <span class="fr-badge fr-badge--info fr-badge--no-icon fr-badge--sm fr-mx-1v" v-for="count in 8" :key="count">Label badge</span>
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-2">
              <a href="/bo/partenaires/"
                 class="fr-link fr-link--icon-left fr-icon-close-circle-line fr-text--sm">Réinitialiser
                les résultats</a>
            </div>
          </div>
          <div class="fr-grid-row fr-grid-row--gutters">
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
              <select class="fr-select" aria-describedby="country-select-1-messages" id="country-select-1"
                      autocomplete="country" name="country">
                <option value="" selected disabled hidden>Etiquettes</option>
                <option value="FR">Etiquette A</option>
              </select>
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
              <input class="fr-input" spellcheck="false" autocomplete="family-name" name="" id=""
                     type="date" placeholder="Date de dépôt">
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
              <select class="fr-select" aria-describedby="country-select-1-messages" id="country-select-1"
                      autocomplete="country" name="country">
                <option value="" selected disabled hidden>Procédure suspectée</option>
                <option value="FR">Nouveau</option>
                <option value="DE">En cours</option>
                <option value="IT">Fermé</option>
                <option value="ES">Refusé</option>
              </select>
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
              <select class="fr-select" aria-describedby="country-select-1-messages" id="country-select-1"
                      autocomplete="country" name="country">
                <option value="" selected disabled hidden>Statut de la visite</option>
                <option value="FR">Non planifiée</option>
                <option value="DE">Planifiée</option>
                <option value="IT">Conclusion à renseigner</option>
                <option value="ES">Terminée</option>
              </select>
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
              <select class="fr-select" aria-describedby="country-select-1-messages" id="country-select-1"
                      autocomplete="country" name="country">
                <option value="" selected disabled hidden>Type de dernier suivi</option>
                <option value="FR">Non planifiée</option>
                <option value="DE">Planifiée</option>
                <option value="IT">Conclusion à renseigner</option>
                <option value="ES">Terminée</option>
              </select>
            </div>
            <div class="fr-col-12 fr-col-lg-6 fr-col-xl-3">
              <input class="fr-input" spellcheck="false" autocomplete="family-name" name="" id=""
                     type="date" placeholder="Date de dernier suivi">
            </div>
            <div class="fr-col-12 fr-col-lg-4 fr-col-xl-2">
              <select class="fr-select" aria-describedby="country-select-1-messages" id="country-select-1"
                      autocomplete="country" name="country">
                <option value="" selected disabled hidden>Déclarant</option>
                <option value="FR">Non planifiée</option>
                <option value="DE">Planifiée</option>
                <option value="IT">Conclusion à renseigner</option>
                <option value="ES">Terminée</option>
              </select>
            </div>
            <div class="fr-col-12 fr-col-lg-4 fr-col-xl-2">
              <select class="fr-select" aria-describedby="country-select-1-messages" id="country-select-1"
                      autocomplete="country" name="country">
                <option value="" selected disabled hidden>Natude du parc</option>
                <option value="FR">Non planifiée</option>
                <option value="DE">Planifiée</option>
                <option value="IT">Conclusion à renseigner</option>
                <option value="ES">Terminée</option>
              </select>
            </div>
            <div class="fr-col-12 fr-col-lg-4 fr-col-xl-2">
              <select class="fr-select" aria-describedby="country-select-1-messages" id="country-select-1"
                      autocomplete="country" name="country">
                <option value="" selected disabled hidden>Allocataire</option>
                <option value="FR">Non planifiée</option>
                <option value="DE">Planifiée</option>
                <option value="IT">Conclusion à renseigner</option>
                <option value="ES">Terminée</option>
              </select>
            </div>
            <div class="fr-col-12 fr-col-lg-4 fr-col-xl-2">
              <select class="fr-select" aria-describedby="country-select-1-messages" id="country-select-1"
                      autocomplete="country" name="country">
                <option value="" selected disabled hidden>Enfant de - 6 ans</option>
                <option value="FR">Non planifiée</option>
                <option value="DE">Planifiée</option>
                <option value="IT">Conclusion à renseigner</option>
                <option value="ES">Terminée</option>
              </select>
            </div>
            <div class="fr-col-12 fr-col-lg-4 fr-col-xl-2">
              <select class="fr-select" aria-describedby="country-select-1-messages" id="country-select-1"
                      autocomplete="country" name="country">
                <option value="" selected disabled hidden>Situation</option>
                <option value="FR">Non planifiée</option>
                <option value="DE">Planifiée</option>
                <option value="IT">Conclusion à renseigner</option>
                <option value="ES">Terminée</option>
              </select>
            </div>
            <div class="fr-col-12 fr-col-lg-4 fr-col-xl-3">
              <select class="fr-select" aria-describedby="country-select-1-messages" id="country-select-1"
                      autocomplete="country" name="country">
                <option value="" selected disabled hidden>Partenaire affectés</option>
                <option value="FR">Non planifiée</option>
                <option value="DE">Planifiée</option>
                <option value="IT">Conclusion à renseigner</option>
                <option value="ES">Terminée</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'TheHistoSignalementListFilter',
  data () {
    return {
      isVisible: false
    }
  }
})
</script>
