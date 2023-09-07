<template>
  <div :id="id" class="signalement-form-disorder-category-list fr-container--fluid fr-my-3v">
    <div v-if="components != undefined" class="fr-grid-row fr-grid-row--gutters">
      <div
        v-for="component in components.body"
        v-bind:key="component.slug"
        class="fr-col-6 fr-col-md-4"
        >
          <SignalementFormDisorderCategoryItem
            :id="component.slug"
            :label="component.label"
            :iconSrc="component.icon.src"
            :clickEvent="handleUpdateSelected"
            />
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'
import SignalementFormDisorderCategoryItem from './SignalementFormDisorderCategoryItem.vue'

export default defineComponent({
  name: 'SignalementFormDisorderCategoryList',
  components: {
    SignalementFormDisorderCategoryItem
  },
  props: {
    id: { type: String, default: null },
    action: { type: String, default: '' },
    components: Object,
    clickEvent: Function
  },
  data () {
    return {
      formStore,
      actionType: (this.action.includes(':')) ? this.action.split(':')[0] : '',
      actionParam: (this.action.includes(':')) ? this.action.split(':')[1] : ''
    }
  },
  created () {
    setTimeout(() => {
      this.handleUpdateSelected('', false)
    }, 50)
  },
  methods: {
    handleUpdateSelected (idDisorder: string, isSelected: boolean) {
      if (!formStore.data.categorieDisorders) {
        formStore.data.categorieDisorders = {
          batiment: [],
          logement: []
        }
      }
      if (idDisorder !== '') {
        const category = idDisorder.includes('batiment') ? 'batiment' : 'logement'
        const indexInList = formStore.data.categorieDisorders[category].indexOf(idDisorder)

        if (isSelected && indexInList === -1) {
          formStore.data.categorieDisorders[category].push(idDisorder)
        } else if (!isSelected && indexInList !== -1) {
          formStore.data.categorieDisorders[category].splice(indexInList, 1)
          this.deleteDisorder(idDisorder)
        }
      }
      if (this.clickEvent !== undefined) {
        this.clickEvent(this.actionType, this.actionParam, this.hasSelectedDisorders() ? '1' : '0')
      }
    },
    hasSelectedDisorders () {
      return formStore.data.categorieDisorders.batiment.length > 0 || formStore.data.categorieDisorders.logement.length > 0
    },
    deleteDisorder (idDisorder: string) {
      for (const dataname in formStore.data) {
        if (dataname.includes(idDisorder)) {
          delete formStore.data[dataname]
        }
      }
    }
  }
})
</script>

<style>
</style>
