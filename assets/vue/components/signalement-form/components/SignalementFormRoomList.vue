<template>
  <div :class="[ customCss, 'signalement-form-roomlist' ]" :id="id">
    <label class="fr-label" :for="id">{{ label }}</label>
    <div
      id="text-input-error-desc-error"
      class="fr-error-text"
      v-if="hasError"
      >
      {{ error }}
    </div>
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
    clickEvent: Function
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
.signalement-form-roomlist-rooms {
  margin-left: 20px;
}
</style>
