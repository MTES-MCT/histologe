document?.querySelectorAll('[data-reactive]')?.forEach(actionBtn => {
    actionBtn.addEventListeners('click touchdown', event => {
        event.preventDefault();
        if (confirm('Voulez-vous vraiment réactiver ce signalement ?')) {
            let formData = new FormData;
            formData.append('_token', actionBtn.getAttribute('data-token'))
            fetch(actionBtn.getAttribute('data-reactive'), {
                method: 'POST',
                body: formData,
            }).then(r => {
                if (r.ok) {
                    window.location = r.url
                }
            })
        }
    })
});