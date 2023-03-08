const formBtn = document.querySelector('#signalement-edit-nde-form-submit');

formBtn?.addEventListener('click', evt => {
    const form = document.querySelector('form#signalement-edit-nde-form');
    const url = form.action;
    const type = form.method;

    const stringToBoolean = (stringValue) => {
        switch(stringValue?.toLowerCase()?.trim()){
            case "true": 
            case "yes": 
            case "1": 
              return true;
    
            case "false": 
            case "no": 
            case "0": 
              return false;
              
            case null: 
            case undefined:
            default: 
              return null;
        }
    }

    const data = { 
        _token: document.getElementById('signalement-edit-nde-token').value,
        dateEntree: document.querySelector('input[name=dateEntree]:checked').value,
        dpe: stringToBoolean(document.querySelector('input[name=dpe]:checked').value),
        dateDernierBail: document.getElementById('signalement-edit-nde-dernier-bail').value,
        dateDernierDPE: document.getElementById('signalement-edit-nde-dpe-date').value,
        consommationEnergie: Number(document.getElementById('signalement-edit-nde-conso-energie').value),
        superficie: Number(document.getElementById('signalement-edit-nde-superficie')?.value),
    };

    const options = {
        method: type,
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
    };

    fetch(url, options)
        .then((response) => {
            if (response.ok) {
                window.location.reload();

            }
        })
        .catch((error) => {
            console.error("Error:", error);
        });
})
