<template>
  <span class="histo-select">
    <select class="fr-select" v-model="valueReturn" :id="id" :name="id" @change="onSelectedEvent">
      <option v-for="item in displayedItems" :value="item.Id" :key="item.Id">{{ item.Text }}</option>
    </select>
  </span>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent( {
  name: 'HistoSelect',
  props: {
    id: { type: String, default: '' },
    innerLabel: { type: String, default: '' },
    value: { type: String, default: '' },
    optionItems: {
		type: Array as () => Array<{ Id: string, Text: string }>,
		default: () => [] 
	},
  },
  data: function () {
    return {
      valueReturn: this.value,
	  displayedItems: [] as Object[]
    }
  },
  methods: {
    onSelectedEvent () {
      // Retour de valeur -> TODO : corriger pour vue3/typescript
      // this.$emit('update:valueReturn', this.valueReturn)
      if (this.onSelect !== undefined) {
        this.onSelect(this.valueReturn)
      }
	  // Rafraichissement des items
	  this.refreshDisplayedItems()
	},

	/**
	 * Mise à jour des items en plaçant le label devant si sélection
	 */
	refreshDisplayedItems () {
		this.displayedItems = []
		const nbItems = this.optionItems.length
		for (let i = 0; i < nbItems; i++) {
			let itemText = this.optionItems[i].Text
			// Préfixe du texte avec le innerLabel
			if (this.innerLabel !== '' && this.valueReturn !== '' && this.valueReturn === this.optionItems[i].Id) {
				itemText = this.innerLabel + ' : ' + this.optionItems[i].Text
			}
			const item = { Id: this.optionItems[i].Id, Text: itemText }
			this.displayedItems.push(item)
		}
	}
  },
  mounted () {
	this.refreshDisplayedItems()
  }
} )
</script>

<style>
</style>
