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

document?.querySelector('.back-link')?.addEventListener('click', (event) => {
    event.preventDefault()
    const backLinkQueryParams = localStorage.getItem('back_link_signalement_view')
    window.location.href = backLinkQueryParams?.length > 0
        ? event.target.dataset.href + backLinkQueryParams
        : event.target.dataset.href
})
