<template>
  <div>
    <fieldset :class="['fr-fieldset', hasErrorFirst ? 'fr-fieldset--error' : '']" :aria-labelledby="id + '-legend'">
      <legend class="fr-fieldset__legend--regular fr-fieldset__legend fr-col-12" :id="id + '-legend'">
        {{ variablesReplacer.replace(label) }}
        <span class="fr-hint-text">
          {{ description }}
        </span>
      </legend>
      <div class="fr-fieldset__element fr-col-12 fr-col-md-4">
        <select
          :class="['fr-select', hasErrorFirst ? 'fr-select--error' : '']"
          :id="idCountryCode"
          :name="idCountryCode"
          v-model="formStore.data[idCountryCode]"
          title="Indicatif national"
          :aria-describedby="hasErrorFirst ? id + '-text-input-error-desc-error' : undefined"
          >
          <option
            v-for="countryItem in countryList"
            v-bind:key="countryItem.code"
            :value="countryItem.code"
            >
            {{ countryItem.label }}
          </option>
        </select>
      </div>
      <div class="fr-fieldset__element fr-col-12 fr-col-md-8">
        <div class="fr-input-wrap fr-icon-phone-line">
          <input
            type="text"
            :id="id"
            :ref="idRef"
            :name="access_name"
            :autocomplete="access_autocomplete"
            v-model="formStore.data[id]"
            :class="[ 'fr-input', hasErrorFirst ? 'fr-input--error' : '' ]"
            :aria-describedby="hasErrorFirst ? id + '-text-input-error-desc-error' : undefined"
            >
        </div>
      </div>
      <div
        :id="id + '-text-input-error-desc-error'"
        class="fr-error-text fr-mt-0 fr-mb-3v fr-ml-2v"
        v-if="hasErrorFirst"
        >
        {{ formStore.validationErrors[id] }}
      </div>
    </fieldset>

    <SignalementFormButton
      :id="idShow"
      label="Ajouter un numéro"
      :customCss="isHiddenSecond ? 'btn-link fr-btn--icon-left fr-icon-add-line' : 'btn-link fr-btn--icon-left fr-icon-add-line fr-hidden'"
      :aria-hidden="isHiddenSecond ? undefined : true"
      :hidden="isHiddenSecond ? undefined : true"
      :action="actionShow"
      :clickEvent="handleClickButton"
      />

    <fieldset
      :id=idSecondGroup
      :class="[ 'fr-fieldset', isHiddenSecond && !hasErrorSecond ? 'fr-hidden' : '', hasErrorSecond ? 'fr-fieldset--error' : '']"
      :aria-hidden="isHiddenSecond && !hasErrorSecond ? true : undefined"
      :hidden="isHiddenSecond && !hasErrorSecond ? true : undefined"
      :aria-labelledby="id + '-legend-second'"
      >
      <legend class="fr-fieldset__legend--regular fr-fieldset__legend fr-col-12" :id="id + '-legend-second'">
        Téléphone secondaire (facultatif)
        <span class="fr-hint-text">
          {{ description }}
        </span>
      </legend>
      <div class="fr-fieldset__element fr-col-12 fr-col-md-4">
        <select
          :class="['fr-select', hasErrorSecond ? 'fr-select--error' : '']"
          :id="idCountryCodeSecond"
          :name="idCountryCodeSecond"
          v-model="formStore.data[idCountryCodeSecond]"
          title="Indicatif national"
          :aria-describedby="hasErrorSecond ? idSecond + '-text-input-error-desc-error' : undefined"
          >
          <option
            v-for="countryItem in countryList"
            v-bind:key="countryItem.code"
            :value="countryItem.code"
            >
            {{ countryItem.label }}
          </option>
        </select>
      </div>
      <div class="fr-fieldset__element fr-col-12 fr-col-md-8">
        <div class="fr-input-wrap fr-icon-phone-line">
          <input
            type="text"
            :id="idSecond"
            :name="idSecond"
            v-model="formStore.data[idSecond]"
            :class="[ 'fr-input', hasErrorSecond ? 'fr-input--error' : '' ]"
            :aria-describedby="hasErrorSecond ? idSecond + '-text-input-error-desc-error' : undefined"
            :autocomplete="access_autocomplete"
            >
        </div>
      </div>
      <div
        :id="idSecond + '-text-input-error-desc-error'"
        class="fr-error-text fr-mt-0 fr-mb-3v fr-ml-2v"
        v-if="hasErrorSecond"
        >
        {{ formStore.validationErrors[idSecond] }}
      </div>
    </fieldset>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { CountryCode, getCountries, getCountryCallingCode } from 'libphonenumber-js'
import { variablesReplacer } from './../services/variableReplacer'
import { CountryPhoneItem } from './../interfaces/interfaceCountryPhoneItem'
import { getCountryNameByCode } from './../countries'
import formStore from './../store'
import SignalementFormButton from './SignalementFormButton.vue'

export default defineComponent({
  name: 'SignalementFormPhonefield',
  components: {
    SignalementFormButton
  },
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    description: { type: String, default: null },
    customCss: { type: String, default: '' },
    validate: { type: Object, default: null },
    hasError: { type: Boolean, default: false },
    error: { type: String, default: '' },
    access_name: { type: String, default: '' },
    access_autocomplete: { type: String, default: '' },
    access_focus: { type: Boolean, default: false },
    clickEvent: Function,
    // les propriétés suivantes ne sont pas utilisées,
    // mais si on ne les met pas, elles apparaissent dans le DOM
    // et ça soulève des erreurs W3C
    modelValue: { type: String, default: null },
    handleClickComponent: Function
  },
  data () {
    if (formStore.data[this.id + '_countrycode'] === '' || formStore.data[this.id + '_countrycode'] === undefined) {
      formStore.data[this.id + '_countrycode'] = 'FR:33'
    }
    if (formStore.data[this.id + '_secondaire_countrycode'] === '' || formStore.data[this.id + '_secondaire_countrycode'] === undefined) {
      formStore.data[this.id + '_secondaire_countrycode'] = 'FR:33'
    }
    return {
      variablesReplacer,
      idCountryCode: this.id + '_countrycode',
      idSecondGroup: this.id + '_secondaire_group',
      idSecond: this.id + '_secondaire',
      idCountryCodeSecond: this.id + '_secondaire_countrycode',
      idShow: this.id + '_ajouter_numero',
      actionShow: 'show:' + this.id + '_secondaire_group',
      formStore,
      idRef: this.id + '_ref'
    }
  },
  methods: {
    handleClickButton (type:string, param:string, slugButton:string) {
      if (this.clickEvent !== undefined) {
        this.clickEvent(type, param, slugButton)
      }
    },
    getSelectOptionLabel (countryCode:CountryCode) {
      return getCountryNameByCode(countryCode) + ' : +' + getCountryCallingCode(countryCode)
    }
  },
  computed: {
    countryList () {
      const countryList:Array<CountryPhoneItem> = []
      const countryCodes = getCountries()
      for (const countryCode of countryCodes) {
        countryList.push({ code: countryCode + ':' + getCountryCallingCode(countryCode), label: this.getSelectOptionLabel(countryCode) })
      }
      countryList.sort(
        (a, b) => {
          if (a.label < b.label) {
            return -1
          }
          if (a.label > b.label) {
            return 1
          }
          return 0
        }
      )

      // Add France at the top of the list (France is in 2 options)
      countryList.unshift({ code: 'FR' + ':' + getCountryCallingCode('FR'), label: this.getSelectOptionLabel('FR') })

      return countryList
    },
    hasErrorFirst () {
      return formStore.validationErrors[this.id] !== undefined
    },
    hasErrorSecond () {
      return formStore.validationErrors[this.idSecond] !== undefined
    },
    isHiddenSecond () {
      return formStore.data[this.idSecond] === '' || formStore.data[this.idSecond] === undefined
    }
  }
})
</script>
