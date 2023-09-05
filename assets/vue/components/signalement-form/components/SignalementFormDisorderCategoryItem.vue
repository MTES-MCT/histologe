<template>
  <div
    :class="['signalement-form-disorder-category-item fr-container--fluid fr-p-3v', isSelected || isAlreadySelected ? 'is-selected' : '']"
    @click="handleClick"
    >
      <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--middle">
        <div class="fr-col-12 fr-col-md-4">
          <img :src="iconSrc" alt="">
        </div>
        <div class="fr-col-12 fr-col-md-8">
          <input
            type="checkbox"
            :id="id"
            :name="id"
            v-model="isSelected"
            @change="handleChange"
            @click.stop
            >
          <label :for="id" class="fr-label" @click.stop>{{ label }}</label>
        </div>
      </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from '../store'

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
      isSelected: this.modelValue,
      formStore
    }
  },
  computed: {
    isAlreadySelected () {
      if (formStore?.data?.categorieDisorders !== undefined) {
        return formStore.data.categorieDisorders.batiment.includes(this.id) || formStore.data.categorieDisorders.logement.includes(this.id)
      }
      return false
    }
  },
  methods: {
    handleClick () {
      this.isSelected = !this.isSelected
      this.handleChange()
    },
    handleChange () {
      this.updateValue()
      if (this.clickEvent !== undefined) {
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
  border: 1px solid var(--border-default-grey);
}
.signalement-form-disorder-category-item.is-selected {
  border: 1px solid var(--border-default-orange-terre-battue);
}
.signalement-form-disorder-category-item input[type=checkbox] {
  position: absolute;
  width: 1.5rem;
  height: 1.5rem;
  opacity: 0;
}
.signalement-form-disorder-category-item label::before {
  position: absolute;
  content: "";
  height: 1.5rem;
  width: 1.5rem;
  margin-left: -2rem;
  outline-color: #0a76f6;
  outline-offset: 2px;
  outline-width: 2px;
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
