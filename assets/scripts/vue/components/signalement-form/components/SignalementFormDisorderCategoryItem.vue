<template>
  <div
    :class="['fr-checkbox-group fr-radio-rich signalement-form-disorder-category-item', isSelected ? categoryZoneCss : '']"
    @click="handleClick"
    :ref="id"
    >
    <input
      type="checkbox"
      :id="id + '_input'"
      :ref="idRef"
      :name="id"
      v-model="isSelected"
      @change="handleChange"
      @click.stop
      >
    <label :for="id + '_input'" class="fr-label" @click.stop>{{ label }}</label>
    <div class="fr-radio-rich__pictogram">
      <img :src="iconSrc" alt="">
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
    access_focus: { type: Boolean, default: false }
  },
  data () {
    return {
      isSelected: this.modelValue,
      formStore,
      idRef: this.id + '_ref'
    }
  },
  created () {
    this.isSelected = this.modelValue || this.isAlreadySelected
  },
  mounted () {
    const element = this.$refs[this.id] as HTMLElement
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
      const focusableElement = this.$refs[this.idRef] as HTMLElement
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
  cursor: pointer;
}
.signalement-form-disorder-category-item input[type=checkbox],
.signalement-form-disorder-category-item input[type=checkbox] ~ label::before {
  left: 1rem;
  top: calc(50% - 0.75rem);
}
.signalement-form-disorder-category-item input[type=checkbox] ~ label {
  align-self: stretch;
  width: 100%;
  margin-left: 0;
  padding: 0.75rem 1rem 0.75rem 3rem;
  border: 1px solid var(--border-default-grey);
}
.signalement-form-disorder-category-item.is-selected-logement input[type=checkbox] ~ label {
  border-color: var(--border-default-blue-france);
}
.signalement-form-disorder-category-item.is-selected-batiment input[type=checkbox] ~ label {
  border-color: var(--border-default-orange-terre-battue);
}
.signalement-form-disorder-category-item.is-selected-logement .fr-radio-rich__pictogram {
  background-image:
    linear-gradient(0deg, var(--border-default-blue-france), var(--border-default-blue-france)),
    linear-gradient(0deg, var(--border-default-blue-france), var(--border-default-blue-france)),
    linear-gradient(0deg, var(--border-default-blue-france), var(--border-default-blue-france)),
    linear-gradient(0deg, var(--border-default-grey), var(--border-default-grey));
}
.signalement-form-disorder-category-item.is-selected-batiment .fr-radio-rich__pictogram {
  background-image:
    linear-gradient(0deg, var(--border-default-orange-terre-battue), var(--border-default-orange-terre-battue)),
    linear-gradient(0deg, var(--border-default-orange-terre-battue), var(--border-default-orange-terre-battue)),
    linear-gradient(0deg, var(--border-default-orange-terre-battue), var(--border-default-orange-terre-battue)),
    linear-gradient(0deg, var(--border-default-grey), var(--border-default-grey));
}
.signalement-form-disorder-category-item.is-selected-logement input[type=checkbox] ~ label,
.signalement-form-disorder-category-item.is-selected-logement .fr-radio-rich__pictogram {
  background-color: var(--blue-france-850-200);
}
.signalement-form-disorder-category-item.is-selected-logement input[type=checkbox] ~ label,
.signalement-form-disorder-category-item.is-selected-batiment input[type=checkbox] ~ label {
  font-weight: bold;
}
.signalement-form-disorder-category-item.is-selected-batiment input[type=checkbox] ~ label,
.signalement-form-disorder-category-item.is-selected-batiment .fr-radio-rich__pictogram {
  background-color: rgb(228, 121, 74, 0.25);
}
</style>
