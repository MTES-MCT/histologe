<template>
  <div class="fr-input-group" :id="id" :ref="id">
    <label :class="[ customCss, 'fr-label' ]" :for="id + '_input'">{{ label }}</label>
    <span v-if="hint !== ''" class="fr-hint-text">{{ hint }}</span>
    <input
      type="date"
      :id="id + '_input'"
      :ref="idRef"
      :name="id"
      :value="internalValue"
      :class="[ customCss, 'fr-input' ]"
      @input="updateValue($event)"
      :aria-describedby="hasError ? id + '-text-input-error-desc-error' : undefined"
      >
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
  name: 'SignalementFormDate',
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    hint: { type: String, default: null },
    modelValue: { type: String, default: null },
    customCss: { type: String, default: '' },
    hasError: { type: Boolean, default: false },
    error: { type: String, default: '' },
    access_focus: { type: Boolean, default: false },
    // les propriétés suivantes ne sont pas utilisées,
    // mais si on ne les met pas, elles apparaissent dans le DOM
    // et ça soulève des erreurs W3C
    validate: { type: Object, default: null },
    handleClickComponent: Function,
    clickEvent: Function,
    access_name: { type: String, default: undefined },
    access_autocomplete: { type: String, default: undefined }
  },
  data () {
    return {
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
