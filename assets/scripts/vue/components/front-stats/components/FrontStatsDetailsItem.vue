<template>
  <div class="front-stats-details-item fr-mb-5w">
    <div>
      <p>{{ value }}</p>
      <p><slot name="title"></slot></p>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  value: string
}>()
</script>

<style>
  .front-stats-details-item:first-child {
    margin-top: 10px;
  }
  .front-stats-details-item div {
    text-align: center;
    font-weight: bold;
    background: #FFF;
    padding: 2rem 0.5rem;
    box-shadow: 0px 3px 12px #16161633;
    border-radius: 4px;
  }
  .front-stats-details-item div p:first-child {
    margin-bottom: 0.5rem;
    font-size: 2rem;
    color: var(--blue-france-sun-113-625);
  }
  .front-stats-details-item div p:last-child {
    margin-bottom: 0px;
    font-size: 0.8rem;
    color: var(--grey-200-850);
  }
</style>
