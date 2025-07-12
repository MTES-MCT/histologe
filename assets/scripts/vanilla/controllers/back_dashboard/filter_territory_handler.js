export default function initFilterTerritoryHandler() {
  const selectTerritoires = document?.getElementById('filter-territoires');
  selectTerritoires?.addEventListener('change', function () {
    const selected = this.value;
    let url = window.location.pathname;

    if (selected !== 'all') {
      url += '?territoireId=' + encodeURIComponent(selected);
    }

    if (window.location.hash) {
      url += window.location.hash;
    }

    window.location.href = url;
  });
}
