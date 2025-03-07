<template>
    <component
      v-for="component in componentList"
      :is="component.type"
      v-bind:key="component.slug"
      :id="component.slug"
      :label="component.label"
      :labelInfo="component.labelInfo"
      :labelUpload="component.labelUpload"
      :description="component.description"
      :placeholder="component.placeholder"
      :components="component.components"
      :icons="component.icons"
      :action="component.action"
      :link="component.link"
      :linktarget="component.linktarget"
      :values="component.values"
      :defaultValue="component.defaultValue"
      :customCss="component.customCss"
      :validate="component.validate"
      :disabled="component.disabled"
      :multiple="component.multiple"
      :ariaControls="component.ariaControls"
      :tagWhenEdit="component.tagWhenEdit"
      v-model="formStore.data[component.slug]"
      :hasError="formStore.validationErrors[component.slug] !== undefined"
      :error="formStore.validationErrors[component.slug]"
      :class="{ 'fr-hidden': !formStore.shouldShowField(component) }"
      :aria-hidden="!formStore.shouldShowField(component) ? true : undefined"
      :hidden="!formStore.shouldShowField(component) ? true : undefined"
      :autocomplete="component.autocomplete"
      :clickEvent="handleClickComponent"
      :handleClickComponent="handleClickComponent"
      :access_name="component.accessibility?.name ?? component.slug"
      :access_autocomplete="component.accessibility?.autocomplete ?? 'off'"
      :access_focus="component.accessibility?.focus ?? false"
      />
</template>
<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'
import { Component } from '../interfaces/interfaceComponent'
import SignalementFormTextfield from './SignalementFormTextfield.vue'
import SignalementFormTextarea from './SignalementFormTextarea.vue'
import SignalementFormButton from './SignalementFormButton.vue'
import SignalementFormLink from './SignalementFormLink.vue'
import SignalementFormOnlyChoice from './SignalementFormOnlyChoice.vue'
import SignalementFormSelect from './SignalementFormSelect.vue'
import SignalementFormDate from './SignalementFormDate.vue'
import SignalementFormYear from './SignalementFormYear.vue'
import SignalementFormTime from './SignalementFormTime.vue'
import SignalementFormCounter from './SignalementFormCounter.vue'
import SignalementFormWarning from './SignalementFormWarning.vue'
import SignalementFormInfo from './SignalementFormInfo.vue'
import SignalementFormCheckbox from './SignalementFormCheckbox.vue'
import SignalementFormPhonefield from './SignalementFormPhonefield.vue'
import SignalementFormUpload from './SignalementFormUpload.vue'
import SignalementFormUploadPhotos from './SignalementFormUploadPhotos.vue'
import SignalementFormUploadDocuments from './SignalementFormUploadDocuments.vue'
import SignalementFormOverview from './SignalementFormOverview.vue'
import SignalementFormConfirmation from './SignalementFormConfirmation.vue'
import SignalementFormRoomList from './SignalementFormRoomList.vue'
import SignalementFormDisorderCategoryItem from './SignalementFormDisorderCategoryItem.vue'
import SignalementFormDisorderCategoryList from './SignalementFormDisorderCategoryList.vue'
import SignalementFormSubscreen from './SignalementFormSubscreen.vue'
import SignalementFormAddress from './SignalementFormAddress.vue'
import SignalementFormIcon from './SignalementFormIcon.vue'
import SignalementFormEmailfield from './SignalementFormEmailfield.vue'
import SignalementFormDisorderOverview from './SignalementFormDisorderOverview.vue'
import SignalementFormModal from './SignalementFormModal.vue'
import SignalementFormAutocomplete from './SignalementFormAutocomplete.vue'

export default defineComponent({
  name: 'SignalementFormComponentGenerator',
  components: {
    SignalementFormTextfield,
    SignalementFormTextarea,
    SignalementFormButton,
    SignalementFormLink,
    SignalementFormOnlyChoice,
    SignalementFormSelect,
    SignalementFormDate,
    SignalementFormYear,
    SignalementFormTime,
    SignalementFormCounter,
    SignalementFormWarning,
    SignalementFormInfo,
    SignalementFormCheckbox,
    SignalementFormPhonefield,
    SignalementFormUpload,
    SignalementFormUploadPhotos,
    SignalementFormUploadDocuments,
    SignalementFormOverview,
    SignalementFormConfirmation,
    SignalementFormRoomList,
    SignalementFormDisorderCategoryItem,
    SignalementFormDisorderCategoryList,
    SignalementFormAddress,
    SignalementFormIcon,
    SignalementFormEmailfield,
    SignalementFormDisorderOverview,
    SignalementFormModal,
    SignalementFormAutocomplete,
    SignalementFormSubscreen
  },
  props: {
    componentList: {
      type: Array as () => Array<Component>,
      required: true
    },
    handleClickComponent: {
      type: Function,
      required: true
    }
  },
  data () {
    return {
      formStore
    }
  }
})
</script>
