document.querySelectorAll('.partner_users_transfer_btn').forEach(swbtn => {
  swbtn.addEventListener('click', evt => {
    const target = evt.target
    document.querySelector('#fr-modal-user-transfer_username').innerHTML = target.getAttribute('data-username')
    document.querySelector('#fr-modal-user-transfer_userid').value = target.getAttribute('data-userid')
    document.querySelector('#user_c_form').addEventListener('submit', (e) => {
      document.querySelector('#user_transfer_form_submit').innerHTML = 'Transfert en cours...'
      document.querySelector('#user_transfer_form_submit').disabled = true
    })
  })
})
document.querySelectorAll('.partner-user-delete').forEach(swbtn => {
  swbtn.addEventListener('click', evt => {
    const target = evt.target
    document.querySelectorAll('.fr-modal-user-delete_username').forEach(userItem => {
      userItem.innerHTML = target.getAttribute('data-username')
    })
    document.querySelector('#fr-modal-user-delete_userid').value = target.getAttribute('data-userid')
    document.querySelector('#user_c_form').addEventListener('submit', (e) => {
      document.querySelector('#user_delete_form_submit').innerHTML = 'Suppression en cours...'
      document.querySelector('#user_delete_form_submit').disabled = true
    })
  })
})
