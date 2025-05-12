Node.prototype.addEventListeners = function (eventNames, eventFunction) {
  for (const eventName of eventNames.split(' ')) { this.addEventListener(eventName, eventFunction) }
}

document.querySelectorAll('.fr-disable-button-when-submit')?.forEach(element => {
  element.addEventListener('submit', (event) => {
    if (element.checkValidity()) {
      element.querySelectorAll('button[type=submit]')?.forEach(element => {
        element.setAttribute('disabled', true)
      })
    }
  })
})

const autoSubmitElements = document.querySelectorAll('.fr-auto-submit')
autoSubmitElements.forEach(autoSubmitElements => {
  autoSubmitElements.addEventListener('change', function () {
    document.getElementById('page').value = 1
    this.form.submit()
  })
})
