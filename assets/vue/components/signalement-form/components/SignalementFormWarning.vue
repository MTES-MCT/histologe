<template>
    <div class="fr-alert fr-alert--warning fr-my-5w">
        <p class="fr-alert__title" v-html="label"></p>
    </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'SignalementFormWarning',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null }
  }
})
</script>

<style>
</style>
