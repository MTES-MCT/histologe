<template>
  <div class="force-height-max">
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
        <nav class="fr-breadcrumb" aria-label="vous êtes ici :">
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
                <a class="fr-breadcrumb__link" aria-current="page">{{ formStore.currentScreen?.screenCategory }}</a>
              </li>
            </ol>
          </div>
        </nav>
      </div>

      <SignalementFormModalBackHome />
    </div>

    <nav class="fr-sidemenu fr-hidden fr-unhidden-md force-height-max">
      <div class="fr-sidemenu__inner force-height-max">
        <div class="fr-collapse" id="fr-sidemenu-wrapper">
          <ul class="fr-sidemenu__list">
            <li
              v-for="menuItem in desktopMenuItems"
              v-bind:key="menuItem.label"
              :class="[ 'fr-sidemenu__item', (menuItem.active) ? 'fr-sidemenu__item--active' : '' ]"
              >
              <button
                v-if="menuItem.current"
                class="fr-link fr-sidemenu__link"
                aria-current="page"
                >{{ menuItem.label }}</button>
              <button
                v-else-if="menuItem.active"
                class="fr-link fr-sidemenu__link"
                @click="handleClick(menuItem.slug)"
                >{{ menuItem.label }}</button>
              <button
                v-else
                class="fr-link fr-sidemenu__link"
                disabled
                >{{ menuItem.label }}</button>
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
import { MenuItem } from '../interfaces/interfaceMenuItem'

export default defineComponent({
  name: 'SignalementFormBreadCrumbs',
  components: {
    SignalementFormModalBackHome
  },
  props: {
    clickEvent: Function
  },
  data () {
    return {
      formStore,
      desktopMenuLabels: [
        { slug: 'adresse_logement_intro', label: 'Adresse et coordonnées' },
        { slug: 'ecran_intermediaire_type_composition', label: 'Type et composition' },
        { slug: 'ecran_intermediaire_situation_occupant', label: 'Situation du foyer' },
        { slug: 'ecran_intermediaire_les_desordres', label: 'Désordres' },
        { slug: 'ecran_intermediaire_procedure', label: 'Procédure' },
        { slug: 'validation_signalement', label: 'Récapitulatif' }
      ]
    }
  },
  computed: {
    desktopMenuItems () {
      const menuItems:Array<MenuItem> = []
      const currentCategoryIndex:number = this.currentCategoryIndex
      for (let i:number = 0; i < this.desktopMenuLabels.length; i++) {
        menuItems.push({ label: this.desktopMenuLabels[i].label, slug: this.desktopMenuLabels[i].slug, active: (i <= currentCategoryIndex && formStore.currentScreen?.slug !== 'confirmation_signalement'), current: (this.desktopMenuLabels[i].label === formStore.currentScreen?.screenCategory) })
      }
      return menuItems
    },
    currentCategoryIndex ():number {
      for (let i:number = 0; i < this.desktopMenuLabels.length; i++) {
        if (this.desktopMenuLabels[i].label === formStore.currentScreen?.screenCategory) {
          return i
        }
      }
      return 0
    }
  },
  methods: {
    handleClick (slug: string) {
      if (this.clickEvent !== undefined) {
        this.clickEvent(slug, false)
      }
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

  .fr-sidemenu__item button.fr-sidemenu__link:disabled {
    color: var(--text-disabled-grey);
  }

  @media (min-width: 48em) {
    .force-height-max {
      height: 100%;
      min-height: 580px;
    }
  }
</style>
