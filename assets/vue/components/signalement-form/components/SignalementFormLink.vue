<template>
  <div>
    <a
      :id="id"
      :class="[ customCss ]"
      :href="link"
      @click="handleClick"
      >
        {{ label}}
    </a>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'SignalementFormLink',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: '' },
    link: { type: String, default: '' },
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
    handleClick () {
      if (this.clickEvent !== undefined) {
        this.clickEvent(this.id)
      }
    }
  }
})
</script>

<style>
</style>
