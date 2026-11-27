import { createApp } from 'vue'
import * as Sentry from '@sentry/browser'

const calendarElement = document.getElementById('calendar-app')
if (calendarElement !== null) {
  try {
    const { default: CalendarApp } = await import('./components/calendar/CalendarApp.vue')
    createApp(CalendarApp).mount(calendarElement)
  } catch (error) {
    console.error('Error loading calendar app:', error)
    Sentry.captureException(error)
  }
}
