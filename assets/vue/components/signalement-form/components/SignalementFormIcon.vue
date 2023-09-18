<template>
  <div
    v-for="(icon, index) in icons"
    v-bind:key="id + '-' + index"
    >
    <div
      v-if="icon"
      class="icon"
      >
      <img :src="icon.src" :alt="icon.alt">
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'SignalementFormIcon',
  props: {
    id: { type: String, default: null },
    icons: { type: Object }
  }
})
</script>
