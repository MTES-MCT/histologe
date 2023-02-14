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
    fetch('/bo/partner/checkmail', {
        method: 'POST',
        body: formData
    }).then(r => {
        if (!r.ok) {
            el.classList.add('fr-input--error');
            el.parentElement.classList.add('fr-input-group--error');
            el.parentElement.querySelector('p.fr-error-text').classList.remove('fr-hidden');
            document.querySelector('#submit_btn_partner').disabled = true;
        } else {
            el.classList.remove('fr-input--error');
            el.parentElement.classList.remove('fr-input-group--error');
            el.parentElement.querySelector('p.fr-error-text').classList.add('fr-hidden');
            document.querySelector('#submit_btn_partner').disabled = false;
        }
    })
}
const serializeArray = (form) => {
    return Array.from(new FormData(form)
        .entries())
        .reduce(function (response, current) {
            response[current[0]] = current[1];
            return response
        }, {})
};
const checkFirstStep = (form) => {
    return !(form.id === "signalement-step-2" && null === form.querySelector('[type="radio"]:checked') || form.id === "signalement-step-2" && form.querySelectorAll('[type="checkbox"]:checked').length !== form.querySelectorAll('[type="radio"]:checked').length);
}
const checkFieldset = (form) => {
    let field = form.querySelector('fieldset[aria-required="true"]')
    if (field) {
        if (null === field.querySelector('[type="checkbox"]:checked')) {
            field.classList.add('fr-fieldset--error');
            field?.querySelector('.fr-error-text')?.classList.remove('fr-hidden');
            invalid = field.parentElement;
            return false;
        } else {
            field.classList.remove('fr-fieldset--error');
            field?.querySelector('.fr-error-text')?.classList.add('fr-hidden');
            return true;
        }
    } else
        return true;
}
const goToStep = (step) => {
    document.querySelector('#signalement-step-' + step + '-btn').click();
}
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
const setBadge = (el) => {
    let container = el.parentElement.querySelector('.selected__value');
    if (el.value !== '') {
        let badge = document.createElement('span');
        badge.classList.add('fr-badge', 'fr-badge--success', 'fr-m-1v')
        badge.innerText = el.selectedOptions[0].text;
        let input = document.createElement('input');
        input.type = "hidden";
        input.name = `${el.id}[]`;
        input.value = el.value;
        container.append(input);
        badge.setAttribute('data-value', el.value);
        container.querySelector('.fr-badge:not([data-value])')?.classList?.add('fr-hidden');
        container.append(badge)
        el.selectedOptions[0].classList.add('fr-hidden')
        badge.addEventListener('click', (event) => {
            removeBadge(badge);
        })
    } else {
        container.querySelectorAll('.fr-badge[data-value]').forEach(badge => {
            removeBadge(badge);
        })
    }
    return false;
}
const removeBadge = (badge) => {
    let val = badge.getAttribute('data-value');
    let input = badge.parentElement.querySelector(`input[value="${val}"]`);
    let select = badge?.parentElement?.parentElement?.querySelector(`select`) ?? badge?.parentElement?.parentElement?.querySelector(`input[type="date"]`);
    select.querySelector(`option[value="${val}"]`)?.classList?.remove('fr-hidden');
    input?.remove();
    let badges = badge.parentElement.querySelectorAll('.fr-badge[data-value]').length !== 1;
    console.log(badge.parentElement.querySelectorAll('.fr-badge[data-value]').length)
    if (!badges) {
        badge?.parentElement?.querySelector('.fr-badge:not([data-value])')?.classList?.remove('fr-hidden');
        if (select.tagName === 'SELECT')
            select.options[0].selected = true;
    }
    badge.remove();
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
                            if (feature.properties.citycode.substr(0, 2) == '69') {
                                const METROPOLE_RHONES_AUTHORIZED_INSEE_CODES = [
                                    69091, 69096, 69123, 69149, 69199, 69205, 69290, 69259, 69266,
                                    69381, 69382, 69383, 69384, 69385, 69386, 69387, 69388, 69389,
                                    69901 ];
                                const COR_RHONES_AUTHORIZED_INSEE_CODES = [
                                    69001, 69006, 69008, 69037, 69054, 69060, 69066, 69070, 69075,
                                    69093, 69102, 69107, 69174, 69130, 69160, 69164, 69169, 69181,
                                    69183, 69188, 69200, 69214, 69217, 69225, 69229, 69234, 69240,
                                    69243, 69248, 69254, 69157
                                ];
                                const RHONES_AUTHORIZED_INSEE_CODES = METROPOLE_RHONES_AUTHORIZED_INSEE_CODES.concat(
                                    COR_RHONES_AUTHORIZED_INSEE_CODES
                                );

                                if (RHONES_AUTHORIZED_INSEE_CODES.indexOf(Number(feature.properties.citycode)) == -1) {
                                    form.querySelector('#fr-error-text-insee')?.classList?.remove('fr-hidden');
                                } else {
                                    form.querySelector('#fr-error-text-insee')?.classList?.add('fr-hidden');
                                }
                                form.querySelector('#signalement-geoloc-lat-occupant').value = feature.geometry.coordinates[0];
                                form.querySelector('#signalement-geoloc-lng-occupant').value = feature.geometry.coordinates[1];
                                container.innerHTML = '';
                            }
                        })
                        container.appendChild(suggestion)

                    }
                })
            })
            return false;
        }
    }, 300 );
};