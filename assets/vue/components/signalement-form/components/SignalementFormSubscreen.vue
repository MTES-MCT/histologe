<template>
  <div :class="[ customCss ]" :id="id">
    <h3 v-if="label">{{ variablesReplacer.replace(label) }}</h3>
    <p v-if="description" v-html="variablesReplacer.replace(description)"></p>
    <div
      v-if="components != undefined"
      >
      <component
        v-for="component in components.body"
        :is="component.type"
        v-bind:key="component.slug"
        :id="component.slug"
        :access_name="component.accessibility?.name ?? component.slug"
        :access_autocomplete="component.accessibility?.autocomplete ?? 'off'"
        :label="component.label"
        :hint="component.hint"
        :labelInfo="component.labelInfo"
        :labelUpload="component.labelUpload"
        :description="component.description"
        :placeholder="component.placeholder"
        :action="component.action"
        :link="component.link"
        :linktarget="component.linktarget"
        :components="component.components"
        :values="component.values"
        :customCss="component.customCss"
        :validate="component.validate"
        :disabled="component.disabled"
        :multiple="component.multiple"
        :ariaControls="component.ariaControls"
        :tagWhenEdit="component.tagWhenEdit"
        v-model="formStore.data[component.slug]"
        :hasError="formStore.validationErrors[component.slug]  !== undefined"
        :error="formStore.validationErrors[component.slug]"
        :class="{ 'fr-hidden': component.conditional && !formStore.shouldShowField(component.conditional.show) }"
        :clickEvent="handleClickComponent"
      />
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'
import { variablesReplacer } from './../services/variableReplacer'
import SignalementFormTextfield from './SignalementFormTextfield.vue'
import SignalementFormTextarea from './SignalementFormTextarea.vue'
import SignalementFormButton from './SignalementFormButton.vue'
import SignalementFormLink from './SignalementFormLink.vue'
import SignalementFormOnlyChoice from './SignalementFormOnlyChoice.vue'
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
import SignalementFormOverview from './SignalementFormOverview.vue'
import SignalementFormConfirmation from './SignalementFormConfirmation.vue'
import SignalementFormRoomList from './SignalementFormRoomList.vue'
import SignalementFormDisorderCategoryItem from './SignalementFormDisorderCategoryItem.vue'
import SignalementFormDisorderCategoryList from './SignalementFormDisorderCategoryList.vue'

export default defineComponent({
  name: 'SignalementFormSubscreen',
  components: {
    SignalementFormTextfield,
    SignalementFormTextarea,
    SignalementFormButton,
    SignalementFormLink,
    SignalementFormOnlyChoice,
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
    SignalementFormOverview,
    SignalementFormConfirmation,
    SignalementFormRoomList,
    SignalementFormDisorderCategoryItem,
    SignalementFormDisorderCategoryList
  },
  props: {
    id: String,
    label: String,
    description: String,
    components: Object,
    customCss: { type: String, default: '' },
    handleClickComponent: Function
  },
  data () {
    return {
      formStore,
      variablesReplacer
    }
  },
  methods: {
    updateFormData (slug: string, value: any) {
      this.formStore.data[slug] = value
    }
  }
})
</script>

<style>
.signalement-form-subscreen-under-checkbox {
  border-left: 3px solid var(--border-action-high-blue-france);
  margin-left: 10px;
  padding-left: 18px;
}
</style>
