<template>
    <div class="fr-alert fr-alert--warning fr-my-5w">
        <p class="fr-alert__title" v-html="labelVariablesReplaced"></p>
    </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { variablesServices } from './../services/variableReplacer'

export default defineComponent({
  name: 'SignalementFormWarning',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null }
  },
  computed: {
    labelVariablesReplaced (): string {
      if (this.label !== undefined) {
        return variablesServices.replaceVariables(this.label)
      }
      return ''
    }
  }
})
</script>

<style>
</style>
