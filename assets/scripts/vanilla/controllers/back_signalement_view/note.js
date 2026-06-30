import { initTinyMCE } from '../../services/form/form_helper';

const noteContainerSelector = '#signalement-note-container';

export function reloadNoteEditor() {
  if (window.tinymce) {
    window.tinymce.get('note-content')?.remove();
  }
  initTinyMCE('#note-content');
}

document.addEventListener('click', (event) => {
  const editButton = event.target.closest('.signalement-note-edit-btn');
  if (editButton) {
    const container = editButton.closest(noteContainerSelector);
    container?.querySelector('.signalement-note-display')?.classList.add('fr-hidden');
    container?.querySelector('.signalement-note-edit')?.classList.remove('fr-hidden');
    return;
  }

  const cancelButton = event.target.closest('.signalement-note-cancel-btn');
  if (cancelButton) {
    const container = cancelButton.closest(noteContainerSelector);
    container?.querySelector('.signalement-note-edit')?.classList.add('fr-hidden');
    container?.querySelector('.signalement-note-display')?.classList.remove('fr-hidden');
  }
});
