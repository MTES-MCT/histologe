/**
 * Formate une valeur statistique pour l'affichage, avec une valeur de repli si elle est absente
 */
export function formatStatValue(
  value: number | string | null | undefined,
  fallback: string,
  suffix: string = ''
): string {
  return `${value ?? fallback}${suffix}`
}
