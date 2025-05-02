export function disableHeaderAndFooterButtonOfModal(modal){
  const headerButtons = modal.querySelectorAll('.fr-modal__header button')
  const footerButtons = modal.querySelectorAll('.fr-modal__footer button')
  headerButtons.forEach(button => {
    button.setAttribute('disabled', '')
  })
  footerButtons.forEach(button => {
    button.setAttribute('disabled', '')
  })
}

export function enableHeaderAndFooterButtonOfModal(modal){
  const headerButtons = modal.querySelectorAll('.fr-modal__header button')
  const footerButtons = modal.querySelectorAll('.fr-modal__footer button')
  headerButtons.forEach(button => {
    button.removeAttribute('disabled')
  })
  footerButtons.forEach(button => {
    button.removeAttribute('disabled')
  })
}