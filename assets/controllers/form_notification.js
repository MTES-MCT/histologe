const histoNotificationCheckboxes = document.querySelectorAll('.check-notification')
const histoNotificationSelected = []

histoNotificationCheckboxes?.forEach(element => {
    element.addEventListener('change', (event) => {
        let idNotification = element.getAttribute('data-notification-id')
        if (element.checked) {
            histoNotificationSelected.push(idNotification)
        } else {
            let indexNotification = histoNotificationSelected.indexOf(idNotification);
            if (indexNotification > -1) {
                histoNotificationSelected.splice(indexNotification, 1);
            }
        }
        histoRefreshNotificationButtons()
    })
})

function histoRefreshNotificationButtons() {
    let countNotificationsSelected = histoNotificationSelected.length
    if (countNotificationsSelected > 0) {
        document.querySelector('#notification-selected-buttons')?.classList.remove('fr-hidden')
        document.querySelector('#notification-all-buttons')?.classList.add('fr-hidden')
        document.querySelector('#notification-selected-buttons-count').textContent = countNotificationsSelected
    } else {
        document.querySelector('#notification-selected-buttons')?.classList.add('fr-hidden')
        document.querySelector('#notification-all-buttons')?.classList.remove('fr-hidden')
    }
    document.querySelectorAll('#notification-selected-buttons input[name=selected_notifications]')?.forEach(element => {
        element.value = histoNotificationSelected.join(',')
    })
}
