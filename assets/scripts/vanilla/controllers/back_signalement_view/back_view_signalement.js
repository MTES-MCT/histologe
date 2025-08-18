import { loadWindowWithLocalStorage } from '../../services/ui/list_filter_helper';
import { btnSignalementFileDeleteAddEventListeners } from '../../services/file/file_delete';
import { btnSignalementFileEditAddEventListeners } from '../../controllers/back_signalement_edit_file/back_signalement_edit_file';

if (document?.querySelector('.fr-breadcrumb.can-fix')) {
  window.onscroll = function () {
    const yPos = window.scrollY;
    if (yPos > 150) {
      document?.querySelector('.fr-breadcrumb.can-fix').classList.add('fixed');
    } else {
      document?.querySelector('.fr-breadcrumb.can-fix').classList.remove('fixed');
    }
  };
}

document.querySelectorAll('.open-modal-reinit-affectation').forEach((button) => {
  button.addEventListener('click', (e) => {
    document.querySelector('#fr-modal-reinit-affectation .partner-nom').textContent =
      e.target.dataset.partnerNom;
    document.querySelector('form#fr-modal-reinit-affectation-form').action =
      e.target.dataset.action;
  });
});

document.querySelectorAll('.btn-list-all-photo-situation').forEach((button) => {
  button.addEventListener('click', (e) => {
    e.preventDefault();
    button.setAttribute('disabled', 'disabled');
    button.textContent = 'Chargement...';
    const url = e.target.dataset.url;
    fetch(url, { method: 'GET' }).then((response) => {
      if (response.ok) {
        response.json().then((data) => {
          document.querySelectorAll('.container-situation').forEach((container) => {
            container.innerHTML = data.html;
            initTippy();
            openPhotoAlbumAddEventListeners();
            btnSignalementFileEditAddEventListeners();
            btnSignalementFileDeleteAddEventListeners();
          });
        });
      }
    });
  });
});

function initTippy() {
  tippy('.part-infos-hover', {
    content(reference) {
      const id = reference.getAttribute('data-template');
      const template = document.getElementById(id);
      return template.innerHTML;
    },
    allowHTML: true,
    interactive: true,
    hideOnClick: true,
    theme: 'light-border',
    arrow: true,
    placement: 'bottom',
    maxWidth: '100%',
  });
}

if (typeof tippy !== 'undefined') {
  initTippy();
}
openPhotoAlbumAddEventListeners();

function openPhotoAlbumAddEventListeners() {
  document?.querySelectorAll('.open-photo-album')?.forEach((btn) => {
    const swipeId = btn.getAttribute('data-id');
    btn.addEventListeners('click touchdown', () => {
      document?.querySelectorAll('.photos-album')?.forEach((element) => {
        element.classList?.remove('fr-hidden');
        displayPhotoAlbum(swipeId);
      });
    });
  });
}

/* global histoPhotoIds */

document?.querySelector('#btn-display-all-suivis')?.addEventListeners('click touchdown', (e) => {
  e.preventDefault();
  document.querySelectorAll('.suivi-item').forEach((item) => {
    item.classList.remove('fr-hidden');
  });
  document.querySelector('#btn-display-all-suivis').classList.add('fr-hidden');
});

document?.querySelectorAll('.photos-album-btn-close')?.forEach((btn) => {
  btn.addEventListeners('click touchdown', () => {
    document?.querySelectorAll('.photos-album')?.forEach((element) => {
      element.classList?.add('fr-hidden');
    });
  });
});
document?.querySelectorAll('.photos-album-swipe')?.forEach((btn) => {
  const swipeDirection = Number(btn.getAttribute('data-direction'));

  btn.addEventListeners('click touchdown', () => {
    let currentId = null;
    document?.querySelectorAll('.photos-album-image-item.loop-current')?.forEach((element) => {
      currentId = Number(element.getAttribute('data-id'));
    });
    let newIndex = histoPhotoIds.indexOf(currentId);
    newIndex += Number(swipeDirection);
    if (newIndex < 0) {
      newIndex = histoPhotoIds.length - 1;
    }
    if (newIndex > histoPhotoIds.length - 1) {
      newIndex = 0;
    }
    displayPhotoAlbum(histoPhotoIds[newIndex]);
  });
});
document?.querySelectorAll('.photos-album-main-btn-edit')?.forEach((btn) => {
  btn.addEventListeners('click touchdown', () => {
    const photoId = btn.dataset.id;
    btn.classList?.add('fr-hidden');
    document
      ?.querySelector('.photos-album-list-btn-edit[data-id="' + photoId + '"]')
      ?.classList?.remove('fr-hidden');
  });
});
document?.querySelectorAll('.photo-album-rotate-left-btn')?.forEach((btn) => {
  btn.addEventListeners('click touchdown', () => {
    const photoId = btn.dataset.id;
    rotatePhotoAlbumImage(photoId, 'left');
  });
});
document?.querySelectorAll('.photo-album-rotate-right-btn')?.forEach((btn) => {
  btn.addEventListeners('click touchdown', () => {
    const photoId = btn.dataset.id;
    rotatePhotoAlbumImage(photoId, 'right');
  });
});
document?.querySelectorAll('.photo-album-save-rotation')?.forEach((btn) => {
  btn.addEventListeners('click touchdown', () => {
    const photoId = btn.dataset.id;
    const rotate = document.querySelector('.photos-album-image[data-id="' + photoId + '"]').dataset
      .rotate;
    const action = btn.dataset.action;
    const form = document.querySelector('#form-save-file-rotation');
    form.querySelector('input[name="rotate"]').value = rotate;
    form.action = action;
    form.submit();
  });
});
document?.querySelectorAll('.photos-album-btn-zoom-in')?.forEach((btn) => {
  btn.addEventListener('click', () => {
    setZoom(btn.dataset.id, true);
  });
});

document?.querySelectorAll('.photos-album-btn-zoom-out')?.forEach((btn) => {
  btn.addEventListener('click', () => {
    setZoom(btn.dataset.id, false);
  });
});

const setZoom = (photoId, isZoomIn) => {
  const img = document.querySelector('.photos-album-image[data-id="' + photoId + '"]');
  let scale = parseFloat(img.dataset.scale) || 1;

  scale = isZoomIn ? Math.min(scale + 0.25, 4) : Math.max(scale - 0.25, 1);
  img.dataset.scale = scale;
  img.style.transformOrigin = 'left top';
  img.style.transform = `scale(${scale})`;

  // Center inside the scroll zone
  let parent = img.parentElement;
  parent.scrollLeft = (parent.scrollWidth - parent.clientWidth) / 2;
  parent.scrollTop = (parent.scrollHeight - parent.clientHeight) / 2;

  // correct transform scale with margin if necessary
  let newHeight = img.height * scale;
  let newWidth = img.width * scale;

  if (newHeight > parent.clientHeight && newWidth < parent.clientWidth) {
    let diffW = newWidth - img.width;
    img.style.marginLeft = '-' + diffW + 'px';
  }
  if (newHeight < parent.clientHeight && newWidth > parent.clientWidth) {
    let diffH = newHeight - img.height;
    img.style.marginTop = '-' + diffH + 'px';
  }
  if (scale == 1) {
    img.style.marginLeft = '0px';
    img.style.marginTop = '0px';
  }
};

const resetZoom = (photoId) => {
  const img = document.querySelector('.photos-album-image[data-id="' + photoId + '"]');
  const scale = 1;
  img.dataset.scale = scale;
  img.style.transform = `scale(${scale})`;
  img.style.marginLeft = '0px';
  img.style.marginTop = '0px';
  img.style.transformOrigin = 'center center';
};

const displayPhotoAlbum = (photoId) => {
  document
    ?.querySelector('.photos-album-main-btn-edit[data-id="' + photoId + '"]')
    ?.classList?.remove('fr-hidden');
  document
    ?.querySelector('.photos-album-list-btn-edit[data-id="' + photoId + '"]')
    ?.classList?.add('fr-hidden');

  document?.querySelectorAll('.photos-album-image-item.loop-current')?.forEach((element) => {
    element.classList?.remove('loop-current');
    element.classList?.add('fr-hidden');
  });
  document
    ?.querySelectorAll('.photos-album-image-item[data-id="' + photoId + '"]')
    ?.forEach((element) => {
      element.classList?.add('loop-current');
      element.classList?.remove('fr-hidden');
      document.querySelector('.photos-album-image[data-id="' + photoId + '"]').dataset.rotate = 1;
      rotatePhotoAlbumImage(photoId, 'left');
      resetZoom(photoId);
    });
};
const rotatePhotoAlbumImage = (photoId, direction) => {
  resetZoom(photoId);
  const imgElement = document.querySelector('.photos-album-image[data-id="' + photoId + '"]');
  const parentElement = imgElement.parentElement;

  imgElement.dataset.rotate = parseInt(imgElement.dataset.rotate) + (direction === 'left' ? -1 : 1);
  if (imgElement.dataset.rotate > 3 || imgElement.dataset.rotate < -3) {
    imgElement.dataset.rotate = 0;
  }
  let rotation = 0;
  rotation += parseInt(imgElement.dataset.rotate) * 90;
  imgElement.style.transform = `rotate(${rotation}deg)`;
  if (rotation % 180 !== 0) {
    imgElement.style.width = parentElement.clientHeight + 'px';
    imgElement.style.height = parentElement.clientWidth + 'px';
  } else {
    imgElement.style.width = parentElement.clientWidth + 'px';
    imgElement.style.height = parentElement.clientHeight + 'px';
  }
  if (imgElement.dataset.rotate !== '0') {
    document
      .querySelector('.photo-album-save-rotation[data-id="' + photoId + '"]')
      .removeAttribute('disabled');
  } else {
    document
      .querySelector('.photo-album-save-rotation[data-id="' + photoId + '"]')
      .setAttribute('disabled', 'disabled');
  }
};

loadWindowWithLocalStorage('click', '[data-filter-list-signalement]', 'back_link_signalement_view');

document?.querySelectorAll('[data-fr-select-target]')?.forEach((t) => {
  const source = document?.querySelector('#' + t.getAttribute('data-fr-select-source'));
  const target = document?.querySelector('#' + t.getAttribute('data-fr-select-target'));
  t.addEventListeners('click touchdown', () => {
    [...source.selectedOptions].forEach((s) => {
      target.append(s);
    });
  });
});

document
  ?.querySelector('#signalement-affectation-form-submit')
  ?.addEventListeners('click touchdown', (e) => {
    e.preventDefault();
    e.target.disabled = true;
    e.target?.form?.querySelectorAll('option').forEach((o) => {
      o.selected = true;
    });
    document
      ?.querySelectorAll('#signalement-affectation-form-row,#signalement-affectation-loader-row')
      .forEach((el) => {
        el.classList.toggle('fr-hidden');
      });

    const formData = new FormData(e.target.form);
    fetch(e.target.getAttribute('formaction'), {
      method: 'POST',
      body: formData,
    }).then((r) => {
      if (r.ok) {
        window.location.reload(true);
      }
    });
  });

const modalsElement = document?.querySelectorAll(
  '#cloture-modal, #fr-modal-add-suivi, #refus-signalement-modal, #refus-affectation-modal'
);
modalsElement.forEach((modalElement) => {
  modalElement.addEventListener('submit', () => {
    const submitButton = modalElement.querySelector('.fr-modal--opened [type=submit]');
    if (submitButton) {
      submitButton.disabled = true;
    }
  });
});

document.querySelectorAll('button[data-cloture-type]').forEach((button) => {
  button.addEventListener('click', (e) => {
    const element = e.target;
    if (element && element?.dataset) {
      document.getElementById('cloture_type').value = element.dataset.clotureType;
    }
  });
});

let modalAddSuiviHasBeenOpened = false;
document?.getElementById('fr-modal-add-suivi')?.addEventListener('dsfr.disclose', () => {
  if (modalAddSuiviHasBeenOpened) {
    return;
  }
  modalAddSuiviHasBeenOpened = true;
  document.getElementById('add_suivi_isPublic').checked = false;
  document.getElementById('add_suivi_isPublic').dispatchEvent(new Event('change'));
  tinymce.get('add_suivi_description').setContent('');
  document.querySelectorAll('input[name="add_suivi[files][]"]').forEach((checkbox) => {
    checkbox.checked = false;
  });
});

document?.getElementById('add_suivi_isPublic')?.addEventListeners('change', (e) => {
  document.getElementById('signalement-add-suivi-submit').textContent = e.target.checked
    ? "Envoyer le suivi à l'usager"
    : 'Enregistrer le suivi interne';
});

document
  ?.getElementById('fr-modal-historique-affectation')
  ?.addEventListener('dsfr.disclose', (event) => {
    const signalementId = event.target.dataset.signalementId;

    document?.querySelectorAll('#signalement-historique-affectation-loader-row').forEach((el) => {
      el.classList.toggle('fr-hidden');
    });

    if (!signalementId) {
      console.warn('Aucun signalementId trouvé');
      return;
    }

    fetch('/bo/history/signalement/' + signalementId + '/affectations', {
      method: 'GET',
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.historyEntries) {
          if (data.historyEntries.length === 0) {
            const loadingMessage = document.getElementById(
              'fr-modal-historique-affectation-loading-message'
            );
            loadingMessage.textContent = "Il n'y a pas d'historique pour ce signalement";
            loadingMessage.classList.remove('fr-hidden');
          } else {
            const historyEntries = data.historyEntries;
            const loadingMessage = document.getElementById(
              'fr-modal-historique-affectation-loading-message'
            );
            loadingMessage.classList.add('fr-hidden');
            const modalContent = document.getElementById('fr-modal-historique-affectation-content');
            modalContent.innerHTML = '';

            for (const [partner, events] of Object.entries(historyEntries)) {
              const table = document.createElement('div');
              table.classList.add('fr-table--sm');
              table.classList.add('fr-table');

              const wrapper = document.createElement('div');
              wrapper.classList.add('fr-table__wrapper');
              table.appendChild(wrapper);

              const container = document.createElement('div');
              container.classList.add('fr-table__container');
              wrapper.appendChild(container);

              const tableContent = document.createElement('div');
              tableContent.classList.add('fr-table__content');
              container.appendChild(tableContent);

              const tableContentTable = document.createElement('table');
              tableContentTable.classList.add('fr-table__content');
              tableContent.appendChild(tableContentTable);

              const tableContentTitle = document.createElement('caption');
              tableContentTitle.textContent = partner;
              tableContentTable.appendChild(tableContentTitle);

              const tableHeader = document.createElement('thead');
              tableHeader.innerHTML = `
                            <tr>
                                <th class="fr-w-15">Date</th>
                                <th>Action</th>
                            </tr>
                        `;
              tableContentTable.appendChild(tableHeader);

              const tableBody = document.createElement('tbody');
              events.forEach((event) => {
                const row = document.createElement('tr');
                const dateCell = document.createElement('td');
                const dateObj = new Date(event.Date);
                const formattedDate =
                  dateObj.toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                  }) +
                  ' à ' +
                  dateObj.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                dateCell.textContent = formattedDate;
                row.appendChild(dateCell);

                const actionCell = document.createElement('td');
                actionCell.textContent = event.Action;
                row.appendChild(actionCell);

                tableBody.appendChild(row);
              });

              tableContentTable.appendChild(tableBody);
              modalContent.appendChild(table);
            }
          }
        } else {
          console.warn('Erreur de récupération des données :', data.response);
        }
      })
      .catch(function (err) {
        console.warn('Something went wrong.', err);
      });
  });

const submitModalDuplicateAddresses = document.getElementById(
  'btn-submit-modal-duplicate-addresses'
);
if (submitModalDuplicateAddresses) {
  submitModalDuplicateAddresses.addEventListener('click', function (event) {
    event.preventDefault();
    const urlToRedirect = this.dataset.url;
    const dismissCheckbox = document.getElementById('dismiss-modal-duplicate-addresses');
    if (dismissCheckbox && dismissCheckbox.checked) {
      this.setAttribute('disabled', 'disabled');
      const form = document.getElementById('form-modal-duplicate-addresses');
      const formData = new FormData(form);
      fetch(form.action, {
        method: 'POST',
        body: formData,
      }).then((response) => {
        window.location.href = urlToRedirect;
      });
    } else {
      window.location.href = urlToRedirect;
    }
  });
}
