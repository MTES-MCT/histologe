const HistoSlider = {
    init:function (sliderId) {
        let slider = document.querySelector(sliderId);
        let items = slider.querySelectorAll('.histoslider--items');
        let pagination = document.querySelector('#homeslider_pagination');
        let paginationItems = pagination.querySelectorAll('img');
        HistoSlider.autoSlide(slider,items,paginationItems);
    },
    autoSlide: (slider,items,paginationItems) => {
        let currentItem = 0;
        let first = items[currentItem];
        let nbItems = items.length;
        items.forEach((item,index)=>{
            item.classList.add('fr-hidden');
            paginationItems[index].src="img/bulle_v.PNG";
        })
        first.classList.remove('fr-hidden');
        paginationItems[0].src="img/bulle.PNG";
        setInterval(()=>{
            currentItem++;
            items.forEach((item,index)=>{
                item.classList.add('fr-hidden')
                paginationItems[index].src="img/bulle_v.PNG";
            })
            if (currentItem < nbItems)
            {
                items[currentItem].classList.remove('fr-hidden')
                paginationItems[currentItem].src="img/bulle.PNG";
            } else {
                first.classList.remove('fr-hidden');
                paginationItems[0].src="img/bulle.PNG";
                currentItem = 0;
            }
        },3000)
    }
}