<template>
  <div class="fr-notice fr-notice--info fr-mb-5v">
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
  name: 'SignalementFormInfo',
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
/* TODO : virer les styles quand bonne version dsfr */
  .fr-notice {
    --title-spacing: 0;
    --text-spacing: 0;
    background-color: #eee;
    color: #161616;
    padding-bottom: 1rem;
    padding-top: 1rem;
    position: relative;
  }
  .fr-notice--info {
    --idle: transparent;
    --hover: var(--background-contrast-info-hover);
    --active: var(--background-contrast-info-active);
    background-color: #e8edff;
    color: #0063cb;
  }
  .fr-notice__body::before {
    --icon-size: 1.5rem;
    background-color: currentColor;
    display: inline-block;
    flex: 0 0 auto;
    height: 1.5rem;
    left: 0;
    -webkit-mask-size: 100% 100%;
    mask-size: 100% 100%;
    position: absolute;
    vertical-align: calc(.375em - .75rem);
    width: 1.5rem;
    mask-image: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCI+PHBhdGggZD0iTTE5LjUgMi41aC0xNWMtMS4xIDAtMiAuOS0yIDJ2MTVjMCAxLjEuOSAyIDIgMmgxNWMxLjEgMCAyLS45IDItMnYtMTVjMC0xLjEtLjktMi0yLTJ6TTEzIDE3aC0ydi02aDJ2NnptMC04aC0yVjdoMnYyeiIvPjwvc3ZnPg==);
  }
  .fr-notice__body {
    padding: 0 2.5rem;
    position: relative;
  }
  .fr-notice__title {
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.5rem;
    position: relative;
  }
</style>
