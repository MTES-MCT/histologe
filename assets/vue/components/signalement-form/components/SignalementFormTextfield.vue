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
import { requests } from './../requests'
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
    searchWhenEdit: { type: Object, default: null }
  },
  data () {
    return {
      idFetchTimeout: 0 as unknown as ReturnType<typeof setTimeout>,
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

      if (this.searchWhenEdit !== undefined && this.searchWhenEdit !== null) {
        let isUpdateAddress = true
        let search = ''
        for (const index of this.searchWhenEdit.values) {
          if (formStore.data[index] === '') {
            isUpdateAddress = false
          } else {
            search += formStore.data[index] + ' '
          }
        }

        if (isUpdateAddress) {
          clearTimeout(this.idFetchTimeout)
          this.idFetchTimeout = setTimeout(() => {
            requests.validateAddress(search, this.handleUpdateInsee)
          }, 200)
        }
      }
    },
    handleUpdateInsee (requestResponse: any) {
      const suggestions = requestResponse.features
      if (suggestions[0] !== undefined) {
        formStore.data[this.searchWhenEdit.result] = suggestions[0].properties.citycode
      }
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
</style>
