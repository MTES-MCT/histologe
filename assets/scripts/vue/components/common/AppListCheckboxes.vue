<template>
  <div class="app-list-checkboxes" ref="container">
    <div class="fr-input-group">
      <label class="fr-label" :for="id">
        <slot name="label"></slot>
      </label>
      <div class="fr-select-group">
        <div :class="['fr-input-wrap', iconClass, 'app-list-checkboxes-wrapper', 'fr-mt-2v']">
          <input
            :id="id"
            :name="id"
            class="fr-input"
            type="text"
            :value="displayText"
            @click="toggleDropdown"
            :placeholder="placeholder"
          />
          <span class="fr-icon-arrow-down-s-line app-list-checkboxes-icon" aria-hidden="true"></span>
        </div>
      </div>
      <div
        v-if="isOpen"
        class="fr-grid-row fr-background-alt--blue-france fr-checkboxes-list"
      >
        <div class="fr-col-12 fr-p-3v">
          <template v-for="(group, groupIndex) in optionGroups" :key="groupIndex">
            <div v-if="group.title" class="fr-text--bold fr-mb-1v">{{ group.title }}</div>
            <div
              v-for="option in group.options"
              :key="option.Id"
              class="fr-checkbox-group fr-mb-1v"
            >
              <input
                type="checkbox"
                :id="`${id}-${option.Id}`"
                :value="option.Id"
                v-model="selectedValues"
                @change="onCheckboxChange"
              />
              <label class="fr-label" :for="`${id}-${option.Id}`">
                {{ option.Text }}
              </label>
            </div>
            <hr v-if="groupIndex < optionGroups.length - 1" class="fr-my-2v" />
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import type { CheckboxOption, CheckboxGroup } from './AppListCheckboxes.types'

export default defineComponent({
  name: 'AppListCheckboxes',
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
    placeholder: {
      type: String,
      default: 'Sélectionner des options'
    },
    iconClass: {
      type: String,
      default: ''
    },
    reset: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      isOpen: false,
      selectedValues: [...this.modelValue] as string[]
    }
  },
  computed: {
    displayText(): string {
      const count = this.selectedValues.length
      if (count === 0) {
        return ''
      }
      return count === 1 ? '1 option sélectionnée' : `${count} options sélectionnées`
    }
  },
  watch: {
    reset() {
      this.resetData()
    },
    modelValue(newValue: string[]) {
      this.selectedValues = [...newValue]
    }
  },
  mounted() {
    document.addEventListener('click', this.handleClickOutside)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside)
  },
  methods: {
    toggleDropdown() {
      this.isOpen = !this.isOpen
    },
    onCheckboxChange() {
      this.$emit('update:modelValue', this.selectedValues)
    },
    handleClickOutside(event: MouseEvent) {
      const container = this.$refs.container as HTMLElement
      if (container && !container.contains(event.target as Node)) {
        this.isOpen = false
      }
    },
    resetData() {
      this.selectedValues = []
      this.isOpen = false
      this.$emit('update:modelValue', this.selectedValues)
    }
  }
})
</script>

<style scoped>
.app-list-checkboxes {
  position: relative;
  width: 100%;
}

.app-list-checkboxes-wrapper {
  position: relative;
}

.app-list-checkboxes-wrapper .fr-input {
  cursor: pointer;
  padding-right: 3rem;
}

.app-list-checkboxes-icon {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  font-size: 1rem;
}

.fr-checkboxes-list {
  position: absolute;
  width: 100%;
  z-index: 1;
  max-height: 400px;
  overflow-y: auto;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.fr-checkbox-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.fr-checkbox-group input[type="checkbox"] {
  margin: 0;
}

.fr-checkbox-group label {
  margin: 0;
  cursor: pointer;
}
</style>
