// These functions were taken from an old minified file
const siblingIndex = (e) => {
  let t = 0;
  for (; (e = e.previousElementSibling); ) t++;
  return t;
};

const sortTableFunction = (table) =>
  function (event) {
    if (event.target.tagName.toLowerCase() === 'a') {
      sortRows(table, siblingIndex(event.target.parentNode));
      event.preventDefault();
    }
  };
const sortRows = (table, index) => {
  let r, a, l;
  const n = table.querySelectorAll('tbody tr');
  const o = 'thead th:nth-child(' + (index + 1) + ')';
  const d = 'td:nth-child(' + (index + 1) + ')';
  const classList = table.querySelector(o).classList;
  const isDesc = classList.contains('desc');
  if (!isDesc) {
    classList.add('desc');
  } else {
    classList.remove('desc');
  }
  let i = [];
  let c = '';
  let u = !0;
  for (
    classList &&
      (classList.contains('date') ? (c = 'date') : classList.contains('number') && (c = 'number')),
    a = 0;
    a < n.length;
    a++
  ) {
    l = n[a].querySelector(d);
    r = l.innerText;
    isNaN(r) ? (u = !1) : (r = parseFloat(r));
    i.push({ value: r, row: n[a] });
  }
  c === '' && u && (c = 'number');
  if (c === 'number') {
    i.sort(sortNumberVal);
    i = i.reverse();
  } else if (c === 'date') {
    i.sort(sortDateVal);
  } else {
    i.sort(sortTextVal);
  }

  if (isDesc) {
    i = i.reverse();
  }
  for (let t = 0; t < i.length; t++) {
    table.querySelector('tbody').appendChild(i[t].row);
  }
};

const sortNumberVal = (e, t) => sortNumber(e.value, t.value);
const sortNumber = (e, t) => {
  return t - e;
};
const sortDateVal = (e, t) => {
  const r = Date.parse(e.value);
  const a = Date.parse(t.value);
  return sortNumber(r, a);
};
const sortTextVal = (e, t) => {
  const r = (e.value + '').toUpperCase();
  const a = (t.value + '').toUpperCase();
  return r < a ? -1 : r > a ? 1 : 0;
};

// encore utilisé dans les logs iDoss, les règles d'auto-affectation, et le simulator d'auto-affectation
// essayer de les supprimer (recherche sur cancelSortable à faire)
const tables = document.querySelectorAll('table.sortable');
let table;
let thead;
let headers;
for (let iTables = 0; iTables < tables.length; iTables++) {
  table = tables[iTables];

  thead = table.querySelector('thead');
  if (thead) {
    headers = thead.querySelectorAll('th');

    for (let jHeaders = 0; jHeaders < headers.length; jHeaders++) {
      headers[jHeaders].innerHTML = '<a href=\'#\'>' + headers[jHeaders].textContent + '</a>';
    }

    thead.addEventListener('click', sortTableFunction(table));
  }
}
