import { initTinyMCE } from '../../services/form/form_helper';
import { registerClickRoute } from '../../services/ui/click_dispatcher';

const personalNoteContainerSelector = '#signalement-personal-note-container';

export function reloadPersonalNoteEditor() {
  if (window.tinymce) {
    window.tinymce.get('personal-note-content')?.remove();
  }
  initTinyMCE('#personal-note-content');
}

registerClickRoute('.signalement-personal-note-edit-btn', (editButton, event) => {
  const container = editButton.closest(personalNoteContainerSelector);
  container?.querySelector('.signalement-personal-note-display')?.classList.add('fr-hidden');
  container?.querySelector('.signalement-personal-note-edit')?.classList.remove('fr-hidden');
});

registerClickRoute('.signalement-personal-note-cancel-btn', (cancelButton, event) => {
  const container = cancelButton.closest(personalNoteContainerSelector);
  container?.querySelector('.signalement-personal-note-edit')?.classList.add('fr-hidden');
  container?.querySelector('.signalement-personal-note-display')?.classList.remove('fr-hidden');
});
