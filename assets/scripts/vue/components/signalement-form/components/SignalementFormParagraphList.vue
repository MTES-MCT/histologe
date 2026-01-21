<template>
  <ul :class="customCss">
    <li v-for="(item, index) in values" :key="index" v-html="variablesReplacer.replace(item)"></li>
  </ul>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { variablesReplacer } from '../services/variableReplacer'

export default defineComponent({
  name: 'SignalementFormParagraphList',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    customCss: { type: String, default: '' },
    values: { type: Array as () => Array<string>, default: null },
    // les propriétés suivantes ne sont pas utilisées,
    // mais si on ne les met pas, elles apparaissent dans le DOM
    // et ça soulève des erreurs W3C
    hasError: { type: Boolean, default: false },
    handleClickComponent: Function,
    clickEvent: Function,
    access_name: { type: String, default: undefined },
    access_autocomplete: { type: String, default: undefined },
    access_focus: { type: Boolean, default: false }
  },
  data () {
    return {
      variablesReplacer
    }
  }
})
</script>

<style>
</style>
