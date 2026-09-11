<template>
  <div class="app-toggle-checkboxes">
    <!-- Partie haute : HistoToggle -->
    <div class="toggle-wrapper">
      <HistoToggle
        :id="`${id}-toggle`"
        v-model="toggleValue"
        @update:model-value="onToggleChange"
        containerClass=""
      >
        <template #label>
          <span v-if="labelPicto" :class="`${labelPicto}`" class="toggle-picto" aria-hidden="true"></span>
          <slot name="label"></slot>
        </template>
      </HistoToggle>

      <span
        :class="['fr-icon-arrow-down-s-line', 'toggle-arrow', { 'toggle-arrow--open': isOpen }]"
        aria-hidden="true"
        @click.stop="toggleOpen"
      ></span>
    </div>

    <!-- Partie basse : Liste des HistoCheckbox -->
    <div v-if="isOpen" class="checkboxes-container">
      <template v-for="(group, groupIndex) in optionGroups" :key="groupIndex">
        <HistoCheckbox
          v-for="option in group.options"
          :key="option.Id"
          :id="`${id}-${option.Id}`"
          :model-value="selectedValues.includes(option.Id)"
          @update:model-value="(checked) => onCheckboxUpdate(option.Id, checked)"
          containerClass="fr-mb-3v"
        >
          <template #label>{{ option.Text }}</template>
        </HistoCheckbox>
      </template>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import type { CheckboxGroup } from './AppListCheckboxes.types'
import HistoToggle from './HistoToggle.vue'
import HistoCheckbox from './HistoCheckbox.vue'

export default defineComponent({
  name: 'AppToggleCheckboxes',
  components: {
    HistoToggle,
    HistoCheckbox
  },
  emits: ['update:modelValue'],
  props: {
    id: { type: String, required: true },
    modelValue: {
      type: Array as PropType<string[]>,
      default: () => []
    },
    optionGroups: {
      type: Array as PropType<CheckboxGroup[]>,
      required: true
    },
    labelPicto: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      isOpen: false,
      toggleValue: false,
      selectedValues: [...this.modelValue] as string[]
    }
  },
  computed: {
    allOptionIds(): string[] {
      return this.optionGroups.flatMap(group => group.options.map(opt => opt.Id))
    }
  },
  watch: {
    modelValue(newValue: string[]) {
      this.selectedValues = [...newValue]
      this.updateToggleValue()
    }
  },
  mounted() {
    this.updateToggleValue()
  },
  methods: {
    toggleOpen() {
      this.isOpen = !this.isOpen
    },
    onToggleChange(checked: boolean) {
      // Ne modifier que les IDs de ce groupe dans le tableau partagé
      const otherValues = this.selectedValues.filter(id => !this.allOptionIds.includes(id))

      if (checked) {
        // Ajouter tous les IDs de ce groupe
        this.selectedValues = [...otherValues, ...this.allOptionIds]
      } else {
        // Retirer tous les IDs de ce groupe
        this.selectedValues = otherValues
      }
      this.$emit('update:modelValue', this.selectedValues)
    },
    onCheckboxUpdate(optionId: string, checked: boolean) {
      if (checked) {
        if (!this.selectedValues.includes(optionId)) {
          this.selectedValues.push(optionId)
        }
      } else {
        this.selectedValues = this.selectedValues.filter(id => id !== optionId)
      }
      this.$emit('update:modelValue', this.selectedValues)
      this.updateToggleValue()
    },
    updateToggleValue() {
      // Le toggle est activé seulement si toutes les options de CE groupe sont cochées
      this.toggleValue = this.allOptionIds.length > 0 &&
                         this.allOptionIds.every(id => this.selectedValues.includes(id))
    }
  }
})
</script>

<style scoped>
.app-toggle-checkboxes {
  margin-bottom: 1rem;
}

.toggle-wrapper {
  display: flex;
  align-items: center;
}

.toggle-wrapper :deep(.histo-toggle) {
  flex: 1;
  margin-bottom: 0;
}

.toggle-arrow {
  position: absolute;
  right: 0;
  flex-shrink: 0;
  transition: transform 0.3s;
  cursor: pointer;
  pointer-events: auto;
}

.toggle-arrow--open {
  transform: rotate(180deg);
}

.checkboxes-container {
  padding: 1rem 1rem 0.5rem 2rem;
}

.toggle-picto {
  display: inline-block;
  width: 15px;
  height: 15px;
  margin-top: 4px;
  margin-right: 8px;
  vertical-align: middle;
}

.toggle-picto.purple-hexagon {
  background-color: #6c63ff;
  clip-path: polygon(30% 0%, 70% 0%, 100% 50%, 70% 100%, 30% 100%, 0% 50%);
  transform: rotate(90deg);
}

.toggle-picto.blue-square {
  background-color: #2196f3;
}

.toggle-picto.purple-diamond {
  background-color: #9c27b0;
  transform: rotate(45deg);
}
</style>
