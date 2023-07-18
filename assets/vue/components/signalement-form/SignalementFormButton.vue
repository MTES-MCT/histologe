<template>
    <div>
        <button
        :type="type"
        :id="id"
        :class="[ 'fr-btn', customCss ]"
        @click="onClickLocalEvent"
        >
            {{ label}}
        </button>
    </div>
</template>

<script lang="ts">
import { defineComponent, PropType } from 'vue'

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
    onClickLocalEvent () {
      if (this.clickEvent !== undefined) {
        this.clickEvent(this.actionType, this.actionParam)
      }
    }
  }
})
</script>

<style>
</style>
