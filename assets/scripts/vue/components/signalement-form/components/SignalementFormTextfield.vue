<template>
<div :class="['fr-input-group', { 'fr-input-group--disabled': disabled }, {'fr-input-group--error' : hasError}]" :id="id" :ref="id">
  <label class='fr-label' :for="id + '_input'">
    {{ variablesReplacer.replace(label) }}
    <span class="fr-hint-text">{{ description }}</span>
  </label>
    <div :class="[ customCss, 'fr-input-wrap' ]">
      <input
        type="text"
        :id="id + '_input'"
        :ref="idRef"
        :name="access_name"
        :autocomplete="access_autocomplete"
        :value="internalValue"
        :placeholder="placeholder"
        :class="[ customCss, 'fr-input', {'fr-input--error' : hasError} ]"
        @input="updateValue($event)"
        :aria-describedby="hasError ? id + '-text-input-error-desc-error' : undefined"
        :disabled="disabled"
        :maxlength="validate?.maxLength"
        >
    </div>
    <div
      :id="id + '-text-input-error-desc-error'"
      class="fr-error-text"
      role="alert"
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
    tagWhenEdit: { type: String, default: '' },
    access_name: { type: String, default: '' },
    access_autocomplete: { type: String, default: '' },
    access_focus: { type: Boolean, default: false },
    // les propriétés suivantes ne sont pas utilisées,
    // mais si on ne les met pas, elles apparaissent dans le DOM
    // et ça soulève des erreurs W3C
    clickEvent: Function,
    handleClickComponent: Function
  },
  data () {
    return {
      idFetchTimeout: 0 as unknown as ReturnType<typeof setTimeout>,
      variablesReplacer,
      idRef: this.id + '_ref'
    }
  },
  mounted () {
    const element = this.$refs[this.id] as HTMLElement
    if (this.access_focus && element && !element.classList.contains('fr-hidden')) {
      this.focusInput()
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
    },
    focusInput () {
      const focusableElement = (this.$refs[this.idRef]) as HTMLElement
      if (focusableElement) {
        focusableElement.focus()
      }
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
</style>
