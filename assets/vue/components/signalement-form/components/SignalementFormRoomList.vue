<template>
  <div class="signalement-form-roomlist" :id="id">
    <label :class="[ customCss, 'fr-label' ]" :for="id">{{ label }}</label>
    <SignalementFormCheckbox
      :key="idPieceAVivre"
      :id="idPieceAVivre"
      label="Une ou des pièces à vivre (salon, chambres)"
      :validate="validate"
      v-model="formStore.data[idPieceAVivre]"
      :hasError="formStore.validationErrors[idPieceAVivre]  !== undefined"
      :error="formStore.validationErrors[idPieceAVivre]"
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
    />
    <!-- TODO : définir en dur que validate.require=false ? -->
    <SignalementFormCheckbox
      v-if="formStore.data.type_logement_commodites_salle_de_bain === 'oui' || formStore.data.type_logement_commodites_wc === 'oui'"
      :key="idSalleDeBain"
      :id="idSalleDeBain"
      label="La salle de bain, salle d'eau et / ou les toilettes"
      :validate="validate"
      v-model="formStore.data[idSalleDeBain]"
      :hasError="formStore.validationErrors[idSalleDeBain]  !== undefined"
      :error="formStore.validationErrors[idSalleDeBain]"
    />
    <!-- TODO : définir en dur que validate.require=false ? -->
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
    updateValue (value: any) {
      this.$emit('update:modelValue', value)
    },
    handleClickButton (type:string, param:string, slugButton:string) {
      if (this.clickEvent !== undefined) {
        this.clickEvent(type, param, slugButton)
      }
    }
  },
  emits: ['update:modelValue']
})
</script>

<style>
.signalement-form-roomlist {
  width: 100%;
}
</style>
