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
    linktarget: { type: String, default: undefined },
    description: { type: String, default: null },
    customCss: { type: String, default: '' },
    ariaControls: { type: String, default: undefined },
    clickEvent: Function,
    // les propriétés suivantes ne sont pas utilisées,
    // mais si on ne les met pas, elles apparaissent dans le DOM
    // et ça soulève des erreurs W3C
    hasError: { type: Boolean, default: undefined },
    access_name: { type: String, default: undefined },
    access_autocomplete: { type: String, default: undefined },
    access_focus: { type: Boolean, default: false }
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
