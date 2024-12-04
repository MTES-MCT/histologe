import { loadWindowWithLocalStorage } from '../../services/list_filter_helper'

/* global histoPhotoIds */

document?.querySelector('#btn-display-all-suivis')?.addEventListeners('click touchdown', (e) => {
  e.preventDefault()
  document.querySelectorAll('.suivi-item').forEach(item => {
    item.classList.remove('fr-hidden')
  })
  document.querySelector('#btn-display-all-suivis').classList.add('fr-hidden')
})

document?.querySelectorAll('.open-photo-album')?.forEach(btn => {
  const swipeId = btn.getAttribute('data-id')
  btn.addEventListeners('click touchdown', (event) => {
    document?.documentElement.setAttribute('data-fr-theme', 'dark')
    document?.querySelectorAll('.photos-album')?.forEach(element => {
      element.classList?.remove('fr-hidden')
      displayPhotoAlbum(swipeId)
    })
  })
})
document?.querySelectorAll('.photos-album-btn-close')?.forEach(btn => {
  btn.addEventListeners('click touchdown', (event) => {
    document?.documentElement.setAttribute('data-fr-theme', 'light')
    document?.querySelectorAll('.photos-album')?.forEach(element => {
      element.classList?.add('fr-hidden')
    })
  })
})
document?.querySelectorAll('.photos-album-swipe')?.forEach(btn => {
  const swipeDirection = Number(btn.getAttribute('data-direction'))

  btn.addEventListeners('click touchdown', (event) => {
    let currentId = null
    document?.querySelectorAll('.photos-album-image-item.loop-current')?.forEach(element => {
      currentId = Number(element.getAttribute('data-id'))
    })
    let newIndex = histoPhotoIds.indexOf(currentId)
    newIndex += Number(swipeDirection)
    if (newIndex < 0) {
      newIndex = histoPhotoIds.length - 1
    }
    if (newIndex > histoPhotoIds.length - 1) {
      newIndex = 0
    }
    displayPhotoAlbum(histoPhotoIds[newIndex])
  })
})
const displayPhotoAlbum = (photoId) => {
  if (document?.documentElement.getAttribute('data-fr-theme') === 'light') {
    document?.documentElement.setAttribute('data-fr-theme', 'dark')
  }
  document?.querySelectorAll('.photos-album-image-item.loop-current')?.forEach(element => {
    element.classList?.remove('loop-current')
    element.classList?.add('fr-hidden')
  })
  document?.querySelectorAll('.photos-album-image-item[data-id="' + photoId + '"]')?.forEach(element => {
    element.classList?.add('loop-current')
    element.classList?.remove('fr-hidden')
  })
}

loadWindowWithLocalStorage('click', '[data-filter-list-signalement]', 'back_link_signalement_view')

document?.querySelectorAll('.signalement-tag-add')?.forEach(element => {
  element.addEventListener('click', (event) => {
    element.classList?.add('fr-hidden', 'disabled')

    const etiquette = document.createElement('span')
    etiquette.classList.add('fr-badge', 'fr-badge--blue-ecume', 'fr-m-1v', 'signalement-tag-remove')
    etiquette.setAttribute('data-tagid', element.getAttribute('data-tagid'))
    etiquette.innerText = element.getAttribute('data-taglabel') + ' '
    etiquette.addEventListener('click', (event) => {
      removeEtiquette(etiquette)
    })

    const container = document.querySelector('#etiquette-selected-list')
    container.append(etiquette)

    const etiquetteIcon = document.createElement('span')
    etiquetteIcon.classList.add('fr-icon-close-line')
    etiquetteIcon.setAttribute('aria-hidden', true)
    etiquette.append(etiquetteIcon)

    const containerNoTag = document.querySelector('#no-tag-on-this-signalement')
    containerNoTag?.classList?.add('fr-hidden')

    refreshHiddenInput()
  })
})

const inputEtiquetteFilter = document?.querySelector('#etiquette-filter-input')
inputEtiquetteFilter?.addEventListener('input', (event) => {
  const inputValue = event.target.value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
  document?.querySelectorAll('.signalement-tag-add')?.forEach(element => {
    const normalizedTagLabel = element.getAttribute('data-taglabel').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
    if (normalizedTagLabel.indexOf(inputValue) > -1 && !element.classList.contains('disabled')) {
      element.classList?.remove('fr-hidden')
    } else {
      element.classList?.add('fr-hidden')
    }
  })
})

document?.querySelectorAll('.signalement-tag-remove')?.forEach(element => {
  element.addEventListener('click', (event) => {
    removeEtiquette(element)
  })
})

const removeEtiquette = (element) => {
  const tagId = element.getAttribute('data-tagid')
  const etiquetteBadgeAdd = document.querySelector('#etiquette-badge-add-' + tagId)
  etiquetteBadgeAdd.classList?.remove('fr-hidden', 'disabled')

  element.remove()

  refreshHiddenInput()
}
const refreshHiddenInput = () => {
  const inputHidden = document.querySelector('#input-tag-ids')
  inputHidden.setAttribute('value', '')
  document?.querySelectorAll('.signalement-tag-remove').forEach(element => {
    if (inputHidden.getAttribute('value') !== '') {
      inputHidden.setAttribute('value', inputHidden.getAttribute('value') + ',' + element.getAttribute('data-tagid'))
    } else {
      inputHidden.setAttribute('value', element.getAttribute('data-tagid'))
    }
  })
}

document?.querySelectorAll('[data-fr-select-target]')?.forEach(t => {
  const source = document?.querySelector('#' + t.getAttribute('data-fr-select-source'))
  const target = document?.querySelector('#' + t.getAttribute('data-fr-select-target'))
  t.addEventListeners('click touchdown', () => {
    [...source.selectedOptions].forEach(s => {
      target.append(s)
    })
  })
})

document?.querySelector('#signalement-affectation-form-submit')?.addEventListeners('click touchdown', (e) => {
  e.preventDefault()
  e.target.disabled = true
  e.target?.form?.querySelectorAll('option').forEach(o => {
    o.selected = true
  })
  document?.querySelectorAll('#signalement-affectation-form-row,#signalement-affectation-loader-row').forEach(el => {
    el.classList.toggle('fr-hidden')
  })

  const formData = new FormData(e.target.form)
  fetch(e.target.getAttribute('formaction'), {
    method: 'POST',
    body: formData
  }).then(r => {
    if (r.ok) {
      window.location.reload(true)
    }
  })
})

const modalsElement = document?.querySelectorAll('#cloture-modal, #fr-modal-add-suivi, #refus-signalement-modal, #refus-affectation-modal')
modalsElement.forEach(modalElement => {
  modalElement.addEventListener('submit', (e) => {
    const submitButton = modalElement.querySelector('.fr-modal--opened [type=submit]')
    if (submitButton) {
      submitButton.disabled = true
    }
  })
})

document.querySelectorAll('button[data-cloture-type]').forEach(button => {
  button.addEventListener('click', (e) => {
    const element = e.target;
    if (element && element?.dataset) {
      document.getElementById('cloture_type').value = element.dataset.clotureType
    }
  });
});

document?.getElementById('signalement-add-suivi-notify-usager')?.addEventListeners('change', (e) => {
  document.getElementById('signalement-add-suivi-submit').textContent = (e.target.checked) ? 'Envoyer le suivi à l\'usager' : 'Enregistrer le suivi interne'
})

document?.getElementById('fr-modal-historique-affectation')?.addEventListener('dsfr.disclose', (event) => {
  const signalementId = event.target.dataset.signalementId

  document?.querySelectorAll('#signalement-historique-affectation-loader-row').forEach(el => {
    el.classList.toggle('fr-hidden')
  })

  if (!signalementId) {
    console.warn('Aucun signalementId trouvé')
    return
  }

  fetch('/bo/history/signalement/' + signalementId + '/affectations', {
    method: 'GET'
  }).then(response => response.json())
    .then(data => {
      if (data.historyEntries) {
        if (data.historyEntries.length === 0) {
          const loadingMessage = document.getElementById('fr-modal-historique-affectation-loading-message')
          loadingMessage.textContent = 'Il n\'y a pas d\'historique pour ce signalement'
          loadingMessage.classList.remove('fr-hidden')
        } else {
          const historyEntries = data.historyEntries
          const loadingMessage = document.getElementById('fr-modal-historique-affectation-loading-message')
          loadingMessage.classList.add('fr-hidden')
          const modalContent = document.getElementById('fr-modal-historique-affectation-content')
          modalContent.innerHTML = ''

          for (const [partner, events] of Object.entries(historyEntries)) {
            const table = document.createElement('div')
            table.classList.add('fr-table--sm')
            table.classList.add('fr-table')

            const wrapper = document.createElement('div')
            wrapper.classList.add('fr-table__wrapper')
            table.appendChild(wrapper)

            const container = document.createElement('div')
            container.classList.add('fr-table__container')
            wrapper.appendChild(container)

            const tableContent = document.createElement('div')
            tableContent.classList.add('fr-table__content')
            container.appendChild(tableContent)

            const tableContentTable = document.createElement('table')
            tableContentTable.classList.add('fr-table__content')
            tableContent.appendChild(tableContentTable)

            const tableContentTitle = document.createElement('caption')
            tableContentTitle.textContent = partner
            tableContentTable.appendChild(tableContentTitle)

            const tableHeader = document.createElement('thead')
            tableHeader.innerHTML = `
                            <tr>
                                <th class="fr-w-15">Date</th>
                                <th>Action</th>
                            </tr>
                        `
            tableContentTable.appendChild(tableHeader)

            const tableBody = document.createElement('tbody')
            events.forEach(event => {
              const row = document.createElement('tr')
              const dateCell = document.createElement('td')
              const dateObj = new Date(event.Date)
              const formattedDate = dateObj.toLocaleDateString('fr-FR', { year: 'numeric', month: '2-digit', day: '2-digit' }) +
                                                ' à ' + dateObj.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
              dateCell.textContent = formattedDate
              row.appendChild(dateCell)

              const actionCell = document.createElement('td')
              actionCell.textContent = event.Action
              row.appendChild(actionCell)

              tableBody.appendChild(row)
            })

            tableContentTable.appendChild(tableBody)
            modalContent.appendChild(table)
          }
        }
      } else {
        console.warn('Erreur de récupération des données :', data.response)
      }
    })
    .catch(function (err) {
      console.warn('Something went wrong.', err)
    })
})
