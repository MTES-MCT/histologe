// This function was taken from an old minified file
document?.querySelectorAll('.fr-pagination__link').forEach(e => {
  let t; let r; let a; const n = document.querySelector('.fr-pagination__link--prev')
  const i = document.querySelector('.fr-pagination__link--next')
  const u = document.querySelector('.fr-pagination__link--first')
  const l = document.querySelector('.fr-pagination__link--last'); let o = 1; const c = parseInt(l.getAttribute('data-page'))
  e.addEventListener('click', e => {
    const p = new FormData(document.querySelector('form[name="bo-filters-form"]'))
    p.append('pagination', 'true')
    const d = document?.querySelector('.fr-pagination__link[aria-current]'); const g = e.target
    g !== n && g !== i && g !== u && g !== l ? o = parseInt(g.getAttribute('data-page')) : g === i ? o = parseInt(d.getAttribute('data-page')) + 1 : g === n ? o = parseInt(d.getAttribute('data-page')) - 1 : g === l ? o = parseInt(c) : g === u && (o = parseInt(1)), p.append('page', o), t = document.querySelector('.fr-pagination__link[data-page="' + o + '"]'), fetch('#', {
      method: 'POST',
      body: p
    }).then(e => e.text().then(e => {
      const p = document.querySelector('#signalements-result')
      p.innerHTML = e, d.removeAttribute('aria-current'), d.href = '#', t.removeAttribute('href'), t.setAttribute('aria-current', 'page'), o !== 1 && o !== c ? r = [u, n, i, l] : o === 1 ? (r = [i, l], a = [u, n]) : o === c && (r = [u, n], a = [i, l]), r.forEach(e => {
        e.removeAttribute('aria-disabled'), e.href = '#'
      }), a && a.forEach(e => {
        e.removeAttribute('href'), e.setAttribute('aria-disabled', 'true')
      })
    }))
  })
})
