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
let isZipForNDE = false
let hasCriticiteForNDE = false
let isNDEBail2023 = false;
let isNDEMissingInfo = false;
let isNDEDPEBefore2023 = false;
let hasDPE = false;
let totalNDEConso = -1;
let superficieNDE = -1;
localStorage.clear();
forms.forEach((form) => {
    form?.querySelectorAll('.toggle-criticite input[type="radio"]')?.forEach((criticite) => {
        criticite.addEventListener('change', (event) => {
            event.currentTarget.parentElement.parentElement.parentElement.querySelector('.fr-toggle__input').checked = true;
            // parent.querySelector('[type="checkbox"]').checked = !parent.querySelector('[type="checkbox"]').checked;
        })
    })
    form?.querySelectorAll('.fr-toggle')?.forEach((t) => {
        t.addEventListener('change', (event) => {
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
    form?.querySelectorAll('#signalement_cpOccupant')?.forEach((element) => {
        element.addEventListener('change', (event) => {
            let cpOccupant = form.querySelector('#signalement_cpOccupant').value;
            let zipOccupant = cpOccupant.substr(0, 2)
            // Only a few code postal available in territory 69
            if (zipOccupant == '69') {
                const METROPOLE_RHONES_AUTHORIZED_CODES_POSTAL = [
                    69000, 69001, 69002, 69003, 69004, 69005, 69006, 69007, 69008, 69009,
                    69100, 69125, 69190, 69200, 69290, 69381,
                    69520, 69600, 69700, 69800
                ]
                const COR_RHONES_AUTHORIZED_CODES_POSTAL = [
                    69170, 69240, 69430, 69470, 69490, 69550, 69870
                ];
                const RHONES_AUTHORIZED_CODES_POSTAL = METROPOLE_RHONES_AUTHORIZED_CODES_POSTAL.concat(
                    COR_RHONES_AUTHORIZED_CODES_POSTAL
                );
                if (RHONES_AUTHORIZED_CODES_POSTAL.indexOf(Number(cpOccupant)) == -1) {
                    form.querySelector('#fr-error-text-code-postal')?.classList?.remove('fr-hidden');
                } else {
                    form.querySelector('#fr-error-text-code-postal')?.classList?.add('fr-hidden');
                }
            }

            // Zip codes available for Non Conformité Energétique
            isZipForNDE = (zipOccupant == '63' || zipOccupant == '89');

            refetchAddress(form)
        })
    })
    form?.querySelectorAll('#signalement_villeOccupant')?.forEach((element) => {
        element.addEventListener('change', (event) => {
            refetchAddress(form)
        })
    })
    form?.querySelectorAll('#form-nde input')?.forEach((element) => {
        element.addEventListener('change', (event) => {
            let isEntreeSelected = false;
            let isDPESelected = false;
            let isDateBailSelected = false;

            let isEntree2023 = false;
            let isEntreeBefore2023 = false;
            hasDPE = false;
            let isDateDPE2023 = false;
            let isDateDPEBefore2023 = false;
            isNDEMissingInfo = false;

            form.querySelectorAll('#form-nde input[name="signalement[dateEntree]"]')?.forEach((element) => {
                if (element.checked) {
                    isEntreeSelected = true;
                    isEntree2023 = (element.value === '2023-01-02')
                    isNDEBail2023 = isEntree2023
                    isEntreeBefore2023 = (element.value === '1970-01-01')
                }
            })
            if (isEntreeSelected) {
                if (isEntreeBefore2023) {
                    form.querySelectorAll('#form-nde input[name="signalement[dateBail]"]')?.forEach((element) => {
                        if (element.checked) {
                            isDateBailSelected = true;
                            isNDEBail2023 = (element.value === '2023-01-02');
                            isNDEMissingInfo = (element.value === 'Je ne sais pas');
                        }
                    })
                }
                if (isNDEBail2023) {
                    form.querySelectorAll('#form-nde input[name="signalement[hasDPE]"]')?.forEach((element) => {
                        if (element.checked) {
                            isDPESelected = true;
                            hasDPE = (element.value === '1');
                            isNDEMissingInfo = (element.value === '');
                        }
                    })
                }
                if (hasDPE) {
                    form.querySelectorAll('#form-nde input[name="signalement[dateDPE]"]')?.forEach((element) => {
                        if (element.checked) {
                            isDateDPE2023 = (element.value === '2023-01-02')
                            isDateDPEBefore2023 = (element.value === '1970-01-01')
                        }
                    })
                }
            }

            // Reinit display
            form.querySelector('#form-nde .display-if-entree-2023')?.classList.add('fr-hidden');
            form.querySelector('#form-nde .display-if-entree-before-2023')?.classList.add('fr-hidden');
            form.querySelector('#form-nde .display-if-has-dpe')?.classList.add('fr-hidden');
            form.querySelector('#form-nde .display-if-dpe-2023')?.classList.add('fr-hidden');
            form.querySelector('#form-nde .display-if-dpe-before-2023')?.classList.add('fr-hidden');
            form.querySelector('#form-nde .display-if-conso-complete')?.classList.add('fr-hidden');

            form.querySelector('#form-nde .display-if-missing-info')?.classList.add('fr-hidden');
            form.querySelector('#form-nde .display-if-bail-before-2023')?.classList.add('fr-hidden');
            form.querySelector('#form-nde .display-if-not-nde')?.classList.add('fr-hidden');
            form.querySelector('#form-nde .display-if-nde')?.classList.add('fr-hidden');
            form.querySelector('#form-nde .display-if-finished')?.classList.add('fr-hidden');

            // Logical display
            if (isEntree2023) {
                form.querySelector('#form-nde .display-if-entree-2023')?.classList.remove('fr-hidden');

            } else if (isEntreeBefore2023) {
                form.querySelector('#form-nde .display-if-entree-before-2023')?.classList.remove('fr-hidden');

                if (isNDEBail2023) {
                    form.querySelector('#form-nde .display-if-entree-2023')?.classList.remove('fr-hidden');
                } else if (isNDEMissingInfo) {
                    form.querySelector('#form-nde .display-if-missing-info')?.classList.remove('fr-hidden');
                    form.querySelector('#form-nde .display-if-finished')?.classList.remove('fr-hidden');
                    return;
                } else if (isDateBailSelected) {
                    form.querySelector('#form-nde .display-if-bail-before-2023')?.classList.remove('fr-hidden');
                    form.querySelector('#form-nde .display-if-finished')?.classList.remove('fr-hidden');
                    return;
                }
            }

            if (hasDPE) {
                form.querySelector('#form-nde .display-if-has-dpe')?.classList.remove('fr-hidden');
                if (isDateDPE2023) {
                    form.querySelector('#form-nde .display-if-dpe-2023')?.classList.remove('fr-hidden');
                    form.querySelector('#form-nde button.calculate-conso')?.click();
    
                } else if (isDateDPEBefore2023) {
                    form.querySelector('#form-nde .display-if-dpe-before-2023')?.classList.remove('fr-hidden');
                    form.querySelector('#form-nde button.calculate-conso')?.click();
                }
            } else if (isNDEMissingInfo) {
                form.querySelector('#form-nde .display-if-missing-info')?.classList.remove('fr-hidden');
                form.querySelector('#form-nde .display-if-finished')?.classList.remove('fr-hidden');
                return;
            } else if (isDPESelected) {
                form.querySelector('#form-nde .display-if-nde')?.classList.remove('fr-hidden');
                form.querySelector('#form-nde .display-if-finished')?.classList.remove('fr-hidden');
                return;
            }
        })
    })
    form?.querySelectorAll('#form-nde button.calculate-conso')?.forEach((element) => {
        element.addEventListener('click', (event) => {
            form.querySelector('#form-nde .display-if-conso-complete')?.classList.add('fr-hidden');
            form.querySelector('#form-nde .display-if-finished')?.classList.add('fr-hidden');
            form.querySelector('#form-nde .fr-error-consoSizeYear')?.classList.add('fr-hidden');
            form.querySelector('#form-nde .fr-error-consoSizePlusYear')?.classList.add('fr-hidden');

            let isDateDPE2023 = false;
            let isDateDPEBefore2023 = false;
            form.querySelectorAll('#form-nde input[name="signalement[dateDPE]"]')?.forEach((element) => {
                if (element.checked) {
                    isDateDPE2023 = (element.value === '2023-01-02')
                    isDateDPEBefore2023 = (element.value === '1970-01-01')
                }
            })

            let consoSizeYear = -1;
            isNDEDPEBefore2023 = false;
            totalNDEConso = -1;
            if (isNDEBail2023) {
                if (isDateDPE2023) {
                    let consoSizeYearTxt = form.querySelector('#form-nde input[name="signalement[consoSizeYear]"]')?.value;
                    if (consoSizeYearTxt.length > 0) {
                        if (isNaN(consoSizeYear)) {
                            form.querySelector('#form-nde .fr-error-consoSizeYear')?.classList.remove('fr-hidden');
                        } else {
                            consoSizeYear = consoSizeYearTxt;
                            form.querySelector('#form-nde .display-if-conso-complete span').textContent = consoSizeYear;
                            form.querySelector('#form-nde .display-if-conso-complete')?.classList.remove('fr-hidden');
                            form.querySelector('#form-nde .display-if-finished')?.classList.remove('fr-hidden');
                        }
                    } else {
                        form.querySelector('#form-nde .display-if-conso-complete')?.classList.add('fr-hidden');
                        form.querySelector('#form-nde .display-if-finished')?.classList.add('fr-hidden');
                    }
                } else if (isDateDPEBefore2023) {
                    isNDEDPEBefore2023 = true;
                    superficieNDE = form.querySelector('#form-nde input[name="signalement[consoSize]"]')?.value;
                    let consoYear = form.querySelector('#form-nde input[name="signalement[consoYear]"]')?.value;
                    if (superficieNDE.length > 0 && consoYear.length > 0) {
                        if (isNaN(superficieNDE) || isNaN(consoYear)) {
                            form.querySelector('#form-nde .fr-error-consoSizePlusYear')?.classList.remove('fr-hidden');
                        } else {
                            consoSizeYear = Math.round(consoYear / superficieNDE, 2);
                            form.querySelector('#form-nde .display-if-conso-complete span').textContent = consoSizeYear;
                            form.querySelector('#form-nde .display-if-conso-complete')?.classList.remove('fr-hidden');
                            form.querySelector('#form-nde .display-if-finished')?.classList.remove('fr-hidden');
                        }
                    } else {
                        form.querySelector('#form-nde .display-if-conso-complete')?.classList.add('fr-hidden');
                        form.querySelector('#form-nde .display-if-finished')?.classList.add('fr-hidden');
                    }
                }
    
                if (consoSizeYear > -1) {
                    totalNDEConso = consoSizeYear;
                    if (consoSizeYear > 450) {
                        form.querySelector('#form-nde .display-if-nde')?.classList.remove('fr-hidden');
                        form.querySelector('#form-nde .display-if-not-nde')?.classList.add('fr-hidden');
                    } else {
                        form.querySelector('#form-nde .display-if-nde')?.classList.add('fr-hidden');
                        form.querySelector('#form-nde .display-if-not-nde')?.classList.remove('fr-hidden');
                    }
                }
            }
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
            if (event.target.files.length > 0) {
                let resTextEl = event.target.parentElement.nextElementSibling;
                let fileData = new FormData();
                let deleter = event.target.parentElement.parentElement.querySelector('.signalement-uploadedfile-delete'),
                    /*src = URL.createObjectURL(event.target.files[0]),*/
                    preview = event.target?.parentElement?.querySelector('img'),
                    fileIsOk = false, file = event.target.files[0];
                let id = event.target.id;
                let progress = document.querySelector("#progress_" + id);
                let totalProgress = document.querySelector('#form_global_file_progress');
                if (preview) {
                    if (event.target.files[0].type === 'image/heic' || event.target.files[0].type === 'image/heif') {
                        event.target.value = "";
                        resTextEl.innerHTML = "Les fichiers de format HEIC/HEIF ne sont pas pris en charge, merci de convertir votre image en JPEG ou en PNG avant de l'envoyer.";
                        resTextEl.classList.remove('fr-hidden')
                    }  else if (event.target.files[0].size > 10 * 1024 * 1024) {
                        event.target.value = "";
                        resTextEl.innerHTML = "L'image dépasse 10MB";
                        resTextEl.classList.remove('fr-hidden')
                    } else {
                        preview.src = URL.createObjectURL(file);
                        ['fr-fi-instagram-line', 'fr-py-7v', 'fr-fi-refresh-line', 'fr-disabled'].map(v => event.target.parentElement.classList.toggle(v));
                        fileIsOk = true;
                    }
                } else if (event.target.parentElement.classList.contains('fr-fi-attachment-fill')) {
                    if (event.target.files[0].type === 'image/heic' || event.target.files[0].type === 'image/heif') {
                        event.target.value = "";
                        resTextEl.innerHTML = "Les fichiers de format HEIC/HEIF ne sont pas pris en charge, merci de convertir votre image en JPEG ou en PNG avant de l'envoyer.";
                        resTextEl.classList.remove('fr-hidden')
                    } else if (event.target.files[0].size > 10 * 1024 * 1024) {
                        event.target.value = "";
                        resTextEl.innerHTML = "Le document dépasse 10MB";
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
                        console.log('progress');
                        console.log(e);
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
                        progress.value = percent_completed;
                    });
                    request.addEventListener('load', function (e) {
                        console.log('load');
                        console.log(e);
                        event.target.parentElement.classList.remove('fr-fi-refresh-line');
                        [preview, deleter].forEach(el => el?.classList?.remove('fr-hidden'));
                        progress.value = 0;
                        let jsonRes = JSON.parse(request.response)
                        if (request.status !== 200) {
                            progress.value = 0;
                            deleter.click();
                            resTextEl.innerText = jsonRes.error;
                            resTextEl.classList.remove('fr-hidden');
                            resTextEl.classList.add('fr-text-label--red-marianne');
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
        if (!form.checkValidity() || !checkFirstStep(form) || !checkFieldset(form)) {
            event.stopPropagation();
            if (form.id === "signalement-step-2") {
                form.querySelector('[role="alert"]').classList.remove('fr-hidden')
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
                    let inseeParam = '';
                    if (form.querySelector('#signalement-insee-occupant').value != undefined && form.querySelector('#signalement-insee-occupant').value != '') {
                        inseeParam = '&insee='+form.querySelector('#signalement-insee-occupant').value;
                    }
                    fetch('checkterritory?cp='+form.querySelector('#signalement_cpOccupant').value+inseeParam).then(r=> r.json()).then(r=> {
                        if(r.success)
                        {
                            nextTabBtn.disabled = false;
                            nextTabBtn.click();
                        } else {
                           dsfr(document.querySelector('#fr-modal-closed-territory')).modal.disclose();
                        }
                    })
                } else {
                    if (form.id === "signalement-step-3") {
                        if (isZipForNDE && hasCriticiteForNDE) {
                            nextTabBtn = document.querySelector('#signalement-step-3b-btn');
                            nextTabBtn.classList.remove('fr-hidden');
                            document.querySelector('#signalement-step-4-btn > span').textContent = '5';
                            document.querySelector('#signalement-step-last-btn > span').textContent = '6';
                        } else {
                            nextTabBtn = document.querySelector('#signalement-step-4-btn');
                            document.querySelector('#signalement-step-4-btn > span').textContent = '4';
                            document.querySelector('#signalement-step-last-btn > span').textContent = '5';
                        }
                    }
                    if (form.id === "signalement-step-3b") {
                        if (superficieNDE > -1) {
                            document.querySelector('#signalement_superficie').value = superficieNDE;
                        }
                    }

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

                            // Reinit display for non-décence
                            updateResultNDE();
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
                                            window.scroll({
                                                top: 0,
                                                behavior: 'smooth'
                                            });
                                        })
                                        localStorage.clear();
                                    } else if (res.response === "success_edited") {
                                        localStorage.clear();
                                        window.location.reload();
                                    } else {
                                        event.target.querySelector('[type="submit"]').disabled = false;
                                        event.target.querySelector('[type="submit"]').innerHTML = "Confirmer";
                                        ['fr-fi-checkbox-circle-fill', 'fr-fi-refresh-fill'].map(v => event.target.querySelector('[type="submit"]').classList.toggle(v));
                                        alert('Erreur lors de l\'enregistrement du signalement !')

                                    }
                                })
                            } else {
                                r.text().then(r=>{
                                    console.log(r)
                                })
                                event.target.querySelector('[type="submit"]').disabled = false;
                                event.target.querySelector('[type="submit"]').innerHTML = "Confirmer";
                                ['fr-fi-checkbox-circle-fill', 'fr-fi-refresh-fill'].map(v => event.target.querySelector('[type="submit"]').classList.toggle(v));
                                alert('Suite à un incident technique, votre signalement n\'a pas pu être enregistré. Nous vous invitons à réessayer dans une heure.')
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

        // Browse all options to check if one nte is checked
        hasCriticiteForNDE = false;
        document?.querySelectorAll('.toggle-criticite-smiley').forEach(elmtSmiley => {
            if (elmtSmiley.checked && elmtSmiley.dataset.nde !== undefined) {
                hasCriticiteForNDE = true;
            }
        })
    })
})
document?.querySelector('#signalement-step-2-panel')?.addEventListener('dsfr.disclose', (ev => {
    ev.target.querySelectorAll('[aria-expanded]').forEach(exp => {
        if (localStorage.getItem(exp.id)) { // noinspection CommaExpressionJS
            document.querySelector('#' + exp.id).setAttribute('aria-expanded', "true"), localStorage.removeItem(exp.id)
        }
    })
}))
document?.querySelector('#signalement-step-last-panel')?.addEventListener('dsfr.disclose', (ev => {
    updateResultNDE();
}))
document?.querySelectorAll(".fr-pagination__link").forEach((e => {
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
})

document?.querySelectorAll('.fr-input--file-signalement').forEach(inputFile => {
    inputFile.addEventListener('change', evt => {
        const files = evt.target.files;
        let uploadValid = true;
        Array.from(files).forEach((file) => {
            if (file.size > 10 * 1024 * 1024) {
                file.value = '';
                uploadValid = false;

                const div = document.createElement('div');
                div.setAttribute('role', 'alert');
                div.classList.add('fr-alert','fr-alert--error','fr-alert--sm');

                const p = document.createElement('p');
                p.textContent = 'Le fichier est trop lourd. Veuillez ré-essayer avec un fichier de 10Mo maximum.';
                div.append(p);

                const parent = document.querySelector('.fr-col-12.fr-col-md-9.fr-col-lg-10');
                parent.prepend(div);

                window.scrollTo(0, 0);
                setTimeout(() => {
                    div.remove();
                }, 7000);
            }
        });
        if (uploadValid) {
            evt.target.form.submit();
        }
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
    const intervalValue = setInterval( histologeAutoopenModal, 200 );
    function histologeAutoopenModal() {
        const htmlElement = document.documentElement;
        const attributeJS = htmlElement.getAttribute('data-fr-js');
        // When loaded, kills the interval and trigger what is needed
        if (attributeJS === 'true') {
            clearInterval( intervalValue );
            // Open modals if they exist
            const modalElements = document.getElementsByClassName('fr-modal autoopen');
            if ( modalElements.length > 0 ){
                dsfr(document.querySelector('.fr-modal.autoopen')).modal.disclose();
            }
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

const refetchAddress = (form) => {
    // If the code postal is manually edited, we reinit the insee/geoloc and fetch the first result
    form.querySelector('#signalement-insee-occupant').value = '';
    form.querySelector('#signalement-geoloc-lat-occupant').value = '';
    form.querySelector('#signalement-geoloc-lng-occupant').value = '';
    let addressComplete = form.querySelector('#signalement_adresseOccupant').value
    addressComplete += ' ' + form.querySelector('#signalement_cpOccupant').value
    addressComplete += ' ' + form.querySelector('#signalement_villeOccupant').value
    fetch('https://api-adresse.data.gouv.fr/search/?q=' + addressComplete).then((res) => {
        res.json().then((r) => {
            let feature = r.features[0];
            form.querySelector('#signalement-insee-occupant').value = feature.properties.citycode;
            let zipOccupant = feature.properties.citycode.substr(0, 2)
            if (zipOccupant == '69') {
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
            }

            // Zip codes available for Non Conformité Energétique
            isZipForNDE = (zipOccupant == '63' || zipOccupant == '89');

            form.querySelector('#signalement-geoloc-lat-occupant').value = feature.geometry.coordinates[0];
            form.querySelector('#signalement-geoloc-lng-occupant').value = feature.geometry.coordinates[1];
        })
    })
}

const updateResultNDE = () => {
    document.querySelector('#result-nde').classList.add('fr-hidden');
    document.querySelectorAll('#result-nde .display-if-missing-info').forEach(el => {
        el.classList.add('fr-hidden')
    })
    document.querySelectorAll('#result-nde .display-if-dpe-before-2023').forEach(el => {
        el.classList.add('fr-hidden')
    })
    document.querySelectorAll('#result-nde .display-if-conso').forEach(el => {
        el.classList.add('fr-hidden')
    })
    document.querySelectorAll('#result-nde .display-if-not-nde').forEach(el => {
        el.classList.add('fr-hidden')
    })
    document.querySelectorAll('#result-nde .display-if-nde').forEach(el => {
        el.classList.add('fr-hidden')
    })
    document.querySelectorAll('#result-nde .display-if-nde-no-dpe').forEach(el => {
        el.classList.add('fr-hidden')
    })
    // Results for non-décence
    if (isZipForNDE && hasCriticiteForNDE && isNDEBail2023) {
        document.querySelector('#result-nde').classList.remove('fr-hidden');
        if (isNDEMissingInfo) {
            document.querySelectorAll('#result-nde .display-if-missing-info').forEach(el => {
                el.classList.remove('fr-hidden')
            })
        } else if (!hasDPE) {
            document.querySelectorAll('#result-nde .display-if-nde-no-dpe').forEach(el => {
                el.classList.remove('fr-hidden')
            })
           
        } else if (isNDEBail2023 && totalNDEConso > -1) {
            if (isNDEDPEBefore2023) {
                document.querySelectorAll('#result-nde .display-if-dpe-before-2023').forEach(el => {
                    el.classList.remove('fr-hidden')
                })
            }
            document.querySelectorAll('#result-nde .display-if-conso').forEach(el => {
                el.classList.remove('fr-hidden')
            })
            document.querySelector('#result-nde .conso-amount').textContent = totalNDEConso;
            if (totalNDEConso > 450) {
                document.querySelectorAll('#result-nde .display-if-nde').forEach(el => {
                    el.classList.remove('fr-hidden')
                })
            } else {
                document.querySelectorAll('#result-nde .display-if-not-nde').forEach(el => {
                    el.classList.remove('fr-hidden')
                })
            }
        } else {
            document.querySelector('#result-nde').classList.add('fr-hidden');
        }
        
    }
}