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
    if (document?.documentElement.getAttribute('data-fr-theme') == 'light') {
        document?.documentElement.setAttribute('data-fr-theme', 'dark')
    }
    document?.querySelectorAll('.photos-album-image-item.loop-current')?.forEach(element => {
        element.classList?.remove('loop-current')
        element.classList?.add('fr-hidden')
    })
    document?.querySelectorAll('.photos-album-image-item[data-id="'+photoId+'"]')?.forEach(element => {
        element.classList?.add('loop-current')
        element.classList?.remove('fr-hidden')
    })
}

document?.querySelector('[data-filter-list-signalement]')?.addEventListener('click', (event) => {
    if (window.history.length > 1) {
        event.preventDefault()
        const backLinkQueryParams = localStorage.getItem('back_link_signalement_view')
        window.location.href = backLinkQueryParams?.length > 0
            ? `${event.target.href}?${backLinkQueryParams}`
            : event.target.href
    }
})

document?.querySelectorAll('.signalement-tag-add')?.forEach(element => {
    element.addEventListener('click', (event) => {
        element.classList?.add('fr-hidden', 'disabled')

        const etiquette = document.createElement('span');
        etiquette.classList.add('fr-badge', 'fr-badge--blue-ecume', 'fr-m-1v', 'signalement-tag-remove')
        etiquette.setAttribute('data-tagid', element.getAttribute('data-tagid'))
        etiquette.innerText = element.getAttribute('data-taglabel') + ' ';
        etiquette.addEventListener('click', (event) => {
            removeEtiquette(etiquette);
        })

        const container = document.querySelector('#etiquette-selected-list');
        container.append(etiquette);

        const etiquetteIcon = document.createElement('span');
        etiquetteIcon.classList.add('fr-icon-close-line')
        etiquetteIcon.setAttribute('aria-hidden', true)
        etiquette.append(etiquetteIcon);

        const containerNoTag = document.querySelector('#no-tag-on-this-signalement');
        containerNoTag?.classList?.add('fr-hidden')

        refreshHiddenInput();
    })
})

const inputEtiquetteFilter = document?.querySelector('#etiquette-filter-input')
inputEtiquetteFilter?.addEventListener('input', (event) => {
    const inputValue = event.target.value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
    document?.querySelectorAll('.signalement-tag-add')?.forEach(element => {
        const normalizedTagLabel = element.getAttribute('data-taglabel').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
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
    const etiquetteBadgeAdd = document.querySelector('#etiquette-badge-add-' + tagId);
    etiquetteBadgeAdd.classList?.remove('fr-hidden', 'disabled');

    element.remove();

    refreshHiddenInput();
}
const refreshHiddenInput = () => {
    const inputHidden = document.querySelector('#input-tag-ids');
    inputHidden.setAttribute('value', '')
    document?.querySelectorAll('.signalement-tag-remove').forEach(element => {
        if (inputHidden.getAttribute('value') !== '') {
            inputHidden.setAttribute('value', inputHidden.getAttribute('value')+','+element.getAttribute('data-tagid'))
        } else {
            inputHidden.setAttribute('value', element.getAttribute('data-tagid'))
        }
    })
}
