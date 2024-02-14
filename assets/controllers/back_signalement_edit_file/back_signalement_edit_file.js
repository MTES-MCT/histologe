function histoUpdateDesordreSelect(target, selectedDocumentType) {
  let desordreSelectBox = document.querySelector('#desordre-slug-select');
  if ('SITUATION' === selectedDocumentType ){
    const selectedDesordreSlug = target.getAttribute('data-desordreSlug')
    let desordres = JSON.parse(target.getAttribute('data-signalement-desordres'))
    desordreSelectBox.innerHTML = '';
    let option = new Option('Sélectionnez le désordre associé', '');
    if ('' === selectedDesordreSlug) {
        option.selected = true;
    }
    desordreSelectBox.appendChild(option);
    for (let key in desordres) {
      if (desordres.hasOwnProperty(key)) {
        let label = desordres[key];
        let option = new Option(label, key);
        if (key.startsWith(selectedDesordreSlug) && '' !== selectedDesordreSlug) {
            option.selected = true;
        }
        desordreSelectBox.appendChild(option);
      }
    }
    desordreSelectBox.classList.remove('fr-hidden')
  }else{
    desordreSelectBox.classList.add('fr-hidden')      
  }
}

document.querySelectorAll('.btn-signalement-file-edit').forEach(swbtn => {
  swbtn.addEventListener('click', evt => {
    evt.preventDefault()
    const target = evt.target

    if ( target.getAttribute('data-type') === 'photo') {
      document.querySelector('.fr-modal-file-edit-type').innerHTML = ' la photo'
    } else {
      document.querySelector('.fr-modal-file-edit-type').innerHTML = ' le document'
    }
    document.querySelector('.fr-modal-file-edit-filename').innerHTML = target.getAttribute('data-filename')
    document.querySelector('.fr-modal-file-edit-infos').innerHTML = 'Ajouté le '+target.getAttribute('data-createdAt')
    + ' par '+ target.getAttribute('data-partner-name')+target.getAttribute('data-user-name')
    document.querySelector('#file-edit-fileid').value = target.getAttribute('data-file-id')

    const documentTypes = JSON.parse(target.getAttribute('data-documentType-list'));
    const selectedDocumentType = target.getAttribute('data-documentType');
    let typeSelectBox = document.querySelector('#document-type-select');
    typeSelectBox.innerHTML = '';
    let option = new Option('Sélectionnez un type', '');
    if ('' === selectedDocumentType) {
        option.selected = true;
    }
    typeSelectBox.appendChild(option);
    for (let key in documentTypes) {
      if (documentTypes.hasOwnProperty(key)) {
        let label = documentTypes[key];
        let option = new Option(label, key);
        if (key === selectedDocumentType) {
            option.selected = true;
        }
        typeSelectBox.appendChild(option);
      }
    }
    histoUpdateDesordreSelect(target, selectedDocumentType)    

    document.querySelector('#document-type-select').addEventListener('change', function () {
      const selectedValue = this.value;
      histoUpdateDesordreSelect(target, selectedValue)   
    });
  })
})
