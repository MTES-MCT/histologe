<template>
  <div
    class="histo-multi-select"
    @focusout="isExpanded = false"
    tabindex="0"
    >
    <div
      class="selector fr-select"
      @click="isExpanded = !isExpanded"
      >
      {{ innerLabel }} ({{ strCountSelectedItems }})
    </div>

    <div v-if="isExpanded" class="selector-items">
      <div class="selector-items-selected">
        <ul>
          <li
            v-for="item in selectedItems"
            :data-optionid="item.Id"
            @click="handleRemoveItem"
            >
            {{ item.Text }}
          </li>
        </ul>
      </div>
      <div class="selector-items-remaining">
        <ul>
          <li
            v-for="item in remainingItems"
            :data-optionid="item.Id"
            @click="handleClickItem"
            >
            {{ item.Text }}
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import HistoInterfaceSelectOption from './HistoInterfaceSelectOption'

export default defineComponent({
  name: 'HistoMultiSelect',
  props: {
    id: { type: String, default: '' },
    modelValue: { type: String, default: '' },
    onChange: { type: Function },
    innerLabel: { type: String, default: '' },
    optionItems: {
      type: Array as () => Array<HistoInterfaceSelectOption>,
      default: () => []
    }
  },
  data: function () {
    return {
      isExpanded: false,
      selectedItems: new Array<HistoInterfaceSelectOption>(),
      remainingItems: new Array<HistoInterfaceSelectOption>()
    }
  },
  emits: ['update:modelValue'],
  methods: {
    handleClickItem (event:any) {
      const clickedElement:any = event.target
      const clickedOptionId:string = clickedElement.dataset.optionid
      // add item in selected ones
      for (const element of this.optionItems) {
        if (element.Id == clickedOptionId) {
          this.selectedItems.push(element)
          break
        }
      }
      // remove item from remaining ones
      for (let i:number = 0; i < this.remainingItems.length; i++) {
        if (this.remainingItems[i].Id == clickedOptionId) {
          this.remainingItems.splice(i, 1)
          break
        }
      }
    },
    handleRemoveItem (event:any) {
      const clickedElement:any = event.target
      const clickedOptionId:string = clickedElement.dataset.optionid
      // add item in selected ones
      for (const element of this.optionItems) {
        if (element.Id == clickedOptionId) {
          this.remainingItems.push(element)
          break
        }
      }
      // remove item from remaining ones
      for (let i:number = 0; i < this.selectedItems.length; i++) {
        if (this.selectedItems[i].Id == clickedOptionId) {
          this.selectedItems.splice(i, 1)
          break
        }
      }
    }
  },
  computed: {
    strCountSelectedItems() {
      if (this.selectedItems.length > 1) {
        return this.selectedItems.length + ' sélectionnées'
      }
      return this.selectedItems.length + ' sélectionnée'
    }
  },
  mounted () {
    this.remainingItems = []
    for (const element of this.optionItems) {
      this.remainingItems.push(element)
    }
  }/*,
  updated () {
    this.refreshDisplayedItems(this.modelValue)
  }*/
})
</script>

<style>
  .histo-multi-select {
    width: 100%;
    position: relative;
  }

  .histo-multi-select .selector {
    background-color: #FFF;
  }

  .histo-multi-select .selector-items {
    position: absolute;
    z-index: 10;
    width: 100%;
    font-size: 1rem;
    line-height: 1.5rem;
    background-color: #FFF;
    border-radius: 4px;
  }

  .histo-multi-select .selector-items .selector-items-selected {
    padding: .5rem 2.5rem .5rem 1rem;
    border-bottom: 1px solid var(--blue-france-850-200);
  }
  .histo-multi-select .selector-items .selector-items-selected ul {
    list-style: none;
    padding: 0px;
  }
  .histo-multi-select .selector-items .selector-items-selected ul li {
    display: inline;
    height: 30px;
    margin-right: .5rem;
    padding: .5rem;
    border: 1px solid var(--blue-france-850-200);
    border-radius: 4px;
    cursor: pointer;
  }

  .histo-multi-select .selector-items .selector-items-remaining ul {
    list-style: none;
    padding: 0px;
  }
  .histo-multi-select .selector-items .selector-items-remaining ul li {
    padding: .5rem 2.5rem .5rem 1rem;
  }
  .histo-multi-select .selector-items .selector-items-remaining ul li:hover {
    background-color: var(--blue-france-850-200);
    cursor: pointer;
  }
  
</style>
