<template>
  <div
    :class="['signalement-form-disorder-category-item fr-container--fluid fr-p-3v', isSelected || isAlreadySelected ? categoryZoneCss : '']"
    @click="handleClick"
    ref="disordercategoryitem"
    >
      <input
      type="checkbox"
      :id="id + '_input'"
      :ref="id + '_ref'"
      :name="id"
      v-model="isSelected"
      @change="handleChange"
      @click.stop
      >
      <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--middle">
        <div class="fr-col-12 fr-col-md-4">
          <img :src="iconSrc" alt="">
        </div>
        <div class="fr-col-12 fr-col-md-8">
          <label :for="id + '_input'" class="fr-label" @click.stop>{{ label }}</label>
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
    clickEvent: Function,
    access_focus: { type: Boolean, default: false },
    validOnEnter: { type: Boolean, default: false }
  },
  data () {
    return {
      isSelected: this.modelValue,
      formStore
    }
  },
  mounted () {
    const element = this.$refs.disordercategoryitem as HTMLElement
    if (this.access_focus && element && !element.classList.contains('fr-hidden')) {
      this.focusInput()
    }
  },
  computed: {
    isAlreadySelected () {
      if (formStore?.data?.categorieDisorders !== undefined) {
        return formStore.data.categorieDisorders.batiment.includes(this.id) || formStore.data.categorieDisorders.logement.includes(this.id)
      }
      return false
    },
    categoryZoneCss () {
      if (this.id.includes('batiment')) {
        return 'is-selected-batiment'
      }
      return 'is-selected-logement'
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
    },
    focusInput () {
      const focusableElement = this.$refs[this.id + '_ref'] as HTMLElement
      if (focusableElement) {
        focusableElement.focus()
      }
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
.signalement-form-disorder-category-item {
  border: 1px solid var(--border-default-grey);
}
.signalement-form-disorder-category-item:has(:focus){
  outline-color: #0a76f6;
  outline-offset: -4px;
  outline-style: solid;
  outline-width: 2px;
}
.signalement-form-disorder-category-item.is-selected-batiment {
  border: 1px solid var(--border-default-orange-terre-battue);
}
.signalement-form-disorder-category-item.is-selected-logement {
  border: 1px solid var(--border-default-blue-france);
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
