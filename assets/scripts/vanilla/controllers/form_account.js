const territory = document.querySelector('#user_territory')

territory?.addEventListeners('change', (event) => {
  const form = document.querySelector('form#account_user')
  const partner = document.querySelector('#user_tempPartner')
  const url = form.action
  const type = form.method

  const options = {
    method: type,
    headers: {},
    body: new FormData(form)
  }

  fetch(url, options)
    .then(function (response) {
      return response.text()
    })
    .then(function (html) {
      const parser = new DOMParser()
      const receivedDoc = parser.parseFromString(html, 'text/html')
      const newPartner = receivedDoc.querySelector('#user_tempPartner')
      if (newPartner !== null) {
        newPartner.addEventListener('change', function (e) {
          e.target.classList.remove('is-invalid')
        })
        partner.replaceWith(newPartner)
      }
    })
    .catch(function (err) {
      console.warn('Something went wrong.', err)
    })
})
