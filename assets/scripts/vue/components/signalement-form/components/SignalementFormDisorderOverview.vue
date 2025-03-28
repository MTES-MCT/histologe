<template>
  <div :id="id" class="signalement-form-disorder-overview fr-container--fluid fr-my-3v" :ref="id">
    <div v-if="formStore.data.categorieDisorders.batiment.length > 0">
      <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--middle">
        <div class="fr-col-2 fr-col-md-1"><img :src="icons ? icons[0].src : ''" :alt="icons ? icons[0].alt : ''" class="fr-disorder-overview-image"></div>
        <h5 v-if="isValidationScreen" class="fr-col-10 fr-col-md-11 fr-h2 fr-disorder-overview-title">Le bâtiment</h5>
        <h3 v-else class="fr-col-10 fr-col-md-11 fr-h2 fr-disorder-overview-title">Le bâtiment</h3>
      </div>
      <div class="fr-accordions-group">
        <section
          v-for="(disorder, index) in formStore.data.categorieDisorders.batiment"
          v-bind:key="disorder"
          class="fr-accordion"
          >
          <div
            v-if="hasCategoryFields(disorder)"
            >
            <h6 v-if="isValidationScreen" class="fr-accordion__title">
              <button
                class="fr-accordion__btn"
                aria-expanded="false"
                :aria-controls="'accordion-disorder-batiment-' + index"
                :ref="idRef"
                >
                {{ dictionaryStore[disorder].default }}
              </button>
            </h6>
            <h4 v-else  class="fr-accordion__title">
              <button
                class="fr-accordion__btn"
                aria-expanded="false"
                :aria-controls="'accordion-disorder-batiment-' + index"
                :ref="idRef"
                >
                {{ dictionaryStore[disorder].default }}
              </button>
            </h4>
            <div class="fr-collapse" :id="'accordion-disorder-batiment-' + index">
              <div
                v-for="field in categoryFields(disorder)"
                v-bind:key="field.slug"
                :class="field.css"
                >
                {{field.label}}
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
    <div v-if="formStore.data.categorieDisorders.logement.length > 0">
      <div class="fr-grid-row fr-grid-row--gutters fr-grid-row--middle">
        <div class="fr-col-2 fr-col-md-1"><img :src="icons ? icons[1].src : ''" :alt="icons ? icons[1].alt : ''" class="fr-disorder-overview-image"></div>
        <h5 v-if="isValidationScreen" class="fr-col-10 fr-col-md-11 fr-h2 fr-disorder-overview-title">Le logement</h5>
        <h3 v-else class="fr-col-10 fr-col-md-11 fr-h2 fr-disorder-overview-title">Le logement</h3>
      </div>
      <div class="fr-accordions-group">
        <section
          v-for="(disorder, index) in formStore.data.categorieDisorders.logement"
          v-bind:key="disorder"
          class="fr-accordion"
          >
          <div
            v-if="hasCategoryFields(disorder)"
            >
            <h6 v-if="isValidationScreen" class="fr-accordion__title">
              <button
                class="fr-accordion__btn"
                aria-expanded="false"
                :aria-controls="'accordion-disorder-logement-' + index"
                :ref="idRef"
                >
                {{ dictionaryStore[disorder].default }}
              </button>
            </h6>
            <h4 v-else class="fr-accordion__title">
              <button
                class="fr-accordion__btn"
                aria-expanded="false"
                :aria-controls="'accordion-disorder-logement-' + index"
                :ref="idRef"
                >
                {{ dictionaryStore[disorder].default }}
              </button>
            </h4>
            <div class="fr-collapse" :id="'accordion-disorder-logement-' + index">
              <div
                v-for="field in categoryFields(disorder)"
                v-bind:key="field.slug"
                :class="field.css"
                >
                {{field.label}}
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
    <div v-if="formStore.data.categorieDisorders.batiment.length === 0 && formStore.data.categorieDisorders.logement.length === 0">
      Aucun désordre sélectionné
    </div>
    <!-- MESSAGE A L'ADMINISTRATION -->
    <div v-if="formStore.currentScreen?.slug !== 'desordres_renseignes_batiment'">
      <br>
      <h5 class="fr-h6">Précisions sur la situation</h5>
      <p class="white-space-pre-line">{{ formStore.data[idMessageAdministration] }}</p>
      <p>
        <span v-if="formStore.data['message_administration_documents_upload'] !== undefined && formStore.data['message_administration_documents_upload'].length > 0">{{ formStore.data['message_administration_documents_upload'].length }} document(s) complémentaire(s) ajouté(s).<br></span>
        <span v-if="formStore.data['message_administration_photos_upload'] !== undefined && formStore.data['message_administration_photos_upload'].length > 0">{{ formStore.data['message_administration_photos_upload'].length }} photo(s) complémentaire(s) ajoutée(s).</span>
      </p>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import formStore from './../store'
import dictionaryStore from './../dictionary-store'
import { dictionaryManager } from './../services/dictionaryManager'

export default defineComponent({
  name: 'SignalementFormDisorderOverview',
  props: {
    id: { type: String, default: null },
    icons: { type: Object },
    isValidationScreen: { type: Boolean, default: false },
    access_focus: { type: Boolean, default: false },
    // les propriétés suivantes ne sont pas utilisées,
    // mais si on ne les met pas, elles apparaissent dans le DOM
    // et ça soulève des erreurs W3C
    hasError: { type: Boolean, default: false },
    clickEvent: Function,
    handleClickComponent: Function,
    access_name: { type: String, default: '' },
    access_autocomplete: { type: String, default: '' }
  },
  data () {
    return {
      formStore,
      dictionaryStore,
      idMessageAdministration: 'message_administration',
      idRef: this.id + '_ref'
    }
  },
  mounted () {
    const element = this.$refs[this.id] as HTMLElement
    if (this.access_focus && element && !element.classList.contains('fr-hidden')) {
      this.focusInput()
    }
  },
  methods: {
    getAnswersSlugs (categorySlug: string) {
      const answersSlugs = []
      for (const dataname in formStore.data) {
        if (dataname.includes(categorySlug) && formStore.data[dataname] !== null) {
          answersSlugs.push(dataname)
        }
      }
      answersSlugs.sort()
      return answersSlugs
    },
    hasCategoryFields (categorySlug: string) {
      const answersSlugs = this.getAnswersSlugs(categorySlug)
      return answersSlugs.length > 0
    },
    categoryFields (categorySlug: string) {
      const answersSlugs = this.getAnswersSlugs(categorySlug)

      let headOfGroupSlug = ''
      const answers = []
      for (const slug of answersSlugs) {
        const translatedSlug = this.getTranslationSlug(slug)

        let label
        let css = ''
        if (slug.includes('_upload')) {
          const nbUpload = this.getNbPhotosForSlug(translatedSlug)
          if (nbUpload > 0) {
            label = this.getUploadLabel(slug, nbUpload)
            css = 'italic-text fr-pl-3v border-left'
          }
        } else {
          label = dictionaryManager.translate(translatedSlug, 'disorderOverview')
        }

        if (headOfGroupSlug === '') {
          headOfGroupSlug = slug
        } else if (slug.includes(headOfGroupSlug)) {
          css += ' fr-pl-3v border-left'
        } else {
          headOfGroupSlug = slug
          css += ' fr-pt-3v'
        }

        if (label !== undefined) {
          answers.push({ slug, label, css })
        }
      }
      return answers
    },
    getTranslationSlug (slug: string): string {
      if (slug.endsWith('_pieces')) {
        slug = 'form_room_pieces'
      } else if (slug.endsWith('_pieces_cuisine')) {
        slug = 'form_room_pieces_cuisine'
      } else if (slug.endsWith('_pieces_piece_a_vivre')) {
        slug = 'form_room_pieces_piece_a_vivre'
      } else if (slug.endsWith('_pieces_salle_de_bain')) {
        slug = 'form_room_pieces_salle_de_bain'
      }
      return slug
    },
    getNbPhotosForSlug (slug: string): number {
      if (formStore.data[slug] !== undefined) {
        if (typeof formStore.data[slug] === 'object' && formStore.data[slug].file !== undefined) {
          return 1
        } else if (formStore.data[slug].length !== undefined) {
          return formStore.data[slug].length
        }
      }
      return 0
    },
    getUploadLabel (slug: string, nbUpload: number): string {
      let label = nbUpload.toString()
      if (slug.includes('photos_upload')) {
        if (nbUpload > 1) {
          label += ' photos jointes'
        } else {
          label += ' photo jointe'
        }
      } else {
        if (nbUpload > 1) {
          label += ' fichiers joints'
        } else {
          label += ' fichier joint'
        }
      }
      return label
    },
    updateValue (event: Event) {
      const value = (event.target as HTMLInputElement).value
      this.formStore.data[this.idMessageAdministration] = value
    },
    focusInput () {
      const focusableElement = (this.$refs[this.idRef]) as Array<HTMLElement>
      if (focusableElement && focusableElement[0]) {
        focusableElement[0].focus()
      }
    }
  }
})
</script>

<style>
.signalement-form-disorder-overview .border-left {
  border-left: 2px solid var(--border-action-high-blue-france);
}
.italic-text {
    font-style: italic;
}
.fr-disorder-overview-title {
  margin: 0;
}
.fr-disorder-overview-image {
  width: 60px;
}
</style>
