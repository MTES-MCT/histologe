export function createChartPattern (color: string, index: number): CanvasPattern {
  const canvas = document.createElement('canvas')
  canvas.width = 12
  canvas.height = 12
  const context = canvas.getContext('2d')
  if (!context) {
    throw new Error('Impossible de créer le contexte des motifs du graphique.')
  }

  context.fillStyle = color
  context.fillRect(0, 0, canvas.width, canvas.height)
  context.strokeStyle = '#FFFFFF'
  context.fillStyle = '#FFFFFF'
  context.lineWidth = 1.5
  context.beginPath()

  switch (index % 5) {
    case 0:
      // Rayures diagonales
      for (let offset = -12; offset <= 12; offset += 12) {
        context.moveTo(offset, 0)
        context.lineTo(offset + 12, 12)
      }
      break
    case 1:
      // Points
      context.arc(6, 6, 2, 0, Math.PI * 2)
      context.fill()
      break
    case 2:
      // Rayures horizontales
      context.moveTo(0, 6)
      context.lineTo(12, 6)
      break
    case 3:
      // Croix
      context.moveTo(3, 3)
      context.lineTo(9, 9)
      context.moveTo(9, 3)
      context.lineTo(3, 9)
      break
    case 4:
      // Rayures verticales
      context.moveTo(6, 0)
      context.lineTo(6, 12)
      break
  }
  context.stroke()

  const pattern = context.createPattern(canvas, 'repeat')
  if (!pattern) {
    throw new Error('Impossible de créer les motifs du graphique.')
  }
  return pattern
}
