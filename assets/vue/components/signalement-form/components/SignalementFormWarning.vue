<template>
  <div class="fr-notice fr-notice--info fr-notice--info-warning fr-mb-5v">
    <div class="fr-container">
      <div class="fr-notice__body">
        <p class="fr-notice__title" v-html="labelVariablesReplaced">
        </p>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { variablesReplacer } from './../services/variableReplacer'

export default defineComponent({
  name: 'SignalementFormWarning',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null }
  },
  computed: {
    labelVariablesReplaced (): string {
      if (this.label !== undefined) {
        return variablesReplacer.replace(this.label)
      }
      return ''
    }
  }
})
</script>

<style>
.fr-notice.fr-notice--info.fr-notice--info-warning {
  --idle: transparent;
  --hover: var(--background-contrast-warning-hover);
  --active: var(--background-contrast-warning-active);
  background-color: var(--background-contrast-warning);
  color: var(--text-default-warning);
}
.fr-notice.fr-notice--info.fr-notice--info-warning .fr-notice__body::before {
  -webkit-mask-image: url("../../../../../public/build/dsfr/icons/system/fr--warning-fill.svg");
  mask-image: url("../../../../../public/build/dsfr/icons/system/fr--warning-fill.svg");
}
</style>
