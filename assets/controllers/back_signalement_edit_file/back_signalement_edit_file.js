function histoUpdateDesordreSelect(target, selectedDocumentType) {
  let desordreSelectBox = document.querySelector('#desordre-slug-select');
  let desordres = JSON.parse(target.getAttribute('data-signalement-desordres'))
  if ('PHOTO_SITUATION' === selectedDocumentType 
    && Object.keys(desordres).length > 0){
    const selectedDesordreSlug = target.getAttribute('data-desordreSlug')
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

function histoCreateMiniatureImage(target) {
  histoDeleteMiniatureImage()
  const filename = target.getAttribute('data-filename')
  const uuidSignalement = target.getAttribute('data-signalement-uuid')
  const url = window.location.origin + '/_up/' + filename +'/'  + uuidSignalement + '?variant=thumb'
  const modalFileEditType = document.querySelector('#fr-modal-edit-file-miniature');
  const newDiv = '<div id="fr-modal-edit-file-miniature-image" class="fr-col-6 fr-col-offset-3 fr-col-md-2 fr-col-offset-md-5 fr-px-5w fr-py-8w fr-rounded fr-border fr-text--center" style="background: url(\'' + url + '\') no-repeat center center/cover;"></div>';
  modalFileEditType.insertAdjacentHTML('afterbegin', newDiv);
}
function histoDeleteMiniatureImage() {
  const parentElement = document.querySelector('#fr-modal-edit-file-miniature');
  const addedDiv = document.querySelector('#fr-modal-edit-file-miniature-image');
  if (addedDiv) {
      parentElement.removeChild(addedDiv);
  }
}

document.querySelectorAll('.btn-signalement-file-edit').forEach(swbtn => {
  swbtn.addEventListener('click', evt => {
    evt.preventDefault()
    const target = evt.target

    if ( target.getAttribute('data-type') === 'photo') {
      document.querySelector('.fr-modal-file-edit-type').innerHTML = ' la photo'
      histoCreateMiniatureImage(target)
    } else {
      document.querySelector('.fr-modal-file-edit-type').innerHTML = ' le document'
      histoDeleteMiniatureImage()
    }
    document.querySelector('.fr-modal-file-edit-filename').innerHTML = target.getAttribute('data-filename')
    document.querySelector('.fr-modal-file-edit-infos').innerHTML = 'Ajouté le '+target.getAttribute('data-createdAt')
    + ' par '+ target.getAttribute('data-partner-name')+target.getAttribute('data-user-name')
    document.querySelector('#file-edit-fileid').value = target.getAttribute('data-file-id')

    const selectedDocumentType = target.getAttribute('data-documentType'); 
    document.querySelector('#fileDescription').value = target.getAttribute('data-description')
    document.querySelector('#fr-modal-edit-file-description').classList.remove('fr-hidden')

    const documentTypes = JSON.parse(target.getAttribute('data-documentType-list'));
    let typeSelectBox = document.querySelector('#document-type-select');
    typeSelectBox.innerHTML = '';
    if (documentTypes !== null ) {
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

    } else {
      let option = new Option(selectedDocumentType, selectedDocumentType);
      option.selected = true;
      typeSelectBox.appendChild(option);
      typeSelectBox.classList.add('fr-hidden')
    }
  })
})
