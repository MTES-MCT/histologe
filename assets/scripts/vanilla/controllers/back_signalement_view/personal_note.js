import { initTinyMCE } from '../../services/form/form_helper';

const personalNoteContainerSelector = '#signalement-personal-note-container';

export function reloadPersonalNoteEditor() {
  if (window.tinymce) {
    window.tinymce.get('personal-note-content')?.remove();
  }
  initTinyMCE('#personal-note-content');
}

document.addEventListener('click', (event) => {
  const editButton = event.target.closest('.signalement-personal-note-edit-btn');
  if (editButton) {
    const container = editButton.closest(personalNoteContainerSelector);
    container?.querySelector('.signalement-personal-note-display')?.classList.add('fr-hidden');
    container?.querySelector('.signalement-personal-note-edit')?.classList.remove('fr-hidden');
    return;
  }

  const cancelButton = event.target.closest('.signalement-personal-note-cancel-btn');
  if (cancelButton) {
    const container = cancelButton.closest(personalNoteContainerSelector);
    container?.querySelector('.signalement-personal-note-edit')?.classList.add('fr-hidden');
    container?.querySelector('.signalement-personal-note-display')?.classList.remove('fr-hidden');
  }
});
