<template>
  <div :class="[ customCss, 'signalement-form-roomlist', { 'signalement-form-roomlist-error': hasError } ]" :id="id">
    <label class="fr-label" :for="idPieceAVivre+'_check'">{{ label }}</label>
    <div class="signalement-form-roomlist-rooms">
      <SignalementFormCheckbox
        :key="idPieceAVivre"
        :id="idPieceAVivre"
        label="Une ou des pièces à vivre (salon, chambres)"
        :validate="validate"
        v-model="formStore.data[idPieceAVivre]"
        :hasError="formStore.validationErrors[idPieceAVivre]  !== undefined"
        :error="formStore.validationErrors[idPieceAVivre]"
        @update:modelValue="handleCheckBox(idPieceAVivre)"
        :access_focus="access_focus"
      />
      <SignalementFormCheckbox
        v-if="formStore.data.type_logement_commodites_cuisine === 'oui'"
        :key="idCuisine"
        :id="idCuisine"
        label="La cuisine / le coin cuisine"
        :validate="validate"
        v-model="formStore.data[idCuisine]"
        :hasError="formStore.validationErrors[idCuisine]  !== undefined"
        :error="formStore.validationErrors[idCuisine]"
        @update:modelValue="handleCheckBox(idCuisine)"
      />
      <SignalementFormCheckbox
        v-if="formStore.data.type_logement_commodites_salle_de_bain === 'oui' || formStore.data.type_logement_commodites_wc === 'oui'"
        :key="idSalleDeBain"
        :id="idSalleDeBain"
        label="La salle de bain, salle d'eau et / ou les toilettes"
        :validate="validate"
        v-model="formStore.data[idSalleDeBain]"
        :hasError="formStore.validationErrors[idSalleDeBain]  !== undefined"
        :error="formStore.validationErrors[idSalleDeBain]"
        @update:modelValue="handleCheckBox(idSalleDeBain)"
      />
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
import SignalementFormCheckbox from './SignalementFormCheckbox.vue'

export default defineComponent({
  name: 'SignalementFormRoomList',
  components: {
    SignalementFormCheckbox
  },
  props: {
    id: { type: String, default: null },
    label: { type: String, default: null },
    modelValue: { type: String, default: null },
    customCss: { type: String, default: '' },
    validate: { type: Object, default: null },
    hasError: { type: Boolean, default: false },
    error: { type: String, default: '' },
    clickEvent: Function,
    access_focus: { type: Boolean, default: false },
    // les propriétés suivantes ne sont pas utilisées,
    // mais si on ne les met pas, elles apparaissent dans le DOM
    // et ça soulève des erreurs W3C
    handleClickComponent: Function,
    access_name: { type: String, default: '' },
    access_autocomplete: { type: String, default: '' }
  },
  data () {
    return {
      idPieceAVivre: this.id + '_piece_a_vivre',
      idCuisine: this.id + '_cuisine',
      idSalleDeBain: this.id + '_salle_de_bain',
      formStore
    }
  },
  methods: {
    handleCheckBox (newValue: string) {
      const isAnyCheckboxChecked = this.formStore.data[this.idPieceAVivre] || this.formStore.data[this.idCuisine] || this.formStore.data[this.idSalleDeBain]
      this.formStore.data[this.id] = isAnyCheckboxChecked
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
.signalement-form-roomlist {
  width: 100%;
  margin-bottom: 20px;
  margin-top: 20px;
}
.signalement-form-roomlist-under-checkbox {
  border-left: 3px solid var(--border-action-high-blue-france);
  margin-left: 10px;
  padding-left: 18px;
}
.signalement-form-roomlist-error {
  border-left: 3px solid var(--text-default-error);
  margin-left: 10px;
  padding-left: 18px;
}
.signalement-form-roomlist-error .fr-label {
  color: var(--text-default-error);
}
.signalement-form-roomlist-error .fr-checkbox-group input[type="checkbox"] + label::before {
  background-image: radial-gradient(at 5px 4px,transparent 4px,var(--border-plain-error) 4px,var(--border-plain-error) 5px,transparent 6px),linear-gradient(var(--border-plain-error),var(--border-plain-error)),radial-gradient(at calc(100% - 5px) 4px,transparent 4px,var(--border-plain-error) 4px,var(--border-plain-error) 5px,transparent 6px),linear-gradient(var(--border-plain-error),var(--border-plain-error)),radial-gradient(at calc(100% - 5px) calc(100% - 4px),transparent 4px,var(--border-plain-error) 4px,var(--border-plain-error) 5px,transparent 6px),linear-gradient(var(--border-plain-error),var(--border-plain-error)),radial-gradient(at 5px calc(100% - 4px),transparent 4px,var(--border-plain-error) 4px,var(--border-plain-error) 5px,transparent 6px),linear-gradient(var(--border-plain-error),var(--border-plain-error)),var(--data-uri-svg);
}
.signalement-form-roomlist-rooms {
  margin-left: 20px;
}
</style>
