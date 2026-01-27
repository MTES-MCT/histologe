document.addEventListener('click', (evt) => {
  const target = evt.target.closest('.btn-delete-config-service-secours-route');
  if (target) {
    document.querySelector('.fr-modal-config-service-secours-route-delete-name').textContent = target.getAttribute('data-config-service-secours-route-name');
    document.querySelector('#config_service_secours_route_delete_form').action = target.getAttribute('data-config-service-secours-route-url');
  }
});
