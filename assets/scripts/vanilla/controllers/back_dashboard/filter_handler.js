export default function initFilterHandler() {
  const selectTerritoires = document?.getElementById('filter-territoires');
  selectTerritoires?.addEventListener('change', function () {
    const url = new URL(window.location.href);
    const selected = this.value;

    if (selected !== 'all') {
      url.searchParams.set('territoireId', selected);
    } else {
      url.searchParams.delete('territoireId');
    }

    if (window.location.hash) {
      url.hash = window.location.hash;
    }
    window.location.href = url.toString();
  });

  const mySignalementsMessagesUsagersButton = document.getElementById('mySignalementsMessagesUsagersButton');
  mySignalementsMessagesUsagersButton?.addEventListener('click', () => {
      const url = new URL(window.location.href);
      const current = url.searchParams.get('mesDossiersMessagesUsagers') === '1';
      url.searchParams.set('mesDossiersMessagesUsagers', current ? '0' : '1');
      window.location.href = url.toString();
  });

  const mySignalementsAverifierButton = document.getElementById('mySignalementsAverifierButton');
  mySignalementsAverifierButton?.addEventListener('click', () => {
      const url = new URL(window.location.href);
      const current = url.searchParams.get('mesDossiersAverifier') === '1';
      url.searchParams.set('mesDossiersAverifier', current ? '0' : '1');
      window.location.href = url.toString();
  });
}
