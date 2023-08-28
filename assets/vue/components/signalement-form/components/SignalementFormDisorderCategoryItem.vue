<template>
  <div
    :class="['signalement-form-disorder-category-item fr-container--fluid fr-p-3v', isSelected ? 'is-selected' : '']"
    @click="handleClick"
    >
      <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--middle">
        <div class="fr-col-12 fr-col-md-4">
          <img :src="iconSrc" alt="">
        </div>
        <div class="fr-col-12 fr-col-md-8">
          <span>{{ label }}</span>
        </div>
      </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'SignalementFormDisorderCategoryItem',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: '' },
    iconSrc: { type: String, default: '' },
    modelValue: { type: Boolean, default: false },
    clickEvent: Function
  },
  data () {
    return {
      isSelected: this.modelValue
    }
  },
  methods: {
    handleClick () {
      if (this.clickEvent !== undefined) {
        this.isSelected = !this.isSelected
        this.updateValue()
        this.clickEvent(this.id, this.isSelected)
      }
    },
    updateValue () {
      this.$emit('update:modelValue', this.isSelected)
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
.signalement-form-disorder-category-item {
  cursor: pointer;
  border: 1px solid var(--border-default-grey);
}
.signalement-form-disorder-category-item.is-selected {
  border: 1px solid var(--border-default-orange-terre-battue);
}
.signalement-form-disorder-category-item .fr-col-md-8 {
  font-weight: bold;
}
@media (max-width: 48em) {
  .signalement-form-disorder-category-item .fr-col-12 {
    text-align: center;
  }
}
</style>
