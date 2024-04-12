let invalid, tables = document.querySelectorAll("table.sortable"),
    table,
    thead,
    headers;
for (let iTables = 0; iTables < tables.length; iTables++) {
    table = tables[iTables];

    if (thead = table.querySelector("thead")) {
        headers = thead.querySelectorAll("th");

        for (let jHeaders = 0; jHeaders < headers.length; jHeaders++) {
            headers[jHeaders].innerHTML = "<a href='#'>" + headers[jHeaders].innerText + "</a>";
        }

        thead.addEventListener("click", sortTableFunction(table));
    }
}

forms.forEach((form) => {
    form?.querySelectorAll('.fr-accordion__title')?.forEach((situation) => {
        situation.addEventListeners("click touchdown", (event) => {
            event.target.parentElement.parentElement.querySelectorAll('[type="radio"],[type="checkbox"]').forEach((ipt) => {
                ipt.checked = false;

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
            p.innerHTML = e, d.removeAttribute('aria-current'), d.href = "#", t.removeAttribute("href"), t.setAttribute('aria-current', 'page'), 1 !== o && o !== c ? r = [u, n, i, l] : 1 === o ? (r = [i, l], a = [u, n]) : o === c && (r = [u, n], a = [i, l]), r.forEach((e => {
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
    
    let formData = new FormData(e.target.form);
    fetch(e.target.getAttribute('formaction'), {
        method: 'POST',
        body: formData
    }).then(r => {
        if (r.ok) {
            window.location.reload(true)
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
                if (r.ok) {
                    if (className && className !== undefined && className !== null){
                        actionBtn?.closest(className).remove()
                    }
                    if (event.target.classList.contains('partner-row-delete')
                        || event.target.classList.contains('suivi-row-delete')) {
                        window.location.reload(true)
                    }
                }
            })
        }
    })
});

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