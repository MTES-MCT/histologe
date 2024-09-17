<template>
  <div class="signalement-form-link" ref="link">
    <a
      :id="id"
      :ref="id + '_ref'"
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
    access_focus: { type: Boolean, default: false },
    // les propriétés suivantes ne sont pas utilisées,
    // mais si on ne les met pas, elles apparaissent dans le DOM
    // et ça soulève des erreurs W3C
    hasError: { type: Boolean, default: undefined },
    access_name: { type: String, default: undefined },
    access_autocomplete: { type: String, default: undefined }
  },
  data () {
    return {
      variablesReplacer
    }
  },
  mounted () {
    const element = this.$refs.link as HTMLElement
    if (this.access_focus && element && !element.classList.contains('fr-hidden')) {
      this.focusInput()
    }
  },
  methods: {
    handleClick () {
      if (this.clickEvent !== undefined) {
        this.clickEvent(this.id)
      }
    },
    focusInput () {
      const focusableElement = (this.$refs[this.id + '_ref']) as HTMLElement
      if (focusableElement) {
        focusableElement.focus()
      }
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
