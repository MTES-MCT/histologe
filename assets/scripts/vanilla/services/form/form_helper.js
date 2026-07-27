Node.prototype.addEventListeners = function (eventNames, eventFunction) {
  for (const eventName of eventNames.split(' ')) {
    this.addEventListener(eventName, eventFunction);
  }
};

document.querySelectorAll('.fr-disable-button-when-submit')?.forEach((element) => {
  element.addEventListener('submit', () => {
    if (element.checkValidity()) {
      element.querySelectorAll('button[type=submit]')?.forEach((element) => {
        element.setAttribute('disabled', true);
      });
    }
  });
});

const autoSubmitElements = document.querySelectorAll('.fr-auto-submit');
autoSubmitElements.forEach((autoSubmitElements) => {
  autoSubmitElements.addEventListener('change', function () {
    document.getElementById('page').value = 1;
    this.form.submit();
  });
});

document.addEventListener('DOMContentLoaded', () => {
  initTinyMCE('textarea.editor:not([data-mention-partners])'); // toolbar sans partnersButton
  initTinyMCE('textarea.editor[data-mention-partners]'); // toolbar avec partnersButton
});

export function initTinyMCE(selector) {
  const editor = document.querySelector(selector);
  if (editor !== null) {
    let toolbar = 'undo redo | styleselect | bold italic | numlist bullist';
    if ('textarea.editor[data-mention-partners]' == selector) {
      toolbar += ' | partnersButton';
    }
    tinymce.init({
      selector: selector,
      browser_spellcheck: true,
      license_key: 'gpl',
      plugins: 'lists',
      toolbar: toolbar,
      ui_mode: 'split', // on attache le menu de suggestions à l'éditeur
      menubar: false,
      height: 320,
      setup: (ed) => {
        // les mentions ne concernent que les éditeurs qui portent data-mention-partners (suivis), lu par instance
        const partnersData = ed.getElement().dataset.mentionPartners;
        if (!partnersData) return;
        const partners = JSON.parse(partnersData);
        // pouvoir désactiver le menu si le suivi est visible bailleur ou usager
        const form = ed.getElement().closest('form');
        const usagerCheckbox = form.querySelector('[name$="[isVisibleForUsager]"]');
        const bailleurCheckbox = form.querySelector('[name$="[isVisibleForBailleur]"]');
        const isMentionBlocked = () => !!(usagerCheckbox?.checked || bailleurCheckbox?.checked);
        // Pour supprimer les mentions pré-existantes si le suivi est visible pour le bailleur ou l'usager
        const removeMentionsIfBlocked = () => {
          if (!isMentionBlocked()) return;
          ed.dom.select('span.mention').forEach((mentionNode) => ed.dom.remove(mentionNode));
        };
        usagerCheckbox?.addEventListener('change', removeMentionsIfBlocked);
        bailleurCheckbox?.addEventListener('change', removeMentionsIfBlocked);

        const getMatchedChars = (pattern) => {
          return partners.filter((p) => p.nom.toLowerCase().includes(pattern.toLowerCase()));
        };

        const buildMentionHtml = (partner) => {
          const span = document.createElement('span');
          span.className = 'mention';
          span.setAttribute('contenteditable', 'false');
          span.dataset.partnerId = partner.id;
          span.textContent = '@' + partner.nom;
          return '<strong>' + span.outerHTML + '</strong>&nbsp;';
        };

        ed.ui.registry.addMenuButton('partnersButton', {
          text: '@',
          fetch: (callback) => {
            const items = partners.map((char) => ({
              type: 'menuitem',
              text: char.nom,
              onAction: (_) => ed.insertContent(buildMentionHtml(char)),
            }));
            callback(items);
          },
          // pouvoir désactiver le menu si le suivi est visible bailleur ou usager
          onSetup: (api) => {
            const update = () => api.setEnabled(!isMentionBlocked());
            update();
            usagerCheckbox?.addEventListener('change', update);
            bailleurCheckbox?.addEventListener('change', update);
            return () => {
              usagerCheckbox?.removeEventListener('change', update);
              bailleurCheckbox?.removeEventListener('change', update);
            };
          },
        });
        ed.ui.registry.addAutocompleter('partners', {
          trigger: '@',
          minChars: 0,
          columns: 1,
          fetch: (pattern) => {
            // pouvoir désactiver le menu si le suivi est visible bailleur ou usager
            if (isMentionBlocked()) return Promise.resolve([]);
            return new Promise((resolve) => {
              const results = getMatchedChars(pattern).map((char) => ({
                type: 'autocompleteitem',
                value: char.id.toString(),
                text: char.nom,
              }));
              resolve(results);
            });
          },
          onAction: (autocompleteApi, rng, value) => {
            const partner = partners.find((p) => p.id.toString() === value);
            ed.selection.setRng(rng);
            ed.insertContent(buildMentionHtml(partner));
            autocompleteApi.hide();
          },
        });
      },
    });
  }
}

export function reloadTinyMCE(selector) {
  if (window.tinymce) {
    tinymce.remove();
  }

  initTinyMCE(selector);
}
