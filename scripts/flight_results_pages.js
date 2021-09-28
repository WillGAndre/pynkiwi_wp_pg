const MAX_FLIGHTS_PER_PAGE = 5;

function next_page() {
    let child_nodes = document.getElementById("flightResults").children;
    let page_count_arr = document.getElementById("page_count").innerHTML.split('/');
    let printed_offers = parseInt(page_count_arr[0]);
    let total_offers = parseInt(page_count_arr[1]);

    if (printed_offers <= total_offers) {
        let i = 0;
        while (i < child_nodes.length) {
            let elem = child_nodes.item(i);
            let j = i/2;
            if (j < printed_offers) {
                elem.style.display = "none";
            } else if (j >= printed_offers && j < printed_offers + MAX_FLIGHTS_PER_PAGE && elem.className == "flightResult vcenter") {
                elem.style.display = "flex";
            } else if (j >= printed_offers + MAX_FLIGHTS_PER_PAGE) {
                elem.style.display = "none";
            }
            i+=2;
        }
        if (total_offers - printed_offers < MAX_FLIGHTS_PER_PAGE) {
            printed_offers += (total_offers - printed_offers);
        } else {
            printed_offers += MAX_FLIGHTS_PER_PAGE;
        }
        document.getElementById("page_count").innerHTML = printed_offers + '/' + total_offers;
    }
}

function previous_page() { // 0 1 2 3 4 | 5 6 7 8 9 | 10 11 12 13 14 |  
    let child_nodes = document.getElementById("flightResults").children;
    let page_count_arr = document.getElementById("page_count").innerHTML.split('/');
    let printed_offers = parseInt(page_count_arr[0]);
    let total_offers = parseInt(page_count_arr[1]);

    if (printed_offers >= MAX_FLIGHTS_PER_PAGE * 2 || total_offers < MAX_FLIGHTS_PER_PAGE * 2) {
        let i = 0;
        while(i < child_nodes.length) {
            let elem = child_nodes.item(i);
            let j = i/2;
            if (j < printed_offers - (MAX_FLIGHTS_PER_PAGE * 2)) {
                elem.style.display = "none";
            } else if (j >= printed_offers - (MAX_FLIGHTS_PER_PAGE * 2) && j < printed_offers - MAX_FLIGHTS_PER_PAGE && elem.className == "flightResult vcenter") {
                elem.style.display = "flex";
            } else if (j >= printed_offers - MAX_FLIGHTS_PER_PAGE) {
                elem.style.display = "none";
            }
            i+=2;
        }
        printed_offers -= MAX_FLIGHTS_PER_PAGE;
        document.getElementById("page_count").innerHTML = printed_offers + '/' + total_offers;
    }
}

