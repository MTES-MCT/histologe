<template>
  <div>
    <div class="fr-hidden-md">
      <!-- Indicateur de progression -->
      <!-- TODO : vérifier les instructions de mathilde sur ce bloc-->
      <div class="progress-indicator">
        <div
          v-for="(step, index) in desktopMenuItems"
          :key="index"
          :class="['progress-indicator__step', { 'progress-indicator__step--completed': index <= currentCategoryIndex }]"
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

    <nav class="fr-sidemenu fr-hidden fr-unhidden-md" aria-labelledby="fr-sidemenu-title">
      <div class="fr-sidemenu__inner">
        <div class="fr-collapse" id="fr-sidemenu-wrapper">
          <div class="fr-sidemenu__title" id="fr-sidemenu-title">Mon signalement</div>
          <ul class="fr-sidemenu__list">
            <li
              v-for="menuItem in desktopMenuItems"
              v-bind:key="menuItem.label"
              :class="[ 'fr-sidemenu__item', menuItem.active ? 'fr-sidemenu__item--active' : '' ]"
              >
              <a
                v-if="menuItem.current"
                class="fr-sidemenu__link"
                href="#"
                aria-current="page"
                >{{ menuItem.label }}</a
              >
              <a
                v-else-if="menuItem.active"
                class="fr-sidemenu__link"
                href="#"
                >{{ menuItem.label }}</a
              >
              <a
                v-else
                class="fr-sidemenu__link"
                >{{ menuItem.label }}</a
              >
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'
import SignalementFormModalBackHome from './SignalementFormModalBackHome.vue'

interface MenuItem {
  label: string,
  active: boolean,
  current: boolean
}

export default defineComponent({
  name: 'SignalementFormBreadCrumbs',
  components: {
    SignalementFormModalBackHome
  },
  data () {
    return {
      formStore,
      desktopMenuLabels: [
        'Adresse et coordonnées',
        'Type et composition',
        'Situation du foyer',
        'Désordres',
        'Procédure',
        'Récapitulatif'
      ]
    }
  },
  computed: {
    desktopMenuItems () {
      const menuItems:Array<MenuItem> = []
      const currentCategoryIndex:number = this.currentCategoryIndex
      for (let i:number = 0; i < this.desktopMenuLabels.length; i++) {
        menuItems.push({ label: this.desktopMenuLabels[i], active: (i <= currentCategoryIndex), current: (this.desktopMenuLabels[i] === formStore.screenData[formStore.currentScreenIndex].screenCategory) })
      }
      return menuItems
    },
    currentCategoryIndex ():number {
      for (let i:number = 0; i < this.desktopMenuLabels.length; i++) {
        if (this.desktopMenuLabels[i] === formStore.screenData[formStore.currentScreenIndex].screenCategory) {
          return i
        }
      }
      return 0
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

  .fr-sidemenu__item a.fr-sidemenu__link:not([href]) {
    color: var(--text-disabled-grey);
  }
</style>
