const modalArreteDetails = document.getElementById('fr-modal-arrete-details');
const modalArreteDelete = document.getElementById('fr-modal-arrete-delete');

document.getElementById('table-list-results')?.addEventListener('click', (e) => {
  const detailsButton = e.target.closest('.open-modal-arrete-details');
  if (detailsButton && modalArreteDetails) {
    document.getElementById('fr-modal-arrete-details-arrete-name').textContent =
      detailsButton.dataset.name;
    const items = document.querySelectorAll('.item-arrete-details');
    items.forEach((item) => {
      item.classList.add('fr-hidden');
    });
    const itemToShow = document.getElementById(
      'fr-modal-arrete-details-' + detailsButton.dataset.id
    );
    if (itemToShow) {
      itemToShow.classList.remove('fr-hidden');
    }
    return;
  }

  const deleteButton = e.target.closest('.open-modal-arrete-delete');
  if (deleteButton && modalArreteDelete) {
    document.getElementById('fr-modal-arrete-delete-arrete-name').textContent =
      deleteButton.dataset.name;
    document.getElementById('arrete_delete_form').action = deleteButton.dataset.url;
  }
});
