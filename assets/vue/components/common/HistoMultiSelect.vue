<template>
  <div
    :class="['histo-multi-select', active ? 'active' : 'inactive']"
    @focusout="isExpanded = false"
    tabindex="0"
    >
    <div
      class="selector fr-select"
      @click="handleClickSelect"
      >
      {{ innerLabel }} ({{ strCountSelectedItems }})
    </div>

    <div v-if="isExpanded" class="selector-items">
      <div class="selector-items-selected">
        <ul>
          <template v-for="item in optionItems">
            <li
              v-if="modelValue.indexOf(item.Id) > -1"
              :data-optionid="item.Id"
              @click="handleRemoveItem"
              >
              {{ unbreakText(item.Text) }}&nbsp;&nbsp;X
            </li>
          </template>
        </ul>
      </div>
      <div class="selector-items-remaining">
        <ul>
          <template v-for="item in optionItems">
            <li
              v-if="modelValue.indexOf(item.Id) == -1"
              :data-optionid="item.Id"
              @click="handleClickItem"
              >
              {{ item.Text }}
            </li>
          </template>
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
    onChange: { type: Function },
    innerLabel: { type: String, default: '' },
    isInnerLabelFemale: { type: Boolean, default: true },
    modelValue: {
      type: Array as () => Array<string>,
      default: () => []
    },
    optionItems: {
      type: Array as () => Array<HistoInterfaceSelectOption>,
      default: () => []
    },
    active: { type: Boolean, default: true }
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
    handleClickSelect () {
      if (this.active) {
        this.isExpanded = !this.isExpanded
      }
    },
    handleClickItem (event:any) {
      const clickedElement:any = event.target
      const clickedOptionId:string = clickedElement.dataset.optionid
      this.modelValue.push(clickedOptionId)
			this.$emit('update:modelValue', this.modelValue)
      if (this.onChange !== undefined) {
        this.onChange()
      }
    },
    handleRemoveItem (event:any) {
      const clickedElement:any = event.target
      const clickedOptionId:string = clickedElement.dataset.optionid
      for (let i:number = this.modelValue.length - 1; i >= 0; i--) {
        if (this.modelValue[i] == clickedOptionId) {
          this.modelValue.splice(i, 1)
        }
      }
			this.$emit('update:modelValue', this.modelValue)
      if (this.onChange !== undefined) {
        this.onChange()
      }
    },
    unbreakText (text:string) {
      if (text.length > 14) {
        return text.substring(0, 14) + '...'
      }
      return text
    }
  },
  computed: {
    strCountSelectedItems() {
      const selectLabel:string = this.isInnerLabelFemale ? 'sélectionnée' : 'sélectionné'
      if (this.modelValue.length > 1) {
        return this.modelValue.length + ' ' + selectLabel + 's'
      }
      return this.modelValue.length + ' ' + selectLabel
    }
  }
})
</script>

<style>
  .histo-multi-select {
    width: 100%;
    position: relative;
  }
  .histo-multi-select.inactive {
    opacity: 0.5;
  }

  .histo-multi-select .selector {
    background-color: #FFF;
    cursor: pointer;
  }
  .histo-multi-select.inactive .selector {
    cursor: not-allowed;
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
    display: inline-block;
    height: 34px;
    line-height: 34px;
    vertical-align: middle;
    margin-right: .5rem;
    margin-bottom: .2rem;
    padding: .1rem .3rem;
    border: 1px solid var(--blue-france-sun-113-625);
    border-radius: 4px;
    cursor: pointer;
    color: var(--blue-france-sun-113-625);
  }

  .histo-multi-select .selector-items .selector-items-remaining ul {
    list-style: none;
    padding: 0px;
    max-height: 250px;
    overflow: auto;
  }
  .histo-multi-select .selector-items .selector-items-remaining ul li {
    padding: .5rem 2.5rem .5rem 1rem;
  }
  .histo-multi-select .selector-items .selector-items-remaining ul li:hover {
    background-color: var(--blue-france-850-200);
    cursor: pointer;
  }
  
</style>
