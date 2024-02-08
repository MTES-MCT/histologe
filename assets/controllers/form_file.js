function histoUpdateValueFromData(elementName, elementData, target) {
  document.querySelector(elementName).value = target.getAttribute(elementData)
}

document.querySelectorAll('.btn-signalement-file-edit').forEach(swbtn => {
  swbtn.addEventListener('click', evt => {
    const target = evt.target

    if ( target.getAttribute('data-type') === 'photos') {
      document.querySelector('.fr-modal-file-edit_type').innerHTML = ' la photo'
    } else {
      document.querySelector('.fr-modal-file-edit_type').innerHTML = ' le document'
    }
    document.querySelector('.fr-modal-file-edit_filename').innerHTML = target.getAttribute('data-filename')
    document.querySelector('.fr-modal-file-edit_infos').innerHTML = 'Ajouté le '+target.getAttribute('data-createdAt')
    + ' par '+ target.getAttribute('data-partner')+target.getAttribute('data-user')

    histoUpdateValueFromData('#file_edit_fileid', 'data-fileid', target)

    const documentTypes = JSON.parse(target.getAttribute('data-document-types'));


    const selectedDocumentType = target.getAttribute('data-documentType');

    let selectBox = document.querySelector('#document-type-select');
    selectBox.innerHTML = '';
    let option = new Option('Sélectionnez un type', '');
    if ('' === selectedDocumentType) {
        option.selected = true;
    }
    selectBox.appendChild(option);
    for (let key in documentTypes) {
      if (documentTypes.hasOwnProperty(key)) {
        let label = documentTypes[key];
        let option = new Option(label, key);
        if (key === selectedDocumentType) {
            option.selected = true;
        }
        selectBox.appendChild(option);
      }
    }
    document.querySelector('#user_edit_form').addEventListener('submit', (e) => {
      histoUpdateSubmitButton('#user_edit_form_submit', 'Edition en cours...')
    })
  })
})
