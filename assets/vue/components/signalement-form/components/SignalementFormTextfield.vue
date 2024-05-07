<template>
<div :class="['fr-input-group', { 'fr-input-group--disabled': disabled }]" :id="id">
  <label class='fr-label' :for="id + '_input'">
    {{ variablesReplacer.replace(label) }}
    <span class="fr-hint-text">{{ description }}</span>
  </label>
    <div :class="[ customCss, 'fr-input-wrap' ]">
      <input
        type="text"
        :id="id + '_input'"
        :name="id"
        :value="internalValue"
        :placeholder="placeholder"
        :class="[ customCss, 'fr-input' ]"
        @input="updateValue($event)"
        aria-describedby="text-input-error-desc-error"
        :disabled="disabled"
        :maxlength="validate?.maxLength"
        >
    </div>
    <div
      id="text-input-error-desc-error"
      class="fr-error-text"
      v-if="hasError"
      >
      {{ error }}
    </div>
</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'
import { variablesReplacer } from './../services/variableReplacer'

export default defineComponent({
  name: 'SignalementFormTextfield',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    description: { type: String, default: null },
    placeholder: { type: String, default: null },
    modelValue: { type: String, default: null },
    customCss: { type: String, default: '' },
    validate: { type: Object, default: null },
    hasError: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: '' },
    tagWhenEdit: { type: String, default: '' }
  },
  data () {
    return {
      variablesReplacer
    }
  },
  computed: {
    internalValue: {
      get () {
        return this.modelValue
      },
      set (newValue: string) {
        this.$emit('update:modelValue', newValue)
      }
    }
  },
  methods: {
    updateValue (event: Event) {
      const value = (event.target as HTMLInputElement).value
      this.$emit('update:modelValue', value)
      if (this.tagWhenEdit !== '') {
        formStore.data[this.tagWhenEdit] = 1
      }
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
</style>
