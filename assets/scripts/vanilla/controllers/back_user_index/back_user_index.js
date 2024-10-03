const searchUserForm = document.getElementById('search-user-form')

if(searchUserForm){
    searchUserForm.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', function(){
            if(select.name === 'territory'){
                searchUserForm.querySelectorAll('input[name="partner[]"]').forEach((input) => {
                    input.checked = false;
                });
            }
            searchUserForm.submit();
        });
    });
    searchUserForm.querySelectorAll('.search-checkbox-container').forEach((select) => {
        select.addEventListener('searchCheckboxChange', function(){
            searchUserForm.submit();
        });
    });
    searchUserForm.addEventListener('submit', function(){
        document.getElementById('page').value = 1;
    });
}