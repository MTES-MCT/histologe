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

  const mySignalementsMessagesUsagersButton = document.getElementById(
    'mySignalementsMessagesUsagersButton'
  );
  mySignalementsMessagesUsagersButton?.addEventListener('click', (e) => {
    e.preventDefault();
    const url = new URL(window.location.href);
    const current = url.searchParams.get('mesDossiersMessagesUsagers');
    const newValue = current === '1' ? '0' : '1';
    url.searchParams.set('mesDossiersMessagesUsagers', newValue);
    if (window.location.href === url.toString()) {
      window.location.reload();
    } else {
      window.location.href = url.toString();
    }
  });

  const mySignalementsAverifierButton = document.getElementById('mySignalementsAverifierButton');
  mySignalementsAverifierButton?.addEventListener('click', (e) => {
    e.preventDefault();
    const url = new URL(window.location.href);
    const current = url.searchParams.get('mesDossiersAverifier');
    const newValue = current === '1' ? '0' : '1';
    url.searchParams.set('mesDossiersAverifier', newValue);
    if (window.location.href === url.toString()) {
      window.location.reload();
    } else {
      window.location.href = url.toString();
    }
  });

  const mySignalementsActiviteRecenteButton = document.getElementById(
    'mySignalementsActiviteRecenteButton'
  );
  mySignalementsActiviteRecenteButton?.addEventListener('click', (e) => {
    e.preventDefault();
    const url = new URL(window.location.href);
    const current = url.searchParams.get('mesDossiersActiviteRecente');
    const newValue = current === '1' ? '0' : '1';
    url.searchParams.set('mesDossiersActiviteRecente', newValue);
    if (window.location.href === url.toString()) {
      window.location.reload();
    } else {
      window.location.href = url.toString();
    }
  });
}
