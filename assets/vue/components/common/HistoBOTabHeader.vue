<template>
  <section class="histo-bo-tab-header fr-grid-row fr-grid-row--middle fr-mb-1w">
    <div class="fr-col-6">
      <h1 class="fr-h2 fr-mb-0"><slot name="title"></slot></h1>
    </div>
    <div class="fr-col-6 fr-text--right">
      <slot name="header-buttons"></slot>
    </div>
  </section>
</template>

<script>
export default {
  name: 'HistoBOTabHeader'
}
</script>
