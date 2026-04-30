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

histoRefreshNotificationButtons();

function histoRefreshNotificationButtons() {
  const deleteBtn = document.querySelector('#delete-notifications-btn');
  const countNotificationsSelected = histoNotificationSelected.length;
  if (countNotificationsSelected > 0) {
    document.querySelector('#notification-selected-buttons-count').textContent =
      countNotificationsSelected + ' sélectionnée(s) :';
    document.querySelector('#mark-as-read-notifications-btn').textContent = 'Marquer comme lue(s)';
    if (deleteBtn) {
      deleteBtn.textContent = 'Supprimer';
      // remove listeners to delete all notifications
      const newDeleteBtn = deleteBtn.cloneNode(true);
      deleteBtn.parentNode.replaceChild(newDeleteBtn, deleteBtn);
    }
  } else {
    document.querySelector('#notification-selected-buttons-count').textContent = '';
    document.querySelector('#mark-as-read-notifications-btn').textContent =
      'Marquer comme lue(s) (tous)';
    if (deleteBtn) {
      deleteBtn.textContent = 'Vider';
      // add confirmation to delete all notifications
      deleteBtn.addEventListener('click', (event) => {
        if (histoNotificationSelected.length === 0) {
          const confirmDeleteAll = confirm(
            'Êtes-vous sûr de vouloir supprimer toutes les notifications ?'
          );
          if (!confirmDeleteAll) {
            event.preventDefault();
          }
        }
      });
    }
  }
  document
    .querySelectorAll('#notification-selected-buttons input[name=selected_notifications]')
    ?.forEach((element) => {
      element.value = histoNotificationSelected.join(',');
    });
}
