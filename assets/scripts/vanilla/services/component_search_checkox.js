document.querySelectorAll('.search-checkbox-container')?.forEach(element => {
    searchCheckboxCompleteInputValue(element)
    let input = element.querySelector('input[type="text"]')
    let checkboxesContainer = element.querySelector('.search-checkbox')
    //init order
    checkboxesContainer.querySelectorAll('.fr-fieldset__element').forEach((checkbox, index) => {
        checkbox.setAttribute('data-order', index)
    })
    //show choices on focus
    input.addEventListener('focus', function() {
        checkboxesContainer.querySelectorAll('.fr-fieldset__element').forEach((checkbox, index) => {
            checkbox.style.display = '';
        })
        checkboxesContainer.style.display = 'block';
        checkboxesContainer.scrollTop = 0;
        searchCheckboxOrderCheckboxes(element)
        input.value = '';
    });
    //filter choices on input keyup
    input.addEventListener('keyup', function() {
        let value = input.value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
        checkboxesContainer.querySelectorAll('.fr-fieldset__element').forEach((checkbox) => {
            let text = checkbox.querySelector('label').textContent.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
            if(text.includes(value)){
                checkbox.style.display = '';
            }else{
                checkbox.style.display = 'none';
            }
        });
    });
    //hide choices on click outside
    document.addEventListener('click', function(event) {
        if (!input.contains(event.target) && !checkboxesContainer.contains(event.target)) {
            checkboxesContainer.style.display = 'none';
            searchCheckboxCompleteInputValue(element)
        }
    });
    //reorder on uncheck
    checkboxesContainer.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
        checkbox.addEventListener('change', function() {
            if(!checkbox.checked && checkbox.closest('.fr-fieldset__element').classList.contains('topped')){
                searchCheckboxOrderCheckboxes(element)
            }
        });
    });
})

function searchCheckboxCompleteInputValue(element) {
    let input = element.querySelector('input[type="text"]')
    let checkboxesContainer = element.querySelector('.search-checkbox')
    let checkedCheckboxes = checkboxesContainer.querySelectorAll('input[type="checkbox"]:checked');

    if(checkedCheckboxes.length === 0){
        input.value = '';   
    }else if(checkedCheckboxes.length > 1){
        input.value = checkedCheckboxes.length + ' éléments sélectionnés';
    }else {
        input.value = checkedCheckboxes.length + ' élément sélectionné';
    }
}

function searchCheckboxOrderCheckboxes(element) {
    let checkboxesContainer = element.querySelector('.search-checkbox')
    let checkboxes = Array.from(checkboxesContainer.querySelectorAll('.fr-fieldset__element'));
    //order checkboxes by data-order attribute
    checkboxes.sort((a, b) => {
        let orderA = parseInt(a.getAttribute('data-order'), 10);
        let orderB = parseInt(b.getAttribute('data-order'), 10);
        return orderA - orderB;
    });
    checkboxesContainer.innerHTML = '';
    checkboxes.forEach(checkbox => {
        checkbox.classList.remove('topped');
        checkboxesContainer.appendChild(checkbox);
    });
    //up checked checkboxes
    let checkedCheckboxes = Array.from(checkboxesContainer.querySelectorAll('input[type="checkbox"]:checked'));
    checkedCheckboxes.reverse();
    checkedCheckboxes.forEach(checkbox => {
        let parent = checkbox.closest('.fr-fieldset__element');
        parent.classList.add('topped');
        checkboxesContainer.prepend(parent)
    })
}