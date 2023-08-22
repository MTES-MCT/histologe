<template>
    <div class="signalement-form-button" :id="id">
      <button
        :type="type"
        :class="[ 'fr-btn', customCss, formStore.lastButtonClicked === id ? 'fr-btn--loading' : '' ]"
        :disabled="formStore.lastButtonClicked !== ''"
        @click="handleClick"
        >
        {{ label}}
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
    clickEvent: Function
  },
  data () {
    return {
      formStore
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
    handleClick () {
      if (this.clickEvent !== undefined) {
        this.clickEvent(this.actionType, this.actionParam, this.id)
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
</style>
