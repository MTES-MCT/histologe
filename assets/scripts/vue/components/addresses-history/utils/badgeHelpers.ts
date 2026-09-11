export function getStatusBadgeFromLabel(status: string): string {
  let className = 'fr-badge fr-badge--no-icon '
  switch (status) {
    case 'nouveau':
      className += 'fr-badge--error'
      break
    case 'en cours':
      className += 'fr-badge--success'
      break
    case 'fermé':
      className += 'fr-badge-grey'
      break
  }

  return className
}
