<template>
    <section>
        Carte
    </section>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from '../store'

export default defineComponent({
  name: 'AddressesHistoryMap',
  emits: ['change'],
  data () {
    return {
      sharedState: store.state,
      sharedProps: store.props
    }
  },
  computed: {
  },
  methods: {
  }
})
</script>
<style>
</style>
