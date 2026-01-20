document.getElementById('form-message-usager')?.addEventListener('submit', function () {
  const submitBtn = document.getElementById('form_finish_submit');
  if (submitBtn) {
    submitBtn.disabled = true;
  }
});
