<template>
  <div>
    <!-- Indicateur de progression -->
    <!-- TODO : vérifier les instructions de mathilde sur ce bloc-->
    <div class="progress-indicator">
      <div
        v-for="(step, index) in formStore.screenData"
        :key="index"
        :class="['progress-indicator__step', { 'progress-indicator__step--completed': index <= formStore.currentScreenIndex }]"
        ></div>
    </div>

     <!-- Fil d'ariane -->
    <div class="fr-px-5w">
      <nav role="navigation" class="fr-breadcrumb" aria-label="vous êtes ici :">
        <button class="fr-breadcrumb__button" aria-expanded="false" aria-controls="breadcrumb-1">Voir le fil d’Ariane</button>
        <div class="fr-collapse" id="breadcrumb-1">
          <ol class="fr-breadcrumb__list">
            <li>
              <a class="fr-breadcrumb__link" href="#" data-fr-opened="false" aria-controls="fr-modal-back-home">Accueil</a>
            </li>
            <li>
              <a class="fr-breadcrumb__link" href="/nouveau-formulaire/signalement/">Signalement</a>
            </li>
            <li>
              <a class="fr-breadcrumb__link" aria-current="page">{{ formStore.screenData[formStore.currentScreenIndex].label }}</a>
            </li>
          </ol>
        </div>
      </nav>
    </div>

    <SignalementFormModalBackHome />
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'
import SignalementFormModalBackHome from './SignalementFormModalBackHome.vue'

export default defineComponent({
  name: 'SignalementFormBreadCrumbs',
  components: {
    SignalementFormModalBackHome
  },
  data () {
    return {
      formStore
    }
  }
})
</script>

<style>
  .progress-indicator {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    width: 100%;
    height: 5px;
    background-color: #f0f0f0;
  }

  .progress-indicator__step {
    flex-grow: 1;
    flex-shrink: 0;
    height: 100%;
    background-color: #e0e0e0;
    margin-right: 5px;
  }

  .progress-indicator__step:last-child {
    margin-right: 0;
  }

  .progress-indicator__step--completed {
    background-color: #c2a1f8;
  }
</style>
