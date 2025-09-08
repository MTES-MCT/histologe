document.querySelectorAll('.btn-delete-user-api-permission').forEach((btn) => {
  btn.addEventListener('click', () => {
    document.querySelector('#user_api_permission_delete_form').action = btn.dataset.url;
    document.querySelector('#fr-modal-user-api-permission-delete-description').innerHTML = btn.dataset.description;
  });
});

const partnerSelect = document.querySelector('#user_api_permission_partner');
const territoryFilter = document.querySelector('#user_api_permission_territoryFilter');
if (partnerSelect) {
  const allPartnersOptions = Array.from(partnerSelect.options);
  
  function filterPartners() {
      const selectedTerritoryId = territoryFilter.value;
      partnerSelect.innerHTML = '';
      const emptyOption = allPartnersOptions.find(option => option.value === '');
      partnerSelect.appendChild(emptyOption.cloneNode(true));
      
      allPartnersOptions.forEach(option => {
          const territoryId = option.getAttribute('data-territoryid');
          if (!selectedTerritoryId || territoryId === selectedTerritoryId) {
              partnerSelect.appendChild(option.cloneNode(true));
          }
      });
  }
  territoryFilter.addEventListener('change', filterPartners);
  filterPartners();
}
