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

export function getArretePictoClass(groupIndex: number): string {
  const pictoMap: Record<number, string> = {
    0: 'purple-hexagon',
    1: 'blue-square',
    2: 'purple-diamond'
  }

  return pictoMap[groupIndex] || 'purple-hexagon'
}

export function getArretePictoClassFromId(
  arreteTypeId: string,
  arreteTypesGroups: Array<{ options: Array<{ Id: string }> }>
): string {
  const groupIndex = arreteTypesGroups.findIndex((group) =>
    group.options.some((option) => option.Id === arreteTypeId)
  )

  return getArretePictoClass(groupIndex !== -1 ? groupIndex : 0)
}
