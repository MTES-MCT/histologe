<template>
  <div class="histo-checkbox">
    <div class="fr-checkbox-group">
      <input type="checkbox" :id="id" :name="id" v-model="valueReturn" @change="onChangeLocalEvent">
      <label class="fr-label" :for="id"><slot name="label"></slot></label>
    </div>
  </div>
</template>

<script>
export default {
	name: 'WDGCheckbox',
	props: {
		id: { type: String, default: null },
    value: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false }
  },
	data () {
		return {
		  valueReturn: this.value
		}
	},
	methods: {
		onChangeLocalEvent () {
			this.$emit('update:valueReturn', this.valueReturn)
		}
	}
}
</script>

<style>
</style>
