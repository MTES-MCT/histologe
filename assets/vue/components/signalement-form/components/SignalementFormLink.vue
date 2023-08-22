<template>
  <div class="signalement-form-link">
    <a
      :id="id"
      :class="[ customCss ]"
      :href="linkVariablesReplaced"
      :target="linktarget"
      @click="handleClick"
      >
        {{ label}}
    </a>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { services } from './../services'

export default defineComponent({
  name: 'SignalementFormLink',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: '' },
    link: { type: String, default: '' },
    linktarget: { type: String, default: '' },
    customCss: { type: String, default: '' },
    clickEvent: Function
  },
  computed: {
    linkVariablesReplaced (): string {
      if (this.link !== undefined) {
        return services.replaceVariables(this.link)
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
.signalement-form-link {
  display: inline-flex;
  text-align: center;
}
</style>
