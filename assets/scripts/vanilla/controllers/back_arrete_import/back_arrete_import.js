const importArreteContainer = document.querySelector('#import-arrete-container');
if (importArreteContainer) {
  const fileInput = importArreteContainer.querySelector('input[type="file"]');
  const form = fileInput?.closest('form');
  if (fileInput && form) {
    fileInput.addEventListener('change', () => {
      if (fileInput.files.length > 0) {
        form.submit();
      }
    });
  }
}
