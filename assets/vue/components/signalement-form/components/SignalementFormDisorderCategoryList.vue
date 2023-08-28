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
    const listSelectedDisorders = new Array<string>()
    return {
      formStore,
      actionType: (this.action.includes(':')) ? this.action.split(':')[0] : '',
      actionParam: (this.action.includes(':')) ? this.action.split(':')[1] : '',
      listSelectedDisorders
    }
  },
  created () {
    setTimeout(() => {
      this.handleUpdateSelected('', false)
    }, 50)
  },
  methods: {
    handleUpdateSelected (idDisorder: string, isSelected: boolean) {
      if (idDisorder !== '') {
        if (isSelected) {
          this.listSelectedDisorders.push(idDisorder)
        } else {
          const index = this.listSelectedDisorders.indexOf(idDisorder)
          if (index > -1) {
            this.listSelectedDisorders.splice(index, 1)
          }
        }
      }
      if (this.clickEvent !== undefined) {
        this.clickEvent(this.actionType, this.actionParam, this.listSelectedDisorders.length > 0 ? '1' : '0')
      }
    }
  }
})
</script>

<style>
</style>
