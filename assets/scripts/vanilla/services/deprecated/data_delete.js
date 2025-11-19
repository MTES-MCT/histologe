import { jsonResponseHandler } from '../../services/component/component_json_response_handler';

//gère la suppression des affectations et des suivis
document.addEventListener('click', (event) => {
  const actionBtn = event.target.closest('[data-delete]');
  
  if (!actionBtn) return;
  
  event.preventDefault();
  
  if (confirm('Voulez-vous vraiment supprimer cet élément ?')) {
    const formData = new FormData();
    formData.append('_token', actionBtn.getAttribute('data-token'));
    fetch(actionBtn.getAttribute('data-delete'), {
      method: 'POST',
      body: formData,
    }).then((r) => {
      if (r.ok) {
        jsonResponseHandler(r);
      }
    });
  }
});
