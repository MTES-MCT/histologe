<template>
    <div class="signalement-form-button" :id="id" :ref="id">
      <button
        :id="id + '_button'"
        :ref="idRef"
        :type="type"
        :class="[ 'fr-btn', customCss, formStore.lastButtonClicked === id ? 'fr-btn--loading fr-btn--icon-right fr-icon-refresh-line' : '' ]"
        :disabled="formStore.lastButtonClicked !== ''"
        @click="handleClick"
        :aria-controls="ariaControls"
        data-fr-opened="false"
        >
        {{ label }}
      </button>
    </div>
</template>

<script lang="ts">
import { defineComponent, PropType } from 'vue'
import formStore from './../store'

export default defineComponent({
  name: 'SignalementFormButton',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: '' },
    action: {
      type: String,
      default: '',
      validator: (value: string) => {
        if ((value && value.includes(':')) || value === 'cancel') {
          // const [actionType, actionParam] = value.split(':')
          // Utilisez actionType et actionParam pour des vérifications supplémentaires si nécessaire
          return true
        }
        return false
      }
    },
    type: { type: String as PropType<'button' | 'submit' | 'reset' | undefined>, default: 'button' },
    customCss: { type: String, default: '' },
    ariaControls: { type: String, default: undefined },
    clickEvent: Function,
    access_focus: { type: Boolean, default: false },
    // les propriétés suivantes ne sont pas utilisées,
    // mais si on ne les met pas, elles apparaissent dans le DOM
    // et ça soulève des erreurs W3C
    handleClickComponent: Function,
    hasError: { type: Boolean, default: undefined },
    access_name: { type: String, default: undefined },
    access_autocomplete: { type: String, default: undefined }
  },
  data () {
    return {
      formStore,
      idRef: this.id + '_ref'
    }
  },
  mounted () {
    const element = this.$refs[this.id] as HTMLElement
    if (element && !element.classList.contains('fr-hidden')) {
      if (this.access_focus) {
        this.focusInput()
      }
    }
  },
  computed: {
    actionType (): string {
      if (this.action.includes(':')) {
        return this.action.split(':')[0]
      }
      return ''
    },
    actionParam (): string {
      if (this.action.includes(':')) {
        return this.action.split(':')[1]
      }
      return ''
    }
  },
  methods: {
    handleClick (event: any) {
      if (this.clickEvent !== undefined) {
        event.preventDefault()
        this.clickEvent(this.actionType, this.actionParam, this.id)
      }
    },
    focusInput () {
      const focusableElement = this.$refs[this.idRef] as HTMLElement
      if (focusableElement) {
        focusableElement.focus()
      }
    }
  }
})
</script>

<style>
.fr-btn.fr-btn--loading {
  opacity: 0.5;
}
.fr-btn.fr-btn--loading:disabled {
  background-color: var(--background-action-high-blue-france);
  color: var(--text-inverted-blue-france);
}
.fr-btn.fr-btn--secondary.fr-btn--loading:disabled {
  background-color: transparent;
  --hover: inherit;
  --active: inherit;
  color: var(--text-action-high-blue-france);
  box-shadow: inset 0 0 0 1px var(--border-action-high-blue-france);
}
.fr-btn.btn-link {
  background-color: white;
  color: var(--blue-france-sun-113-625);
  text-decoration: underline;
}
.fr-btn.btn-link:not(:disabled):hover {
  background-color: white;
  color: var(--blue-france-sun-113-625-hover);
}
.fr-btn.btn-link:not(:disabled):active {
  background-color: white;
  color: var(--blue-france-sun-113-625-active);
}
</style>
