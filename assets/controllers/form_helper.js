Node.prototype.addEventListeners = function (eventNames, eventFunction) {
    for (let eventName of eventNames.split(' '))
        this.addEventListener(eventName, eventFunction);
}

const localStorage = window.localStorage;
const uploadedFiles = [];

document.querySelectorAll('.fr-disable-button-when-submit')?.forEach(element => {
    element.addEventListener('submit', (event) => {
        if (element.checkValidity()) {
            element.querySelectorAll('button[type=submit]')?.forEach(element => {
                element.setAttribute('disabled', true);
            })
        }
    })
})

const selects = document.querySelectorAll('.fr-select-submit');
selects.forEach(select => {
    select.addEventListener('change', function() {
        this.form.submit();
    });
});

const checkFieldset=e=>{let t=e.querySelector('fieldset[aria-required="true"]');return!t||(null===t.querySelector('[type="checkbox"]:checked')?(t.classList.add("fr-fieldset--error"),t?.querySelector(".fr-error-text")?.classList.remove("fr-hidden"),invalid=t.parentElement,!1):(t.classList.remove("fr-fieldset--error"),t?.querySelector(".fr-error-text")?.classList.add("fr-hidden"),!0))}

const forms = document.querySelectorAll('form.needs-validation:not([name="bug-report"])');
forms.forEach((form) => {
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
                        ['fr-icon-camera-fill', 'fr-py-7v', 'fr-icon-refresh-line', 'fr-disabled'].map(v => event.target.parentElement.classList.toggle(v));
                        fileIsOk = true;
                    }
                } else if (event.target.parentElement.classList.contains('fr-icon-attachment-fill')) {
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
                        ['fr-icon-attachment-fill', 'fr-icon-refresh-line', 'fr-disabled'].map(v => event.target.parentElement.classList.toggle(v));
                    }
                }
                if (fileIsOk) {
                    // [preview, deleter].forEach(el => el?.classList?.remove('fr-hidden'))
                    deleter.addEventListeners('click touchdown', (e) => {
                        e.preventDefault();
                        if (preview) {
                            preview.src = '#';
                            event.target.parentElement.classList.add('fr-icon-camera-fill')
                        } else if (event.target.parentElement.classList.contains('fr-icon-checkbox-circle-fill')) {
                            ['fr-icon-attachment-fill', 'fr-icon-checkbox-circle-fill'].map(v => event.target.parentElement.classList.toggle(v));
                        } else {
                            event.target.parentElement.classList.add('fr-icon-attachment-fill');
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
                        event.target.parentElement.classList.remove('fr-icon-refresh-line');
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
                                ['fr-icon-checkbox-circle-fill'].map(v => event.target.parentElement.classList.toggle(v));
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
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!form.checkValidity() || !checkFieldset(form)) {
            event.stopPropagation();
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
            if (invalid) {
                const y = invalid.getBoundingClientRect().top + window.scrollY;
                window.scroll({
                    top: y,
                    behavior: 'smooth'
                });
            }
        } else {
            Object.keys(uploadedFiles).map((f, index) => {
                let fi = JSON.parse(uploadedFiles[f]);
                form.insertAdjacentHTML('beforeend', `<input type="hidden" name="signalement[files][${fi.key}][${fi.titre}]" value="${fi.file}">`);
            });
            form.submit();
        }
    })
})