export default function sortHandler(loadPanelContent) {
  document.addEventListener('change', async function (e) {
    const select = e.target;
    if (!(select instanceof HTMLElement) || !select.id?.includes('order-type')) return;

    const divBodyId = select.dataset.sortBody;
    const divElement = document.getElementById(divBodyId);
    if (!divElement) {
      console.error(`Aucune div trouvé avec l'id "${divBodyId}"`);
      return;
    }

    const [sortBy, orderBy] = select.value.split('-');
    divElement.dataset.url =
      divElement.dataset.url.split('?')[0] + `?sortBy=${sortBy}&orderBy=${orderBy}`;

    await loadPanelContent(divElement);

    const newSelect = document.getElementById(select.id);
    if (newSelect) {
      newSelect.value = `${sortBy}-${orderBy}`;
      Array.from(newSelect.options).forEach((option) => {
        option.removeAttribute('selected');
        if (option.value === newSelect.value) {
          option.setAttribute('selected', 'selected');
        }
      });
    }
  });
}
