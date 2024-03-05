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
                if (r.ok) {
                    actionBtn.closest(className).remove()
                    if (event.target.classList.contains('partner-row-delete')) {
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