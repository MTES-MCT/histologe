<template>
	<span class="histo-button">
		<button
		  :type="type"
		  :id="id"
		  :disabled="disabled || loading"
		  :class="[ this.color, this.loading ? 'loading' : '', 'fr-btn', iconClass ]"
		  @click="onClickLocalEvent"
		  >
			<slot name="label-loading" v-if="loading"></slot>
			<slot name="label" v-else></slot>
		</button>
	</span>
</template>

<script lang="ts">
export default {
	name: 'HistoButton',
	props: {
		id: { type: String, default: null },
		type: { type: String, default: 'submit' },
		color: { type: String, default: '' },
		link: { type: String, default: '' },
		iconClass: { type: String, default: '' },
		disabled: { type: Boolean, default: false },
		loading: { type: Boolean, default: false },
		clickEvent: Function
	},
	computed: {
	},
	methods: {
		onClickLocalEvent () {
			if (this.link !== '' && this.link !== undefined) {
				this.disabled = true
				window.location = this.link
			}
			if (this.clickEvent !== undefined) {
				this.clickEvent(this._props)
			}
		}
	}
}
</script>

<style>
</style>
