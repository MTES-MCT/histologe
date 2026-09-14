import { registerClickRoute } from '../../services/ui/click_dispatcher';

registerClickRoute('.btn-delete-config-service-secours-route', (target, evt) => {
  document.querySelector('.fr-modal-config-service-secours-route-delete-name').textContent =
    target.getAttribute('data-config-service-secours-route-name');
  document.querySelector('#config_service_secours_route_delete_form').action = target.getAttribute(
    'data-config-service-secours-route-url'
  );
});
