const siblingIndex=e=>{let t=0;for(;e=e.previousElementSibling;)t++;return t}

const sortTableFunction=e=>function(t){"a"==t.target.tagName.toLowerCase()&&(sortRows(e,siblingIndex(t.target.parentNode)),t.preventDefault())}
const sortRows=(e,t)=>{
    let r,a,l,n=e.querySelectorAll("tbody tr"),o="thead th:nth-child("+(t+1)+")",d="td:nth-child("+(t+1)+")",s=e.querySelector(o).classList,i=[],c="",u=!0;
    for(s&&(s.contains("date")?c="date":s.contains("number")&&(c="number")),a=0;a<n.length;a++)
        l=n[a].querySelector(d),r=l.innerText,isNaN(r)?u=!1:r=parseFloat(r),i.push({value:r,row:n[a]});
    ""==c&&u&&(c="number"),"number"==c?(i.sort(sortNumberVal),i=i.reverse()):"date"==c?i.sort(sortDateVal):i.sort(sortTextVal);
    for(let t=0;t<i.length;t++)e.querySelector("tbody").appendChild(i[t].row)
}

const sortNumberVal=(e,t)=>sortNumber(e.value,t.value)
const sortNumber=(e,t)=>{e-t}
const sortDateVal=(e,t)=>{let r=Date.parse(e.value),a=Date.parse(t.value);return sortNumber(r,a)}
const sortTextVal=(e,t)=>{let r=(e.value+"").toUpperCase(),a=(t.value+"").toUpperCase();return r<a?-1:r>a?1:0}

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