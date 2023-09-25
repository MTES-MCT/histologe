<template>
  <div>
    <div class="fr-input-group">
      <label class='fr-label' :for="id">
        {{ variablesReplacer.replace(label) }}
      </label>
      <div class="fr-grid-row fr-grid-row--gutters">
        <div class="fr-col-12 fr-col-md-4">
          <select
            class="fr-select"
            :id="idCountryCode"
            :name="idCountryCode"
            v-model="formStore.data[idCountryCode]"
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
        <div class="fr-col-12 fr-col-md-8">
          <div class="fr-input-wrap">
            <input
              type="text"
              :id="id"
              :name="id"
              v-model="formStore.data[id]"
              class="fr-input fr-icon-phone-line"
              :aria-describedby="'text-input-error-desc-error-'+id"
              >
          </div>
        </div>
      </div>
      <div
        :id="'text-input-error-desc-error-'+id"
        class="fr-error-text"
        v-if="formStore.validationErrors[id] !== undefined"
        >
        {{ formStore.validationErrors[id] }}
      </div>
    </div>

    <SignalementFormButton
      :id="idShow"
      label="Ajouter un numéro"
      :customCss="formStore.data[idSecond] === '' || formStore.data[idSecond] === undefined ? 'btn-link' : 'btn-link fr-hidden'"
      :action="actionShow"
      :clickEvent="handleClickButton"
      />

    <div
      :id="idSecondGroup"
      :class="[ 'fr-input-group', formStore.data[idSecond] === undefined ? 'fr-hidden' : '' ]"
      >
      <label class='fr-label' :for="idSecond">
        Téléphone secondaire (facultatif)
      </label>
      <div class="fr-grid-row fr-grid-row--gutters">
        <div class="fr-col-12 fr-col-md-4">
          <select
            class="fr-select"
            :id="idCountryCodeSecond"
            :name="idCountryCodeSecond"
            v-model="formStore.data[idCountryCodeSecond]"
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
        <div class="fr-col-12 fr-col-md-8">
          <div class="fr-input-wrap">
            <input
              type="text"
              :id="idSecond"
              :name="idSecond"
              v-model="formStore.data[idSecond]"
              class="fr-input fr-icon-phone-line"
              :aria-describedby="'text-input-error-desc-error-'+idSecond"
              >
          </div>
        </div>
      </div>
      <div
        :id="'text-input-error-desc-error-'+idSecond"
        class="fr-error-text"
        v-if="formStore.validationErrors[idSecond] !== undefined"
        >
        {{ formStore.validationErrors[idSecond] }}
      </div>
    </div>
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
    customCss: { type: String, default: '' },
    validate: { type: Object, default: null },
    hasError: { type: Boolean, default: false },
    error: { type: String, default: '' },
    clickEvent: Function
  },
  data () {
    if (formStore.data[this.id + '_countrycode'] === '' || formStore.data[this.id + '_countrycode'] === undefined) {
      formStore.data[this.id + '_countrycode'] = '33'
    }
    if (formStore.data[this.id + '_secondaire_countrycode'] === '' || formStore.data[this.id + '_secondaire_countrycode'] === undefined) {
      formStore.data[this.id + '_secondaire_countrycode'] = '33'
    }
    return {
      variablesReplacer,
      idCountryCode: this.id + '_countrycode',
      idSecondGroup: this.id + '_secondaire_group',
      idSecond: this.id + '_secondaire',
      idCountryCodeSecond: this.id + '_secondaire_countrycode',
      idShow: this.id + '_ajouter_numero',
      actionShow: 'show:' + this.id + '_secondaire_group',
      formStore
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
        countryList.push({ code: getCountryCallingCode(countryCode), label: this.getSelectOptionLabel(countryCode) })
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
      countryList.unshift({ code: getCountryCallingCode('FR'), label: this.getSelectOptionLabel('FR') })

      return countryList
    }
  }
})
</script>

<style>
.fr-address-suggestion:hover {
    background-color: #417dc4;
    color: white !important;
}
.fr-address-group {
  margin-top: -1.5rem;
}
</style>
