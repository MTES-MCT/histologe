// Avec "moduleResolution": "node", TypeScript ignore le champ "exports" de @fullcalendar/vue3
// et ne trouve pas les types des locales (déclarés uniquement dans locales/locale.d.ts)
declare module '@fullcalendar/vue3/locales/fr' {
  import type { LocaleInput } from '@fullcalendar/vue3'
  const locale: LocaleInput
  export default locale
}
