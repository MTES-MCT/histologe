import { attacheAutocompleteAddressEvent } from '../../services/component/component_search_address'

const containerFormDemandeLienSignalement = document?.querySelector('#container-form-demande-lien-signalement')
if (containerFormDemandeLienSignalement) {
  attachSubmitFormDemandeLienSignalementEvent()
}

function attachSubmitFormDemandeLienSignalementEvent () {
  const fomDemandeLienSignalement = document?.querySelector('#form-demande-lien-signalement')
  if (!fomDemandeLienSignalement) {
    return false
  }
  fomDemandeLienSignalement.addEventListener('submit', (e) => {
    e.preventDefault()
    fomDemandeLienSignalement.querySelector('button[type="submit"]').disabled = true
    const form = e.target
    fetch(form.action, { method: form.method, body: new FormData(form) })
      .then(response => response.json())
      .then(json => {
        containerFormDemandeLienSignalement.innerHTML = json.html
        attachSubmitFormDemandeLienSignalementEvent()
        const inputAdresse = document?.querySelector('#demande_lien_signalement_adresseHelper')
        attacheAutocompleteAddressEvent(inputAdresse)
      })
    return true
  })
}
