/**
 * Plage de dates par défaut : les 6 derniers mois complets
 * (du 1er jour d'il y a 6 mois au dernier jour du mois précédent)
 */
export function getDefaultDateRange(): Date[] {
  const today = new Date()
  const startDate = new Date(today.getFullYear(), today.getMonth() - 6, 1)

  const endDate = new Date()
  endDate.setDate(0)
  endDate.setHours(23, 59, 59)

  return [startDate, endDate]
}

/**
 * Convertit une date en chaîne ISO en heure locale (attendue côté PHP)
 */
export function toLocalIsoString(date: Date): string {
  const localDate = new Date(date)
  localDate.setMinutes(localDate.getMinutes() - localDate.getTimezoneOffset())
  return localDate.toISOString()
}
