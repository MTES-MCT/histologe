const histoNotificationSelected = [];
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

function histoRefreshNotificationButtons() {
  const countNotificationsSelected = histoNotificationSelected.length;
  if (countNotificationsSelected > 0) {
    document.querySelector('#notification-selected-buttons-count').textContent = countNotificationsSelected + ' sélectionnée(s) :';
    document.querySelector('#mark-as-read-notifications-btn').textContent = 'Marquer comme lue(s)';
    document.querySelector('#delete-notifications-btn').textContent = 'Supprimer';
  } else {
    document.querySelector('#notification-selected-buttons-count').textContent = '';
    document.querySelector('#mark-as-read-notifications-btn').textContent = 'Marquer comme lue(s) (tous)';
    document.querySelector('#delete-notifications-btn').textContent = 'Vider';
  }
  document.querySelectorAll('#notification-selected-buttons input[name=selected_notifications]')?.forEach((element) => {
      element.value = histoNotificationSelected.join(',');
  });
}
