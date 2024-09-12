<template>
  <div :class="['fr-input-group', { 'fr-input-group--disabled': disabled }]" :id="id">
    <label class='fr-label' :for="id + '_input'">
      {{ label }}
      <span class="fr-hint-text">{{ description }}</span>
    </label>
    <div :class="[ customCss, 'fr-input-wrap' ]">
      <input
        type="email"
        :id="id + '_input'"
        :name="access_name"
        :autocomplete="access_autocomplete"
        :value="internalValue"
        :class="[ customCss, 'fr-input' ]"
        @input="updateValue($event)"
        :aria-describedby="hasError ? id + '-text-input-error-desc-error' : undefined"
        :disabled="disabled"
      >
    </div>
    <div
      :id="id + '-text-input-error-desc-error'"
      class="fr-error-text"
      v-if="hasError"
    >
      {{ error }}
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'SignalementFormEmailfield',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    description: { type: String, default: null },
    modelValue: { type: String, default: null },
    customCss: { type: String, default: '' },
    validate: { type: Object, default: null },
    hasError: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: '' },
    access_name: { type: String, default: '' },
    access_autocomplete: { type: String, default: '' },
    access_focus: { type: Boolean, default: false },
    // les propriétés suivantes ne sont pas utilisées,
    // mais si on ne les met pas, elles apparaissent dans le DOM
    // et ça soulève des erreurs W3C
    clickEvent: Function,
    handleClickComponent: Function
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
    }
  },
  emits: ['update:modelValue']
})
</script>
