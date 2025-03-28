<template>
  <div class="fr-select-group">
    <label class="fr-label" :for="id">
      <slot name="label"></slot>
    </label>
    <select
      class="fr-select"
      :id="id"
      :name="id"
      :value="modelValue"
      @change="onSelectedEvent"
      >
      <option v-if="placeholder" value="" selected disabled hidden>{{ placeholder }}</option>
      <option v-for="item in displayedItems" :value="item.Id" :key="item.Id">{{ item.Text }}</option>
    </select>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import HistoInterfaceSelectOption from './HistoInterfaceSelectOption'

export default defineComponent({
  name: 'HistoSelect',
  props: {
    id: { type: String, default: '' },
    modelValue: { type: String, default: '' },
    onSelect: { type: Function },
    innerLabel: { type: String, default: '' },
    optionItems: {
      type: Array as () => Array<HistoInterfaceSelectOption>,
      default: () => []
    },
    placeholder: { type: String, default: '' }
  },
  data: function () {
    return {
      displayedItems: new Array<HistoInterfaceSelectOption>()
    }
  },
  emits: ['update:modelValue'],
  methods: {
    onSelectedEvent (e: any) {
      this.$emit('update:modelValue', e.target.value)
      if (this.onSelect !== undefined) {
        this.onSelect(e.target.value)
      }
      this.refreshDisplayedItems(e.target.value)
    },

    /**
     * Mise à jour des items en plaçant le label devant si sélection
     */
    refreshDisplayedItems (selectedValue: string) {
      this.displayedItems = new Array<HistoInterfaceSelectOption>()
      const nbItems = this.optionItems.length
      for (let i = 0; i < nbItems; i++) {
        let itemText = this.optionItems[i].Text
        // Préfixe du texte avec le innerLabel
        if (this.innerLabel !== '' && selectedValue !== '' && selectedValue === this.optionItems[i].Id) {
          itemText = this.innerLabel + ' : ' + this.optionItems[i].Text
        }
        const item = new HistoInterfaceSelectOption()
        item.Id = this.optionItems[i].Id
        item.Text = itemText
        this.displayedItems.push(item)
      }
    }
  },
  mounted () {
    this.refreshDisplayedItems(this.modelValue)
  }
})
</script>

<style>
  .fr-select-group select {
    background-color: var(--background-contrast-grey);
  }
</style>
