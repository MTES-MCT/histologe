let invalid, tables = document.querySelectorAll("table.sortable"),
    table,
    thead,
    headers,
    i,
    j;
for (i = 0; i < tables.length; i++) {
    table = tables[i];

    if (thead = table.querySelector("thead")) {
        headers = thead.querySelectorAll("th");

        for (j = 0; j < headers.length; j++) {
            headers[j].innerHTML = "<a href='#'>" + headers[j].innerText + "</a>";
        }

        thead.addEventListener("click", sortTableFunction(table));
    }
}
forms.forEach((form) => {
    form?.querySelectorAll('.toggle-criticite input[type="radio"]')?.forEach((criticite) => {
        criticite.addEventListener('change', (event) => {
            event.currentTarget.parentElement.parentElement.parentElement.querySelector('.fr-toggle__input').checked = true;
            // parent.querySelector('[type="checkbox"]').checked = !parent.querySelector('[type="checkbox"]').checked;
        })
    })
    form?.querySelectorAll('.fr-toggle')?.forEach((t) => {
        t.addEventListener('change', (event) => {
            console.log('toggle')
            if (!event.target.checked)
                event.currentTarget.nextElementSibling.querySelectorAll('.fr-collapse input[type="radio"]').forEach((radio) => {
                    radio.checked = false
                    radio.required = false;
                })
        })
    })
    form?.querySelectorAll('.fr-hover-critere')?.forEach(fhc => {
        fhc.addEventListeners('click touchdown', (event) => {
            if (fhc.parentElement.parentElement.getAttribute('aria-expanded') === "true") {
                let target = form?.querySelector('#' + fhc.parentElement.parentElement.getAttribute('aria-controls'))
                target.classList.remove('fr-fieldset--error')
                target.querySelectorAll('input').forEach(i => {
                    i.required = false;
                    i.checked = false;
                })
                target.querySelectorAll('.fr-radio-rich img').forEach(img => {
                    img.src = img.getAttribute('data-fr-unchecked-icon');
                })
            }
            // if(event.target.parentElement.parentElement.querySelector('[type="checkbox"]'))

        })
    })
    form?.querySelectorAll('.fr-accordion__title')?.forEach((situation) => {
        situation.addEventListeners("click touchdown", (event) => {
            event.target.parentElement.parentElement.querySelectorAll('[type="radio"],[type="checkbox"]').forEach((ipt) => {
                ipt.checked = false;

            })
        })
    })
    form?.querySelectorAll('[data-fr-toggle-show],[data-fr-toggle-hide]')?.forEach((toggle) => {
        toggle.addEventListener('change', (event) => {
            let toShow = event.target.getAttribute('data-fr-toggle-show'),
                toHide = event.target.getAttribute('data-fr-toggle-hide'),
                toUnrequire = event.target.getAttribute('data-fr-toggle-unrequire'),
                toRequire = event.target.getAttribute('data-fr-toggle-require')
            toShow && toShow.split('|').map(targetId => {
                let target;
                if (targetId === "signalement-consentement-tiers-bloc") {
                    target = document?.querySelector('#signalement-consentement-tiers-bloc');
                    target.querySelector('[type="checkbox"]').required = true;
                } else {
                    target = form?.querySelector('#' + targetId);
                    target.querySelectorAll('input:not([type="checkbox"]),textarea,select').forEach(ipt => {
                        if (ipt.name !== "signalement[numAllocataire]") {
                            ipt.required = true;
                            if (ipt.labels)
                                ipt.labels[0].classList.add('required')
                        }
                    })
                }
                if (target.id === "signalement-methode-contact") {
                    target.querySelector('fieldset').setAttribute('aria-required', true)
                }
                target.classList.remove('fr-hidden')
            })
            toHide && toHide.split('|').map(targetId => {
                let target;
                if (targetId === "signalement-consentement-tiers-bloc") {
                    target = document.querySelector('#signalement-consentement-tiers-bloc');
                    target.querySelector('[type="checkbox"]').required = false
                } else {
                    target = form?.querySelector('#' + targetId);
                    target?.querySelectorAll('input:not([type="checkbox"]),textarea,select')?.forEach(ipt => {
                        ipt.required = false;
                    })
                }
                if (target.id === "signalement-methode-contact") {
                    target?.querySelector('fieldset[aria-required="true"]')?.removeAttribute('aria-required')
                    target?.querySelectorAll('[type="checkbox"]')?.forEach(chk => {
                        chk.checked = false;
                    })
                }
                target.classList.add('fr-hidden')
            })
            toUnrequire && toUnrequire.split('|').map(targetId => {
                let target = form?.querySelector('#' + targetId);
                if (!target)
                    target = document?.querySelector('#' + targetId);
                target.required = false;
                target?.parentElement?.classList?.remove('fr-input-group--error')
                target?.parentElement?.querySelector('.fr-error-text')?.classList.add('fr-hidden')
                target?.classList?.remove('fr-input--error')
                target.labels[0].classList.remove('required')
            })
            toRequire && toRequire.split('|').map(targetId => {
                let target = form?.querySelector('#' + targetId);
                if (!target)
                    target = document?.querySelector('#' + targetId);
                target.required = true;
                target?.labels[0]?.classList.add('required')
            })
        })
    })
    form?.querySelectorAll('input[type="file"]')?.forEach((file) => {
        file.addEventListener('change', (event) => {
            // console.log(file.files[0])
            if (event.target.files.length > 0) {
                let resTextEl = event.target.parentElement.nextElementSibling;
                console.log(resTextEl)
                let fileData = new FormData();
                let deleter = event.target.parentElement.parentElement.querySelector('.signalement-uploadedfile-delete'),
                    /*src = URL.createObjectURL(event.target.files[0]),*/
                    preview = event.target?.parentElement?.querySelector('img'),
                    fileIsOk = false, file = event.target.files[0];
                let id = event.target.id;
                let progress = document.querySelector("#progress_" + id);
                let totalProgress = document.querySelector('#form_global_file_progress');
                console.log(file)
                if (preview) {
                    /*const MAX_SIZE = (1024 * 1024) / 2;
                    const RATIO = (MAX_SIZE / event.target.files[0].size) / 2
                    if (event.target.files[0].size > MAX_SIZE) {
                        resizeImage(event.target.files[0], RATIO).then(function (blob) {
                            // Preview
                            // Assume that `previewEle` represents the preview image element
                            preview.src = URL.createObjectURL(blob);
                            fileData.append(event.target.name, blob);
                            event.target.value = '';
                        });
                    } else {*/
                    preview.src = URL.createObjectURL(file);
                    /* }*/
                    // preview.src = src;
                    ['fr-fi-instagram-line', 'fr-py-7v', 'fr-fi-refresh-line', 'fr-disabled'].map(v => event.target.parentElement.classList.toggle(v));
                    fileIsOk = true;
                } else if (event.target.parentElement.classList.contains('fr-fi-attachment-fill')) {
                    if (event.target.files[0].size > 10 * 1024 * 1024) {
                        /*event.target.value = "";*/
                        // resTextEl.innerHTML = "Le document dépasse 10MB";
                        resTextEl.classList.remove('fr-hidden')
                    } else {
                        resTextEl.classList.add('fr-hidden')
                        fileIsOk = true;
                        ['fr-fi-attachment-fill', 'fr-fi-refresh-line', 'fr-disabled'].map(v => event.target.parentElement.classList.toggle(v));
                    }
                }
                if (fileIsOk) {
                    // [preview, deleter].forEach(el => el?.classList?.remove('fr-hidden'))
                    deleter.addEventListeners('click touchdown', (e) => {
                        e.preventDefault();
                        if (preview) {
                            preview.src = '#';
                            event.target.parentElement.classList.add('fr-fi-instagram-line')
                        } else if (event.target.parentElement.classList.contains('fr-fi-checkbox-circle-fill')) {
                            ['fr-fi-attachment-fill', 'fr-fi-checkbox-circle-fill'].map(v => event.target.parentElement.classList.toggle(v));
                        } else {
                            event.target.parentElement.classList.add('fr-fi-attachment-fill');
                        }
                        event.target.value = '';
                        fileData.delete(event.target.name);
                        delete uploadedFiles[event.target.id];
                        [preview, deleter].forEach(el => el?.classList.add('fr-hidden'));
                        event.target.parentElement.classList.remove('fr-disabled')
                        resTextEl.innerText = '';
                    })
                    fileData.append(event.target.name, file)
                    let request = new XMLHttpRequest();
                    let finish_submit_btn = document.querySelector('#form_finish_submit');
                    request.open('POST', event.target.getAttribute('data-handle-url'));
                    request.upload.addEventListener('progress', function (e) {
                        totalProgress.classList.remove('fr-hidden')
                        finish_submit_btn.disabled = true;
                        finish_submit_btn.innerHTML = 'Téléversement en cours, veuillez patienter....';
                        let activeProgresses = document.querySelectorAll('progress:not(.fr-hidden,.final-progress)');
                        let percent_completed = (e.loaded / e.total) * 100;
                        let total_percent_completed = 0;
                        activeProgresses.forEach(acp => {
                            total_percent_completed += acp.value;
                        })
                        totalProgress.value = total_percent_completed / activeProgresses.length;
                        console.log(id)
                        progress.value = percent_completed;
                        console.log(percent_completed);
                    });
                    request.addEventListener('load', function (e) {
                        console.log(request.status);
                        console.log(request.response);
                        event.target.parentElement.classList.remove('fr-fi-refresh-line');
                        [preview, deleter].forEach(el => el?.classList?.remove('fr-hidden'));
                        progress.value = 0;
                        let jsonRes = JSON.parse(request.response)
                        if (request.status !== 200) {
                            resTextEl.innerText = jsonRes.error;
                            resTextEl.classList.remove('fr-hidden');
                            resTextEl.classList.add('fr-text-label--red-marianne');
                            progress.value = 0;
                            deleter.click()
                        } else {
                            resTextEl.innerText = jsonRes.titre
                            resTextEl.classList.remove('fr-hidden');
                            resTextEl.classList.add('fr-text-label--green-emeraude');
                            if (!preview)
                                ['fr-fi-checkbox-circle-fill'].map(v => event.target.parentElement.classList.toggle(v));
                            uploadedFiles[event.target.id] = request.response;
                        }
                        progress.classList.add('fr-hidden');
                        event.target.value = '';
                        if (document.querySelectorAll('progress:not(.fr-hidden,.final-progress)').length < 1) {
                            totalProgress.classList.add('fr-hidden')
                            finish_submit_btn.innerHTML = 'Confirmer';
                            finish_submit_btn.disabled = false;
                        }
                    });
                    progress.classList.remove('fr-hidden')
                    request.send(fileData);
                    event.target.parentElement.classList.add('fr-disabled')
                }
            }
        })
    })
    form?.querySelectorAll('[data-fr-adresse-autocomplete]').forEach((autocomplete) => {
        autocomplete.addEventListener('keyup', () => {
            searchAddress(form, autocomplete)
        });
    })
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        /*    console.log(form.querySelectorAll('[type="checkbox"]:checked').length)*/
        if (!form.checkValidity() || !checkFirstStep(form) || !checkFieldset(form)) {
            event.stopPropagation();
            if (form.id === "signalement-step-2") {
                form.querySelector('[role="alert"]').classList.remove('fr-hidden')
                /* invalid = document.querySelector("div[role='alert']");*/
                form?.querySelectorAll('.fr-fieldset__content.fr-collapse.fr-collapse--expanded').forEach(exp => {
                    exp.querySelector('[type="radio"]:first-of-type').required = true;
                    if (exp.querySelector('input:invalid')) {
                        exp.classList.add('fr-fieldset--error')
                        exp.querySelector('.fr-error-text').classList.remove('fr-hidden')
                    }
                })
                invalid = form?.querySelector('*:invalid:first-of-type')?.parentElement;
                if (!invalid)
                    invalid = document.querySelector("div[role='alert']")
                form.addEventListener('change', () => {
                    form?.querySelectorAll('.fr-fieldset__content.fr-collapse.fr-collapse--expanded').forEach(exp => {
                        if (null === exp.querySelector('input:invalid')) {
                            exp.classList.remove('fr-fieldset--error')
                            exp.querySelector('.fr-error-text').classList.add('fr-hidden')
                        }
                    })
                    if (checkFirstStep(form)) {
                        form.querySelector('[role="alert"]').classList.add('fr-hidden')
                    }
                })
            } else {
                form.querySelectorAll('input,textarea,select,fieldset[aria-required="true"]').forEach((field) => {
                    if (field.tagName === "FIELDSET") {
                        if (!checkFieldset(form)) {
                            field.addEventListener('change', () => {
                                checkFieldset(form);
                            })
                            invalid = field.parentElement;
                        }
                    } else if (!field.checkValidity()) {
                        /* console.log(field)*/
                        let parent = field.parentElement;
                        if (field.type === 'radio')
                            parent = field.parentElement.parentElement.parentElement;
                        [field.classList, parent.classList].forEach((f) => {
                            f.add(f[0] + '--error');
                        })
                        parent?.querySelector('.fr-error-text')?.classList.remove('fr-hidden');
                        field.addEventListener('input', () => {
                            if (field.checkValidity()) {
                                [field.classList, parent.classList].forEach((f) => {
                                    f.remove(f[0] + '--error');
                                })
                                parent.querySelector('.fr-error-text')?.classList.add('fr-hidden');
                            }
                        })
                        invalid = form?.querySelector('*:invalid:first-of-type')?.parentElement;
                    }

                })

            }
            if (invalid) {
                const y = invalid.getBoundingClientRect().top + window.scrollY;
                window.scroll({
                    top: y,
                    behavior: 'smooth'
                });
            }
        } else {
            form.querySelectorAll('input,textarea,select').forEach((field) => {
                let parent = field.parentElement;
                if (field.type === 'radio')
                    parent = field.parentElement.parentElement.parentElement;
                [field.classList, parent.classList].forEach((f) => {
                    f.remove(f[0] + '--error');
                })
                parent.querySelector('.fr-error-text')?.classList.add('fr-hidden');
            })
            if (form.name !== 'signalement') {
                Object.keys(uploadedFiles).map((f, index) => {
                    let fi = JSON.parse(uploadedFiles[f]);
                    form.insertAdjacentHTML('beforeend', `<input type="hidden" name="signalement[files][${fi.key}][${fi.titre}]" value="${fi.file}">`);
                });
                form.submit();
            }
            else {
                let currentTabBtn = document.querySelector('.fr-tabs__list>li>button[aria-selected="true"]'),
                    nextTabBtn = currentTabBtn.parentElement?.nextElementSibling?.querySelector('button');
                if (form.id === "signalement-step-1" && form.querySelector('.checkterr')) {
                    fetch('checkterritory?cp='+form.querySelector('#signalement_cpOccupant').value).then(r=> r.json()).then(r=> {
                        if(r.success)
                        {
                            nextTabBtn.disabled = false;
                            nextTabBtn.click();
                        } else {
                           dsfr(document.querySelector('#fr-modal-closed-territory')).modal.disclose();
                        }
                    })
                } else {
                    if (nextTabBtn) {
                        if (nextTabBtn.hasAttribute('data-fr-last-step')) {
                            var nbDocs = 0;
                            var nbPhotos = 0;
                            document.querySelector('#recap-signalement-situation').innerHTML = '';
                            forms.forEach((form) => {
                                form.querySelectorAll('[type="file"]').forEach(file => {
                                    if (file.classList.contains("doc-file")) {
                                        if (file.parentElement.classList.contains('fr-fi-checkbox-circle-fill')) {
                                            nbDocs ++;
                                        }
                                    }
                                    if (file.classList.contains("photo-file")) {
                                        if (file.previousElementSibling != undefined && file.previousElementSibling.src != undefined && file.previousElementSibling.src != '') {
                                            nbPhotos ++;
                                        }
                                    }
                                })

                                form.querySelectorAll('input,textarea,select').forEach((input) => {
                                    if (document.querySelector('#recap-' + input.id)) {
                                        document.querySelector('#recap-' + input.id).innerHTML = `${input.value}`;
                                    } else if (input.classList.contains('signalement-situation') && input.checked)
                                        document.querySelector('#recap-signalement-situation').innerHTML += '- ' + input.value + '<br>';
                                });
                                let compAddress = Array( 'signalement_etageOccupant', 'signalement_escalierOccupant', 'signalement_numAppartOccupant', 'signalement_adresseAutreOccupant' );
                                for (const str of compAddress) {
                                    if ( document.querySelector('#recap-' + str).innerHTML == '' ) {
                                        document.querySelector('#recap-container-' + str).style.display = 'none';
                                    } else {
                                        document.querySelector('#recap-container-' + str).style.display = 'inline';
                                    }
                                }
                            })

                            document.querySelector('#recap-signalement_documents').innerHTML = nbDocs + ' document(s) transmis';
                            document.querySelector('#recap-signalement_photos').innerHTML = nbPhotos + ' photo(s) transmise(s)';
                        }
                        nextTabBtn.disabled = false;
                        nextTabBtn.click();
                    } else if (!nextTabBtn) {
                        event.target.querySelector('[type="submit"]').disabled = true;
                        ['fr-fi-checkbox-circle-fill', 'fr-fi-refresh-fill'].map(v => event.target.querySelector('[type="submit"]').classList.toggle(v));
                        event.target.querySelector('[type="submit"]').innerHTML = "En cours d'envoi..."
                        let formData = new FormData();
                        forms.forEach((form) => {
                            let data = serializeArray(form);
                            for (let i = 0; i < Object.keys(data).length; i++) {
                                let x = Object.keys(data)[i];
                                let y = Object.values(data)[i];
                                if (x !== 'signalement[photos]' && x !== 'signalement[documents]')
                                    formData.append(x, y);
                            }
                        })
                        Object.keys(uploadedFiles).map((f, index) => {
                            let fi = JSON.parse(uploadedFiles[f]);
                            formData.append(`signalement[files][${fi.key}][${fi.titre}]`, fi.file)
                        })
                        fetch(form.action, {
                            method: "POST",
                            body: formData
                        }).then((r) => {

                            if (r.ok) {
                                r.json().then((res) => {
                                    if (res.response === "success") {
                                        document.querySelectorAll('#signalement-tabs,#signalement-success').forEach(el => {
                                            el.classList.toggle('fr-hidden')
                                        })
                                        localStorage.clear();
                                    } else if (res.response === "success_edited") {
                                        localStorage.clear();
                                        window.location.reload();
                                    } else {
                                        event.target.querySelector('[type="submit"]').disabled = false;
                                        event.target.querySelector('[type="submit"]').innerHTML = "Confirmer";
                                        ['fr-fi-checkbox-circle-fill', 'fr-fi-refresh-fill'].map(v => event.target.querySelector('[type="submit"]').classList.toggle(v));
                                        alert('Erreur lors de l\'enregistrement du  signalement !')

                                    }
                                })
                            } else {
                                r.text().then(r=>{
                                    console.log(r)
                                })
                                event.target.querySelector('[type="submit"]').disabled = false;
                                event.target.querySelector('[type="submit"]').innerHTML = "Confirmer";
                                ['fr-fi-checkbox-circle-fill', 'fr-fi-refresh-fill'].map(v => event.target.querySelector('[type="submit"]').classList.toggle(v));
                                alert('Erreur lors de l\'enregistrement du  signalement ! Nos équipes ont été informées du problème.')
                            }
                        })
                    }
                }
            }
        }
    })
})
document?.querySelectorAll('.fr-tabs__panel')?.forEach((tab) => {
    tab.addEventListener("dsfr.conceal", () => {
        if (tab.id === "signalement-step-2-panel") {
            tab.querySelectorAll('[aria-expanded="true"]').forEach(opened => {
                localStorage.setItem(opened.id, 'true')
            })
        }
        const y = tab.getBoundingClientRect().top + window.scrollY;
        window.scroll({
            top: y,
            behavior: 'smooth'
        });
    });
})
document?.querySelectorAll('[data-goto-step]')?.forEach(stepper => {
    stepper.addEventListeners('click touchdown', (evt) => {
        evt.preventDefault();
        goToStep(stepper.getAttribute('data-goto-step'))
    })
})
document?.querySelectorAll('.toggle-criticite-smiley').forEach(iptSmiley => {
    iptSmiley.addEventListener('change', (evt) => {
        let icon = evt.target.labels[0]?.parentElement?.querySelector('.fr-radio-rich__img img');
        evt.target.parentElement.parentElement.querySelectorAll('.fr-radio-rich__img img').forEach(iptParentImg => {
            iptParentImg.src = iptParentImg.getAttribute('data-fr-unchecked-icon')
        })
        if (evt.target.checked === true)
            icon.src = evt.target.parentElement.querySelector('.fr-radio-rich__img img').getAttribute('data-fr-checked-icon')
    })
})
document?.querySelector('#signalement-step-2-panel')?.addEventListener('dsfr.disclose', (ev => {
    ev.target.querySelectorAll('[aria-expanded]').forEach(exp => {
        if (localStorage.getItem(exp.id)) { // noinspection CommaExpressionJS
            document.querySelector('#' + exp.id).setAttribute('aria-expanded', "true"), localStorage.removeItem(exp.id)
        }
    })
}))
document?.querySelectorAll(".fr-pagination__link:not([aria-current])").forEach((e => {
    let t, r, a, n = document.querySelector(".fr-pagination__link--prev"),
        i = document.querySelector(".fr-pagination__link--next"),
        u = document.querySelector(".fr-pagination__link--first"),
        l = document.querySelector(".fr-pagination__link--last"), o = 1, c = parseInt(l.getAttribute("data-page"));
    e.addEventListener("click", (e => {
        let p = new FormData(document.querySelector('form[name="bo-filters-form"]'));
        p.append("pagination", "true");
        let d = document?.querySelector(".fr-pagination__link[aria-current]"), g = e.target;
        g !== n && g !== i && g !== u && g !== l ? o = parseInt(g.getAttribute("data-page")) : g === i ? o = parseInt(d.getAttribute("data-page")) + 1 : g === n ? o = parseInt(d.getAttribute("data-page")) - 1 : g === l ? o = parseInt(c) : g === u && (o = parseInt(1)), p.append("page", o), t = document.querySelector('.fr-pagination__link[data-page="' + o + '"]'), fetch("#", {
            method: "POST",
            body: p
        }).then((e => e.text().then((e => {
            let p = document.querySelector("#signalements-result");
            p.innerHTML = e, p.querySelectorAll("tr").forEach((e => {
                gauge = new Gauge(e.querySelector(".gauge-signalement")).setOptions(opts), gauge.set(e.getAttribute("data-score"))
            })), d.removeAttribute('aria-current'), d.href = "#", t.removeAttribute("href"), t.setAttribute('aria-current', 'page'), 1 !== o && o !== c ? r = [u, n, i, l] : 1 === o ? (r = [i, l], a = [u, n]) : o === c && (r = [u, n], a = [i, l]), r.forEach((e => {
                e.removeAttribute("aria-disabled"), e.href = "#"
            })), a && a.forEach((e => {
                e.removeAttribute("href"), e.setAttribute('aria-disabled', "true")
            }))
        }))))
    }))
}));
document?.querySelectorAll('[data-removable="true"]')?.forEach(removale => {
    removale.addEventListener('click', () => {
        removeBadge(removale);
    })
})
document?.querySelectorAll('[data-fr-select-target]')?.forEach(t => {
    let source = document?.querySelector('#' + t.getAttribute('data-fr-select-source'));
    let target = document?.querySelector('#' + t.getAttribute('data-fr-select-target'));
    t.addEventListeners('click touchdown', () => {
        [...source.selectedOptions].map(s => {
            target.append(s)
        })
    })
})

document?.querySelector('#signalement-affectation-form-submit')?.addEventListeners('click touchdown', (e) => {
    e.preventDefault();
    e.target.disabled = true;
    e.target?.form?.querySelectorAll('option').forEach(o => {
        o.selected = true;
    })
    document?.querySelectorAll('#signalement-affectation-form-row,#signalement-affectation-loader-row').forEach(el => {
        el.classList.toggle('fr-hidden')
    })
    //POST
    let formData = new FormData(e.target.form);
    fetch(e.target.getAttribute('formaction'), {
        method: 'POST',
        body: formData
    }).then(r => {
        if (r.ok) {
            /*r.json().then(res => {*/
            window.location.reload(true)
            /*})*/
        }
    })
    //console.log(e.target.form);
})
document?.querySelectorAll('.fr-input--file-signalement').forEach(inputFile => {
    inputFile.addEventListener('change', evt => {
        evt.target.form.submit();
    })
})
document?.querySelector('#partner_add_user,#situation_add_critere')?.addEventListeners('click touchdown', (event) => {
    event.preventDefault();
    let template, container, count, row, className;
    if (event.target.id === 'partner_add_user') {   
        template = document.importNode(document.querySelector('#partner_add_user_row').content, true)
        container = document.querySelector('#partner_add_user_placeholder')
        className = 'partner-row-user'
        count = container.querySelectorAll('.' + className)?.length
    } else {
        template = document.importNode(document.querySelector('#situation_add_critere_row').content, true)
        container = document.querySelector('#situation_add_critere_placeholder')
        className = 'situation-row-critere';
        count = container.querySelectorAll('.' + className)?.length
    }
    row = document.createElement('div');
    row.classList.add('fr-grid-row', 'fr-grid-row--gutters', 'fr-grid-row--middle', 'fr-background-alt--blue-france', 'fr-mb-5v', className);
    template.querySelectorAll('label,input,select,button,textarea').forEach(field => {
        field.id = field?.id?.replaceAll('__ID__', count);
        field.name = field?.name?.replaceAll('__ID__', count);
        if (field.tagName === 'LABEL')
            field.setAttribute('for', field.getAttribute('for').replaceAll('__ID__', count))
        if (field.tagName === 'BUTTON')
            field.addEventListeners('click touchdown', (event) => {
                event.target.closest('.' + className).remove();
            })
    })
    row.appendChild(template);
    container.appendChild(row);
})


/*document?.querySelectorAll('[data-tag-add]')?.forEach(addBtn => {
    addBtn.addEventListener('click', addTagEvent)
});*/
document?.querySelectorAll('[data-tag-delete]')?.forEach(delBtn => {
    delBtn.addEventListener('click', deleteTagEvent)
});
document?.querySelectorAll('[data-delete]')?.forEach(actionBtn => {
    actionBtn.addEventListeners('click touchdown', event => {
        event.preventDefault();
        let className;
        if (event.target.classList.contains('partner-user-delete'))
            className = '.partner-row-user';
        else if (event.target.classList.contains('situation-critere-delete'))
            className = '.situation-row-critere';
        else if (event.target.classList.contains('signalement-file-delete'))
            className = '.signalement-file-item';
        else if (event.target.classList.contains('signalement-row-delete'))
            className = '.signalement-row';
        else if (event.target.classList.contains('partner-row-delete'))
            className = '.partner-row';
        if (confirm('Voulez-vous vraiment supprimer cet élément ?')) {
            let formData = new FormData;
            formData.append('_token', actionBtn.getAttribute('data-token'))
            let value = actionBtn.getAttribute('data-value') ?? null;
            if (value)
                formData.append('item', 'Tag'), formData.append('value', value);
            fetch(actionBtn.getAttribute('data-delete'), {
                method: 'POST',
                body: formData,
            }).then(r => {
                if (r.ok)
                    actionBtn.closest(className).remove()
            })
        }
    })
});
document?.querySelectorAll('.fr-password-toggle')?.forEach(pwdToggle => {
    pwdToggle.addEventListeners('click touchdown', (event) => {
        ['fr-fi-eye-off-fill', 'fr-fi-eye-fill'].map(c => {
            event.target.classList.toggle(c);
        })
        let pwd = event.target.parentElement.querySelector('[name^="password"]');
        "text" !== pwd.type ? pwd.type = "text" : pwd.type = "password";
    })
})
document?.querySelector('form[name="login-creation-mdp-form"]')?.querySelectorAll('[name^="password"]').forEach(pwd => {
    pwd.addEventListener('input', () => {
        let pass = document?.querySelector('form[name="login-creation-mdp-form"] #login-password').value;
        let repeat = document?.querySelector('form[name="login-creation-mdp-form"] #login-password-repeat').value;
        let pwdMatchError = document?.querySelector('form[name="login-creation-mdp-form"] #password-match-error');
        let submitBtn = document?.querySelector('form[name="login-creation-mdp-form"] #submitter');
        submitBtn.addEventListener('click', (e) => {
            e.preventDefault()
        })
        if (pass !== repeat) {
            document?.querySelector('form[name="login-creation-mdp-form"]').querySelectorAll('.fr-input-group').forEach(iptGroup => {
                iptGroup.classList.add('fr-input-group--error')
                iptGroup.querySelector('.fr-input').classList.add('fr-input--error')
            })
            submitBtn.disabled = true;
            pwdMatchError.classList.remove('fr-hidden')
        } else {
            document?.querySelector('form[name="login-creation-mdp-form"]').querySelectorAll('.fr-input-group--error,.fr-input--error').forEach(iptError => {
                ['fr-input-group--error', 'fr-input--error'].map(c => {
                    iptError.classList.remove(c)
                });
            })
            pwdMatchError.classList.add('fr-hidden');
            submitBtn.disabled = false;
        }
    })
})
document.querySelector('#modal-dpe-opener')?.addEventListener('click', (event) => {
    let urlDpe = event.target.getAttribute('data-dpe-url');
    fetch(urlDpe).then(r => {
        r.json().then(res => {
            if (res.total > 1) {
                let modalDpeContent = document.querySelector('#modal-dpe-content');
                modalDpeContent.innerHTML = '';
                res.aggs.map(agg => {
                    agg.results.map(dpe => {
                        let classeDpe = dpe.classe_consommation_energie;
                        if (classeDpe !== "N") {
                            console.log(dpe)
                            let classeGes = dpe.classe_estimation_ges, col = document.createElement('div'),
                                imgDpe = document.createElement('img'), imgGes = document.createElement('img');
                            imgDpe.src = '/img/dpe_' + classeDpe + '.png';
                            imgGes.src = '/img/ges_' + classeGes + '.png';
                            modalDpeContent.append(col);
                            col.insertAdjacentHTML('beforeend', `<h5 class="fr-h3 fr-col-12 fr-mb-0">${dpe.date_etablissement_dpe}</h5>`)
                            col.insertAdjacentHTML('beforeend', `<h6>Consommation énergie ${dpe.consommation_energie}</h6>`);
                            [imgDpe, imgGes].map(img => {
                                img.classList.add('fr-col-6')
                            })
                            col.append(imgDpe, imgGes)
                        }
                    })
                })
            }
        })
    })
})

document.addEventListener("DOMContentLoaded", function() {
    // Interval to check if dsfr javascript is loaded
    const intervalValue = setInterval( histologe_autoopen_modal, 10 );
    function histologe_autoopen_modal() {
        const htmlElement = document.documentElement;
        const attributeJS = htmlElement.getAttribute("data-fr-js");
        // When loaded, kills the interval and trigger what is needed
        if (attributeJS == 'true') {
            clearInterval( intervalValue );
            dsfr(document.querySelector('.fr-modal.autoopen')).modal.disclose();
        }

    }
});

document.querySelectorAll('.value-switcher').forEach(sw => {
    sw.addEventListener(sw.getAttribute('data-action') ?? 'change', (evt => {
        let url = evt.target.getAttribute('data-url'), formData = new FormData();
        formData.append('_token', evt.target.getAttribute('data-token'))
        formData.append('item', evt.target.getAttribute('data-item'))
        formData.append('value', evt?.target?.selectedIndex ? evt?.target?.options[evt?.target?.selectedIndex]?.value : '')
        fetch(url, {
            method: 'POST',
            body: formData
        }).then(r => r.json().then(res => {
            if (res.return === 1) {
                ['fr-badge--error', 'fr-badge--success'].map(c => {
                    evt.target.classList.toggle(c);
                })
                evt.target.innerText === "OUI" ? evt.target.innerText = "NON" : evt.target.innerText = "OUI"
            }
        }))

    }))
})
