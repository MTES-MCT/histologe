<template>
    <div>
        <button
        :type="type"
        :id="id"
        :class="[ 'fr-btn', css ]"
        @click="onClickLocalEvent"
        >
            {{ label}}
        </button>
        <!-- <button
        :type="type"
        :id="id"
        :disabled="disabled || loading"
        :class="[ this.color, this.loading ? 'loading' : '', 'fr-btn', iconClass ]"
        @click="onClickLocalEvent"
        >
            <slot name="label-loading" v-if="loading"></slot>
            <slot name="label" v-else></slot>
        </button> -->
    </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'SignalementFormButton',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: '' },
    action: { type: String, default: '' },
    type: { type: String, default: 'button' },
    css: { type: String, default: '' },
    // color: { type: String, default: '' },
    // link: { type: String, default: '' },
    // iconClass: { type: String, default: '' },
    // disabled: { type: Boolean, default: false },
    // loading: { type: Boolean, default: false },
    clickEvent: Function
  },
  data () {
    const actions = this.action.split(':')
    const actionType = actions[0]
    const actionParam = actions[1]
    return {
      actionType,
      actionParam
    }
  },
  methods: {
    onClickLocalEvent () {
      if (this.clickEvent !== undefined) {
        this.clickEvent(this.actionType, this.actionParam)
      }
    }
  }
})
</script>

<style>
</style>
