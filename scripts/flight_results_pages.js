function next_page() {  // TODO: Fix bug
    let child_nodes = document.getElementById("flightResults").children;
    let page_count_arr = document.getElementById("page_count").innerHTML.split('/');
    let printed_offers = parseInt(page_count_arr[0]);
    let total_offers = parseInt(page_count_arr[1]);

    if (printed_offers !== total_offers) {
        let oracle = 5;
        while (oracle) {
            if (printed_offers + oracle <= total_offers) {
                let i = 0;
                while (i < child_nodes.length) {
                    let elem = child_nodes.item(i);
                    let j = i/2;
                    if (j < printed_offers) {
                        elem.style.display = "none";
                    } else if (j >= printed_offers && j < printed_offers + oracle && elem.className == "flightResult vcenter") {
                        elem.style.display = "flex";
                    } else if (j >= printed_offers + oracle) {
                        elem.style.display = "none";
                    }
                    i+=2;
                }
                printed_offers += oracle;
                document.getElementById("page_count").innerHTML = printed_offers + '/' + total_offers;
                break
            } else {
                oracle -= 1;
            }
        }
    }
}

function previous_page() { // 0 1 2 3 4 | 5 6 7 8 9 | 10 11 12 13 14 |
    let child_nodes = document.getElementById("flightResults").children;
    let page_count_arr = document.getElementById("page_count").innerHTML.split('/');
    let printed_offers = parseInt(page_count_arr[0]);
    let total_offers = parseInt(page_count_arr[1]);
    let oracle = 5;

    if (printed_offers !== 1 && printed_offers > oracle) {
        while (oracle) {
            if (printed_offers - oracle >= 1) { // && printed_offers >= (oracle * 2) - 1
                let i = 0;
                while(i < child_nodes.length) {
                    let elem = child_nodes.item(i);
                    let j = i/2;
                    if (j < printed_offers - (oracle * 2)) {
                        elem.style.display = "none";
                    } else if (j >= printed_offers - (oracle * 2) && j < printed_offers - oracle && elem.className == "flightResult vcenter") {
                        elem.style.display = "flex";
                    } else if (j >= printed_offers - oracle) {
                        elem.style.display = "none";
                    }
                    i+=2;
                }
                printed_offers -= oracle;
                document.getElementById("page_count").innerHTML = printed_offers + '/' + total_offers;
                break
            } else {
                oracle -= 1;
            }
        }
    }
}
