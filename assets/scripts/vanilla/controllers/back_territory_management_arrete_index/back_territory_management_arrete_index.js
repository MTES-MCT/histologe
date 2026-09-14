import { registerClickRoute } from '../../services/ui/click_dispatcher';

const modalArreteDetails = document.getElementById('fr-modal-arrete-details');
const modalArreteDelete = document.getElementById('fr-modal-arrete-delete');

if (modalArreteDetails) {
  registerClickRoute('.open-modal-arrete-details', (button, e) => {
    document.getElementById('fr-modal-arrete-details-arrete-name').textContent =
      button.dataset.name;
    const items = document.querySelectorAll('.item-arrete-details');
    items.forEach((item) => {
      item.classList.add('fr-hidden');
    });
    const itemToShow = document.getElementById('fr-modal-arrete-details-' + button.dataset.id);
    if (itemToShow) {
      itemToShow.classList.remove('fr-hidden');
    }
  });
}

if (modalArreteDelete) {
  registerClickRoute('.open-modal-arrete-delete', (button, e) => {
    document.getElementById('fr-modal-arrete-delete-arrete-name').textContent = button.dataset.name;
    document.getElementById('arrete_delete_form').action = button.dataset.url;
  });
}
