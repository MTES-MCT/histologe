import type { EventInput } from '@fullcalendar/vue3'

// TODO : événements fictifs pour la mise en forme, à supprimer au branchement de la vraie source
export const FAKE_EVENTS: EventInput[] = [
  { title: 'Visite #2026-3034 Toto', start: '2026-11-02T09:00:00', extendedProps: { type: 'visite' } },
  { title: 'Visite #2026-2993 Titi', start: '2026-11-02T14:00:00', extendedProps: { type: 'visite' } },
  { title: 'Visite #2026-3034', start: '2026-11-04T09:00:00', extendedProps: { type: 'visite' } },
  { title: 'Visite #2026-3034', start: '2026-11-06T09:00:00', extendedProps: { type: 'visite' } },
  { title: 'Visite #2026-2993', start: '2026-11-06T14:00:00', extendedProps: { type: 'visite' } },
  { title: 'Club utilisateurs (RT)', start: '2026-11-10T14:00:00', extendedProps: { type: 'club' } },
  { title: 'Visite #2026-3093', start: '2026-11-12T10:00:00', extendedProps: { type: 'visite' } },
  { title: 'Visite #2026-3034', start: '2026-11-16T09:00:00', extendedProps: { type: 'visite' } },
  { title: 'Visite #2026-2993', start: '2026-11-16T14:00:00', extendedProps: { type: 'visite' } },
  { title: 'Visite #2026-3044', start: '2026-11-16T15:00:00', extendedProps: { type: 'visite' } },
  { title: 'Visite #2026-520', start: '2026-11-16T16:00:00', extendedProps: { type: 'visite' } },
  { title: 'Session de prise en main', start: '2026-11-19T14:00:00', extendedProps: { type: 'club' } },
  { title: 'Visite #2026-1925', start: '2026-11-24', allDay: true, extendedProps: { type: 'visite' } },
  { title: 'Club partenaires', start: '2026-11-26T14:00:00', extendedProps: { type: 'club' } },
  { title: 'Club partenaires', start: '2026-12-01T14:00:00', extendedProps: { type: 'club' } },
  { title: 'Visite #2026-2993', start: '2026-12-04T14:00:00', extendedProps: { type: 'visite' } },
]
