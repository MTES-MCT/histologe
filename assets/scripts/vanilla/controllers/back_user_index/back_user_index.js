const searchUserForm = document.getElementById('search-user-form')

if(searchUserForm){
    searchUserForm.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', function(){
            if(select.name === 'territory'){
                searchUserForm.querySelectorAll('input[name="partner[]"]').forEach((input) => {
                    input.checked = false;
                });
            }
            document.getElementById('page').value = 1;
            searchUserForm.submit();
        });
    });
    searchUserForm.querySelectorAll('.search-checkbox-container').forEach((select) => {
        select.addEventListener('searchCheckboxChange', function(){
            document.getElementById('page').value = 1;
            searchUserForm.submit();
        });
    });
    searchUserForm.addEventListener('submit', function(){
        document.getElementById('page').value = 1;
    });
}