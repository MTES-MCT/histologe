import { Chart as ChartJS } from 'chart.js'

/**
 * Navigation au clavier dans un graphique chart.js.
 * Chaque fonction reçoit l'événement d'un élément focusable qui contient le canvas du graphique.
 */

/**
 * Instance chart.js du canvas contenu dans l'élément qui porte l'écouteur
 */
const getChart = (event: Event): ChartJS | undefined => {
  const element = event.currentTarget as HTMLElement | null
  const canvas = element?.querySelector('canvas')
  if (!canvas) {
    return undefined
  }
  return ChartJS.getChart(canvas)
}

const getValuesCount = (chart: ChartJS): number => {
  return chart.data.labels?.length ?? 0
}

/**
 * Index de la valeur actuellement affichée, ou null si aucune
 */
const getActiveIndex = (chart: ChartJS): number | null => {
  const activeElements = chart.getActiveElements()
  if (activeElements.length === 0) {
    return null
  }
  return activeElements[0].index
}

/**
 * Met en surbrillance la valeur à l'index donné et affiche son infobulle, comme au survol de la souris
 */
const showValue = (chart: ChartJS, index: number): void => {
  const activeElements = [{ datasetIndex: 0, index }]
  const element = chart.getDatasetMeta(0).data[index]
  chart.setActiveElements(activeElements)
  chart.tooltip?.setActiveElements(activeElements, { x: element.x, y: element.y })
  chart.update()
}

export const showFirstChartValue = (event: Event): void => {
  const chart = getChart(event)
  if (chart && getValuesCount(chart) > 0) {
    showValue(chart, 0)
  }
}

export const showLastChartValue = (event: Event): void => {
  const chart = getChart(event)
  if (chart && getValuesCount(chart) > 0) {
    showValue(chart, getValuesCount(chart) - 1)
  }
}

/**
 * Affiche la valeur suivante, en revenant à la première après la dernière
 */
export const showNextChartValue = (event: Event): void => {
  const chart = getChart(event)
  if (!chart) {
    return
  }
  const activeIndex = getActiveIndex(chart)
  const isLastValue = activeIndex === getValuesCount(chart) - 1
  if (activeIndex === null || isLastValue) {
    showFirstChartValue(event)
    return
  }
  showValue(chart, activeIndex + 1)
}

/**
 * Affiche la valeur précédente, en revenant à la dernière avant la première
 */
export const showPreviousChartValue = (event: Event): void => {
  const chart = getChart(event)
  if (!chart) {
    return
  }
  const activeIndex = getActiveIndex(chart)
  if (activeIndex === null || activeIndex === 0) {
    showLastChartValue(event)
    return
  }
  showValue(chart, activeIndex - 1)
}

/**
 * Masque l'infobulle et la surbrillance
 */
export const hideChartValue = (event: Event): void => {
  const chart = getChart(event)
  if (!chart) {
    return
  }
  chart.setActiveElements([])
  chart.tooltip?.setActiveElements([], { x: 0, y: 0 })
  chart.update()
}
