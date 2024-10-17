const searchZoneForm = document.getElementById('search-zone-form')

if(searchZoneForm){
    searchZoneForm.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', function(){
            searchUserForm.submit();
        });
    });
    searchZoneForm.addEventListener('submit', function(){
        document.getElementById('page').value = 1;
    });
}