const searchTerritoryForm = document.getElementById('search-territory-form')

if(searchTerritoryForm){
    searchTerritoryForm.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', function(){
            document.getElementById('page').value = 1;
            searchTerritoryForm.submit();
        });
    });
    searchTerritoryForm.addEventListener('submit', function(event){
        document.getElementById('page').value = 1;
    });
}