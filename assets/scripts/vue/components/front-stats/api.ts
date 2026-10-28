import axios from 'axios'
import * as Sentry from '@sentry/browser'
import { AXIOS_TIMEOUT } from '../../../vanilla/controllers/environment'
import type { FrontStatsResponse } from './types'

/**
 * Récupère les statistiques publiques, éventuellement filtrées par territoire
 */
export async function fetchFrontStats(ajaxUrl: string, territoire: string): Promise<FrontStatsResponse> {
  try {
    const response = await axios.get<FrontStatsResponse>(ajaxUrl, {
      params: { territoire },
      timeout: AXIOS_TIMEOUT
    })
    return response.data
  } catch (error) {
    console.error('Error fetching front stats:', error)
    Sentry.captureException(error)
    throw error
  }
}
