let histoNotificationSelected = [];
let countNotificationsSelected = 0;
const histoNotificationsContainer = document.querySelector('#table-list-results');
histoNotificationsContainer?.addEventListener('change', (event) => {
  const element = event.target;
  if (!element.classList.contains('check-notification')) {
    return;
  }

  histoNotificationSelected.length = 0;
  document.querySelectorAll('.check-notification:checked').forEach((checkedElement) => {
    const checkedIdNotification = checkedElement.getAttribute('data-notification-id');
    histoNotificationSelected.push(checkedIdNotification);
  });

  histoRefreshNotificationButtons();
});

export function histoReinitNotification() {
  histoNotificationSelected = [];
  countNotificationsSelected = 0;
}
export function histoRefreshNotificationButtons() {
  const deleteBtn = document.querySelector('#delete-notifications-btn');
  const markAsReadBtn = document.querySelector('#mark-as-read-notifications-btn');
  const nbSelectedLabel = document.querySelector('#notification-selected-buttons-count');
  if (deleteBtn) {
    countNotificationsSelected = histoNotificationSelected.length;
    if (countNotificationsSelected > 0) {
      nbSelectedLabel.textContent = countNotificationsSelected + ' sélectionnée(s) :';
      markAsReadBtn.textContent = 'Marquer comme lue(s)';
      deleteBtn.textContent = 'Supprimer';
      // remove listeners to delete all notifications
      const newDeleteBtn = deleteBtn.cloneNode(true);
      deleteBtn.parentNode.replaceChild(newDeleteBtn, deleteBtn);
    } else {
      nbSelectedLabel.textContent = '';
      markAsReadBtn.textContent = 'Marquer comme lue(s) (tous)';
      deleteBtn.textContent = 'Vider';
      // add confirmation to delete all notifications
      deleteBtn.addEventListener('click', (event) => {
        if (countNotificationsSelected === 0) {
          const confirmDeleteAll = confirm(
            'Êtes-vous sûr de vouloir supprimer toutes les notifications ?'
          );
          if (!confirmDeleteAll) {
            event.preventDefault();
          }
        }
      });
    }
    document
      .querySelectorAll('#notification-selected-buttons input[name=selected_notifications]')
      ?.forEach((element) => {
        element.value = histoNotificationSelected.join(',');
      });
  }
}
histoReinitNotification();
histoRefreshNotificationButtons();
