const setBadge = (el) => {
  const container = el.parentElement.querySelector('.selected__value')
  if (el.value !== '') {
    const badge = document.createElement('span')
    badge.classList.add('fr-badge', 'fr-badge--success', 'fr-m-1v')
    badge.innerText = el.selectedOptions[0].text
    const input = document.createElement('input')
    input.type = 'hidden'
    input.name = `${el.id}[]`
    input.value = el.value
    container.append(input)
    badge.setAttribute('data-value', el.value)
    container.querySelector('.fr-badge:not([data-value])')?.classList?.add('fr-hidden')
    container.append(badge)
    el.selectedOptions[0].classList.add('fr-hidden')
    badge.addEventListener('click', (event) => {
      removeBadge(badge)
    })
  } else {
    container.querySelectorAll('.fr-badge[data-value]').forEach(badge => {
      removeBadge(badge)
    })
  }
  return false
}
const removeBadge = (badge) => {
  const val = badge.getAttribute('data-value')
  const input = badge.parentElement.querySelector(`input[value="${val}"]`)
  const select = badge?.parentElement?.parentElement?.querySelector('select') ?? badge?.parentElement?.parentElement?.querySelector('input[type="date"]')
  select.querySelector(`option[value="${val}"]`)?.classList?.remove('fr-hidden')
  input?.remove()
  const badges = badge.parentElement.querySelectorAll('.fr-badge[data-value]').length !== 1
  if (!badges) {
    badge?.parentElement?.querySelector('.fr-badge:not([data-value])')?.classList?.remove('fr-hidden')
    if (select.tagName === 'SELECT') { select.options[0].selected = true }
  }
  badge.remove()
}

document?.querySelectorAll('.select-search-filter-form')?.forEach(select => {
  select.addEventListener(
    'change',
    () => {
      setBadge(select)
    },
    false
  )
})

document?.querySelectorAll('[data-removable="true"]')?.forEach(removale => {
  removale.addEventListener('click', () => {
    removeBadge(removale)
  })
})
