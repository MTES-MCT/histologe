document?.querySelector('#btn-display-all-suivis')?.addEventListeners('click touchdown', (e) => {
    e.preventDefault()
    document.querySelectorAll('.suivi-item').forEach(item => {
        item.classList.remove('fr-hidden')
    })
    document.querySelector('#btn-display-all-suivis').classList.add('fr-hidden')
})