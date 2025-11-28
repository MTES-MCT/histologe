import { applyFilter } from '../../controllers/back_signalement_view/toggle-suivi-auto';

const flashMessagesContainer = document.getElementById('flash-messages-live-container');

export function jsonResponseHandler(response) {
  response.json().then((response) => {
    jsonResponseProcess(response);
  });
}

export function jsonResponseProcess(response) {
  if (response.redirect) {
    window.location.href = response.url;
    window.location.reload();
  } else if (response.stayOnPage) {
    if (response.flashMessages) {
      response.flashMessages.forEach((flashMessage) => {
        addFlashMessage(flashMessage);
      });
    }
    if (response.htmlTargetContents) {
      response.htmlTargetContents.forEach((htmlTargetContent) => {
        const targetElement = document.querySelector(htmlTargetContent.target);
        if (targetElement) {
          targetElement.innerHTML = htmlTargetContent.content;
        }
      });
    }
    if (response.closeModal) {
      const openModalElement = document.querySelector('.fr-modal--opened');
      if (openModalElement) {
        dsfr(openModalElement).modal.conceal();
      }
    }
    if (response.functions) {
      response.functions.forEach((fn) => {
        switch (fn.name) {
          case 'applyFilter':
            applyFilter();
            break;
        }
      });
    }
  } else {
    location.reload();
    window.scrollTo(0, 0);
  }
}

export function jsonResponseProcess(response) {
  if (response.redirect) {
    window.location.href = response.url;
    window.location.reload();
  } else if(response.stayOnPage){
    if(response.flashMessages){
      response.flashMessages.forEach((flashMessage) => {
        addFlashMessage(flashMessage);
      });
    }
    if(response.htmlTargetContents){
      response.htmlTargetContents.forEach((htmlTargetContent) => {
        const targetElement = document.querySelector(htmlTargetContent.target);
        if(targetElement){
          targetElement.innerHTML = htmlTargetContent.content;
        }
      });
    }
    if(response.closeModal){
      const openModalElement = document.querySelector('.fr-modal--opened');
      if(openModalElement){
        dsfr(openModalElement).modal.conceal();
      }
    }
    if(response.functions){
      response.functions.forEach((fn) => {
        switch(fn.name){
          case 'applyFilter':
            applyFilter();
            break;
        }
      });
    }
  } else {
    location.reload();
    window.scrollTo(0, 0);
  }
}

export function addFlashMessage(flashMessage) {
  const divElement = document.createElement('div');
  divElement.classList.add('fr-notice', `fr-notice--${flashMessage.type}`);
  divElement.setAttribute('role', 'alert');
  divElement.innerHTML = `
      <div class="fr-container">
          <div class="fr-notice__body">
              <p>
                <span class="fr-notice__title">${sanitizeMessage(flashMessage.title)}</span>
                <span class="fr-notice__text">${sanitizeMessage(flashMessage.message)}</span>
              </p>
              <button title="Masquer le message" type="button" class="fr-btn--close fr-btn">Masquer le message</button>
          </div>
      </div>
    `;
  flashMessagesContainer.appendChild(divElement);
}

function sanitizeMessage(message) {
  // Échappe tous les caractères HTML
  const div = document.createElement('div');
  div.textContent = message;
  let sanitized = div.innerHTML;

  // Réautorise uniquement les balises <br> et <br/>
  sanitized = sanitized.replace(/&lt;br\s*\/?&gt;/gi, '<br>');

  return sanitized;
}
