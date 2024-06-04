// Warning ! For the moment we do not know how to compile this file in const.min.js, we keep it for the debug


Node.prototype.addEventListeners = function (eventNames, eventFunction) {
    for (let eventName of eventNames.split(' '))
        this.addEventListener(eventName, eventFunction);
}

const addTagEvent = (event) => {
    event.target.removeEventListener('click', addTagEvent, true);
    let formData = new FormData;
    formData.append('_token', event.target.getAttribute('data-token'))
    formData.append('item', 'Tag');
    formData.append('value', event.target.getAttribute('data-value'));
    event.target.getAttribute('data-tag-add') && fetch(event.target.getAttribute('data-tag-add'), {
        method: 'POST',
        body: formData,
    }).then(r => {
        if (r.ok) {
            let container = document.querySelector(`#tags_active_container`);
            ['fr-fi-close-line', 'fr-fi-add-line'].forEach(c => {
                event.target.classList.toggle(c)
            })
            event.target.setAttribute('data-tag-delete', event.target.getAttribute('data-tag-add'));
            event.target.removeAttribute('data-tag-add')
            container.querySelector('em').classList.add('fr-hidden');
            let deleterBtn = event.target?.querySelector('span.tag--deleter.fr-hidden');
            deleterBtn?.classList?.add('fr-hidden');
            deleterBtn?.removeEventListener('click', persistRemoveTagEvent,true);
            container.appendChild(event.target);
            event.target.addEventListener('click', deleteTagEvent);
        }
    })
}
const deleteTagEvent = (event) => {
    event.target.removeEventListener('click', deleteTagEvent, true);
    let formData = new FormData;
    formData.append('_token', event.target.getAttribute('data-token'))
    formData.append('item', 'Tag');
    formData.append('value', event.target.getAttribute('data-value'));
    event.target.getAttribute('data-tag-delete') && fetch(event.target.getAttribute('data-tag-delete'), {
        method: 'POST',
        body: formData,
    }).then(r => {
        if (r.ok) {
            document.querySelector('#tags_select_tooltip_btn')._tippy.show();
            let container = document.querySelector(`#tags_inactive_container`);
            ['fr-fi-close-line', 'fr-fi-add-line'].forEach(c => {
                event.target.classList.toggle(c)
            })
            event.target.setAttribute('data-tag-add', event.target.getAttribute('data-tag-delete'));
            event.target.removeAttribute('data-tag-delete');
            let deleterBtn = event.target?.querySelector('span.tag--deleter.fr-hidden');
            deleterBtn?.classList?.remove('fr-hidden');
            deleterBtn?.addEventListener('click', persistRemoveTagEvent);
            container.appendChild(event.target);
            if (!document.querySelector(`#tags_active_container`).querySelector('.fr-badge'))
                document.querySelector(`#tags_active_container`).querySelector('em').classList.remove('fr-hidden');
            event.target.addEventListener('click', addTagEvent);
        }
    })
}
const persistRemoveTagEvent = (event) => {
    let tag = event.target.parentElement;
    let id = tag.getAttribute('data-value');
    let url = tag.getAttribute('data-remove-url').replace('__ID__', id);
    if(confirm('Êtes-vous certains de vouloir supprimer ce tag ?\nCette action est irréversible.')) {
        fetch(url).then(r => {
            if (r.ok) {
                tag.remove();
            }
        })
    }
}
const forms = document.querySelectorAll('form.needs-validation:not([name="bug-report"])');
const localStorage = window.localStorage;
const uploadedFiles = [];
const checkUserMail = (el) => {
    let formData = new FormData();
    formData.append('email', el.value)
    formData.append('_token', el.getAttribute('data-token'))
    fetch('/bo/partenaires/checkmail', {
        method: 'POST',
        body: formData
    }).then(r => {
        if (!r.ok) {
            r.json().then((r) => {
                el.classList.add('fr-input--error');
                el.parentElement.classList.add('fr-input-group--error');
                el.parentElement.querySelector('p.fr-error-text').innerText = r.error;
                el.parentElement.querySelector('p.fr-error-text').classList.remove('fr-hidden');
                document.querySelector('#user_create_form_submit').disabled = true;
                document.querySelector('#user_edit_form_submit').disabled = true;
            })
        } else {
            el.classList.remove('fr-input--error');
            el.parentElement.classList.remove('fr-input-group--error');
            el.parentElement.querySelector('p.fr-error-text').classList.add('fr-hidden');
            document.querySelector('#user_create_form_submit').disabled = false;
            document.querySelector('#user_edit_form_submit').disabled = false;            
        }
    })
    .catch(function (err) {
        console.warn('Something went wrong.', err);
    });
};
const serializeArray = (form) => {
    return Array.from(new FormData(form)
        .entries())
        .reduce(function (response, current) {
            response[current[0]] = current[1];
            return response
        }, {})
};
const sortTableFunction = (table) => {
    return function (ev) {
        if (ev.target.tagName.toLowerCase() === 'A') {
            sortRows(table, siblingIndex(ev.target.parentNode));
            ev.preventDefault();
        }
    };
}
const siblingIndex = (node) => {
    let count = 0;

    while (node === node.previousElementSibling) {
        count++;
    }

    return count;
}
const sortRows = (table, columnIndex) => {
    let rows = table.querySelectorAll("tbody tr"),
        sel = "thead th:nth-child(" + (columnIndex + 1) + ")",
        sel2 = "td:nth-child(" + (columnIndex + 1) + ")",
        classList = table.querySelector(sel).classList,
        values = [],
        cls = "",
        allNum = true,
        val,
        index,
        node;

    if (classList) {
        if (classList.contains("date")) {
            cls = "date";
        } else if (classList.contains("number")) {
            cls = "number";
        }
    }

    for (index = 0; index < rows.length; index++) {
        node = rows[index].querySelector(sel2);
        val = node.innerText;

        if (isNaN(val)) {
            allNum = false;
        } else {
            val = parseFloat(val);
        }

        values.push({value: val, row: rows[index]});
    }

    if (cls == "" && allNum) {
        cls = "number";
    }

    if (cls == "number") {
        values.sort(sortNumberVal);
        values = values.reverse();
    } else if (cls == "date") {
        values.sort(sortDateVal);
    } else {
        values.sort(sortTextVal);
    }

    for (let idx = 0; idx < values.length; idx++) {
        table.querySelector("tbody").appendChild(values[idx].row);
    }
}
const sortNumberVal = (a, b) => {
    return sortNumber(a.value, b.value);
}
const sortNumber = (a, b) => {
    return a - b;
}
const sortDateVal = (a, b) => {
    let dateA = Date.parse(a.value),
        dateB = Date.parse(b.value);

    return sortNumber(dateA, dateB);
}
const sortTextVal = (a, b) => {
    let textA = (a.value + "").toUpperCase();
    let textB = (b.value + "").toUpperCase();

    if (textA < textB) {
        return -1;
    }

    if (textA > textB) {
        return 1;
    }

    return 0;
}

let idFetchTimeout;
const searchAddress = (form, autocomplete) => {
    clearTimeout(idFetchTimeout);
    idFetchTimeout = setTimeout( () => {
        if (autocomplete.value.length > 10) {
            autocomplete.removeEventListener('keyup', searchAddress)
            fetch('https://api-adresse.data.gouv.fr/search/?q=' + autocomplete.value).then((res) => {
                res.json().then((r) => {
                    let container = form.querySelector('#signalement-adresse-suggestion')
                    container.innerHTML = '';
                    for (let feature of r.features) {
                        let suggestion = document.createElement('div');
                        suggestion.classList.add('fr-col-12', 'fr-p-3v', 'fr-text-label--blue-france', 'fr-adresse-suggestion');
                        suggestion.innerHTML = feature.properties.label;
                        suggestion.addEventListener('click', () => {
                            form.querySelector('#signalement_adresseOccupant').value = feature.properties.name;
                            form.querySelector('#signalement_cpOccupant').value = feature.properties.postcode;
                            form.querySelector('#signalement_villeOccupant').value = feature.properties.city;
                            form.querySelector('#signalement-insee-occupant').value = feature.properties.citycode;
                            let zipOccupant = feature.properties.citycode.substr(0, 2)
                            if (zipOccupant == '69' || zipOccupant == '29') {
                                const METROPOLE_RHONES_AUTHORIZED_INSEE_CODES = [
                                    69091, 69096, 69123, 69149, 69199, 69205, 69290, 69259, 69266,
                                    69381, 69382, 69383, 69384, 69385, 69386, 69387, 69388, 69389,
                                    69003, 69029, 69033, 69034, 69040, 69044, 69046, 69271, 69063,
                                    69273, 69068, 69069, 69071, 69072, 69275, 69081, 69276, 69085,
                                    69087, 69088, 69089, 69100, 69279, 69142, 69250, 69116, 69117,
                                    69127, 69282, 69283, 69284, 69143, 69152, 69153, 69163, 69286,
                                    69168, 69191, 69194, 69204, 69207, 69202, 69292, 69293, 69296,
                                    69244, 69256, 69260, 69233, 69278
                                ];
                                const COR_RHONES_AUTHORIZED_INSEE_CODES = [
                                    69001, 69006, 69008, 69037, 69054, 69060, 69066, 69070, 69075,
                                    69093, 69102, 69107, 69174, 69130, 69160, 69164, 69169, 69181,
                                    69183, 69188, 69200, 69214, 69217, 69225, 69229, 69234, 69240,
                                    69243, 69248, 69254, 69157
                                ];
    
                                const FINISTERE_AUTHORIZED_INSEE_CODES = [29232, 29019];
    
                                const AUTHORIZED_INSEE_CODES = METROPOLE_RHONES_AUTHORIZED_INSEE_CODES.concat(
                                    COR_RHONES_AUTHORIZED_INSEE_CODES, 
                                    FINISTERE_AUTHORIZED_INSEE_CODES
                                );
    
                                if (AUTHORIZED_INSEE_CODES.indexOf(Number(a.properties.citycode)) == -1) {
                                    e.querySelector('#fr-error-text-insee')?.classList?.remove('fr-hidden');
                                } else {
                                    e.querySelector('#fr-error-text-insee')?.classList?.add('fr-hidden');
                                }
                            }

                            // Zip codes available for Non Conformité Energétique
                            isZipForNDE = (zipOccupant == '63' || zipOccupant == '89');
                        })
                        container.appendChild(suggestion)

                    }
                })
            })
            return false;
        }
    }, 300 );
};