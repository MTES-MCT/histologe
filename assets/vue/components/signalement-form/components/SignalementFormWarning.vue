<template>
  <div class="fr-notice fr-notice--info fr-notice--info-warning fr-mb-5v">
    <div class="fr-container">
      <div class="fr-notice__body">
        <p class="fr-notice__title" v-html="variablesReplacer.replace(label)">
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
  data () {
    return {
      variablesReplacer
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
  -webkit-mask-image: url("../../../img/fr--warning-fill.svg");
  mask-image: url("../../../img/fr--warning-fill.svg");
}
</style>
