import { createApp } from 'vue'
import * as Sentry from '@sentry/browser'

// Chargé à la demande : le calendrier embarque fullcalendar, inutile sur les autres pages
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
