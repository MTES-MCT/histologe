import { setCookie, getCookie } from './cookie_utils'

const maintenanceBannerElement = document.getElementById('maintenance-banner')
if (maintenanceBannerElement !== null) {
  if (document.body.dataset.modeMaintenance === '1') {
    setCookie('maintenanceBannerShow', true, 1) // 1 day
  }

  const closeButtonElement = document.querySelector('#maintenance-banner .fr-btn--close')
  if (closeButtonElement !== null) {
    closeButtonElement.addEventListener('click', (event) => {
      const notice = event.target.parentNode.parentNode.parentNode
      notice.parentNode.removeChild(notice)
      setCookie('maintenanceBannerShow', false, 1) // 1 day
    })
  }

  if (getCookie('maintenanceBannerShow') === 'false') {
    maintenanceBannerElement.classList.add('fr-hidden')
  } else {
    maintenanceBannerElement.classList.remove('fr-hidden')
  }
}
