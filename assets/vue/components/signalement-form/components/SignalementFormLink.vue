<template>
  <div class="signalement-form-link">
    <a
      :id="id"
      :class="[ customCss ]"
      :href="variablesReplacer.replace(link)"
      :target="linktarget"
      @click="handleClick"
      :aria-controls="ariaControls"
      data-fr-opened="false"
      :title="description"
      >
        {{ label}}
    </a>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { variablesReplacer } from './../services/variableReplacer'

export default defineComponent({
  name: 'SignalementFormLink',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: '' },
    link: { type: String, default: '' },
    linktarget: { type: String, default: '' },
    description: { type: String, default: null },
    customCss: { type: String, default: '' },
    ariaControls: { type: String, default: '' },
    clickEvent: Function
  },
  methods: {
    handleClick () {
      if (this.clickEvent !== undefined) {
        this.clickEvent(this.id)
      }
    }
  },
  data () {
    return {
      variablesReplacer
    }
  }
})
</script>

<style>
.signalement-form-link {
  display: inline-flex;
  text-align: center;
}
</style>
