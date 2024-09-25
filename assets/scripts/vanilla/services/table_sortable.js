// These functions were taken from an old minified file
const siblingIndex=e=>{
    let t=0;for(;e=e.previousElementSibling;)t++;return t
}

const sortTableFunction=table=>function(event){
    if ("a"==event.target.tagName.toLowerCase()) {
        sortRows(table,siblingIndex(event.target.parentNode));
        event.preventDefault();
    }
}
const sortRows=(table,index)=>{
    let r,a,l;
    let n=table.querySelectorAll("tbody tr");
    let o="thead th:nth-child("+(index+1)+")";
    let d="td:nth-child("+(index+1)+")";
    let classList = table.querySelector(o).classList;
    let isDesc = classList.contains('desc')
    if (!isDesc) {
        classList.add('desc')
    } else {
        classList.remove('desc')
    }
    let i=[],c="",u=!0;
    for(classList&&(classList.contains("date")?c="date":classList.contains("number")&&(c="number")),a=0;a<n.length;a++)
        l=n[a].querySelector(d),r=l.innerText,isNaN(r)?u=!1:r=parseFloat(r),i.push({value:r,row:n[a]});
    ""==c&&u&&(c="number"),
    "number"==c?(i.sort(sortNumberVal),i=i.reverse()):"date"==c?i.sort(sortDateVal):i.sort(sortTextVal);

    if (isDesc) {
        i=i.reverse()
    }
    for(let t=0;t<i.length;t++) {
        table.querySelector("tbody").appendChild(i[t].row)
    }
}

const sortNumberVal=(e,t)=>sortNumber(e.value,t.value)
const sortNumber=(e,t)=>{ return t-e }
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