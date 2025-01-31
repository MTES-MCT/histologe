/* global dsfr */

const modalCguBo = document.getElementById('fr-modal-cgu-bo')
const acceptCguBoCheckbox = document.getElementById('checkboxes-modal-cgu-bo-accept')
const acceptCguBoButton = document.getElementById('fr-modal-cgu-bo-btn')

function handleModalDisclose () {
  setTimeout(() => {
    const divContent = modalCguBo.querySelector('.fr-modal__body')
    divContent.scrollTo({ top: 0 })
  }, 200)
}

function handleAcceptCheck () {
  acceptCguBoButton.disabled = !acceptCguBoCheckbox.checked
}

function handleAcceptButton () {
  const url = document.getElementById('form-cgu-bo-url').value
  const data = {
    _token: document.getElementById('form-cgu-bo-token').value
  }
  const options = {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
  }

  fetch(url, options)
    .then((response) => {
      if (response.ok) {
        dsfr(modalCguBo).modal.conceal()
      } else {
        alert('Erreur lors de l\'enregistrement de votre validation')
      }
    })
    .catch((error) => {
      console.error('Error:', error)
      alert('Erreur lors de l\'enregistrement de votre validation')
    })
}

acceptCguBoCheckbox?.addEventListener('click', handleAcceptCheck)
acceptCguBoButton?.addEventListener('click', handleAcceptButton)
modalCguBo?.addEventListener('dsfr.disclose', handleModalDisclose)

const modalPopNotification = document.getElementById('fr-modal-pop-notification')
if(modalPopNotification){
  //prevent error dsfr is not defined or null
  const checkDsfrInterval = setInterval(() => {
    if (typeof dsfr !== 'undefined' && dsfr !== null) {
      clearInterval(checkDsfrInterval);

        modalPopNotification.addEventListener('dsfr.conceal', (e) => {
            const deletePopNotificationUrl = modalPopNotification.dataset.deleteUrl;
            fetch(deletePopNotificationUrl, {})
        });

      if (modalCguBo && modalPopNotification) {
        modalCguBo.addEventListener('dsfr.conceal', (e) => {
          dsfr(modalPopNotification).modal.disclose();
        });
      } else if (modalPopNotification) {
        dsfr(modalPopNotification).modal.disclose();
      }
    }
  }, 100);
}

