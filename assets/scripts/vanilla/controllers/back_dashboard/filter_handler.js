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
    const url = new URL(window.location.href);
    const isCurrentPressed = e.target.getAttribute('aria-pressed') !== 'true'; // event received after update of aria-pressed
    url.searchParams.set('mesDossiersMessagesUsagers', isCurrentPressed ? '0' : '1');
    window.location.href = url.toString();
  });

  const mySignalementsAverifierButton = document.getElementById('mySignalementsAverifierButton');
  mySignalementsAverifierButton?.addEventListener('click', (e) => {
    const url = new URL(window.location.href);
    const isCurrentPressed = e.target.getAttribute('aria-pressed') !== 'true'; // event received after update of aria-pressed
    url.searchParams.set('mesDossiersAverifier', isCurrentPressed ? '0' : '1');
    window.location.href = url.toString();
  });
}
