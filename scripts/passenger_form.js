/*
    TODO:
    ---> Refactor code, replace global vars
        with class that is constructed the 
        same way as init().

    ---> Add user checkout before 'Payment' bt.
*/

function show_curr_offer() {
    let pass_info_elem = document.getElementById("pass_info");
    let cur_offer_elem = document.getElementById("container_offer");
    let pass_form_prev_bt = document.getElementById("prev_bt");
    if (cur_offer_elem != null) {
        if (cur_offer_elem.style.display == "none") {
            pass_info_elem.style.display = "none";
            pass_form_prev_bt.style.display = "none";
            cur_offer_elem.style.display = "block";
        }
    }
}

function show_pass_form() {
    let pass_info_elem = document.getElementById("pass_info");
    let cur_offer_elem = document.getElementById("container_offer");
    let prev_bt = document.getElementById("prev_bt");
    if (pass_info_elem != null) {
        if (pass_info_elem.style.display == "none") {
            pass_info_elem.style.display = "block";
            prev_bt.style.display = "inline-flex";
            cur_offer_elem.style.display = "none";
        }
    }
}

function show_checkout() {
    let checkout_elem = document.getElementById("checkout");
    let pass_info_elem = document.getElementById("pass_info");
    let prev_bt = document.getElementById("prev_bt");
    if (checkout_elem != null) {
        if (checkout_elem.style.display == "none") {
            prev_bt.style.display = "none";
            pass_info_elem.style.display = "none";
            checkout_elem.style.display = "flex";
            print_checkout_html();
        }
    }
}

/**
 * Script used to create list of passengers,
 * add new passengers and clear the current list.
 */
let passenger_list = [];
let passenger_ids = [];
let passenger_types = [];

/**
 * Every infant needs to be
 * allocated to a single 
 * unique adult, this array
 * holds each id relative to
 * an infant. Before payment
 * this arr must be empty.
 */
let infants_not_allocated = [];
let infants_allocated = [];

let init_flag = 1;
function init() {
    get_passenger_ids();
    debug_pass_info();
}

function get_passenger_ids() {
    console.log('\t- Setting passenger ids');
    let index = 0;
    while (document.getElementById("pass_" + index + "_id") != null) {
        let id = document.getElementById("pass_" + index + "_id").innerHTML;
        let type = document.getElementById("pass_" + index + "_type").innerHTML;
        passenger_ids.push(id);
        passenger_types.push(type);
        if (type === "infant_without_seat") {
            infants_not_allocated.push(id);
        }
        index++;
    }
}

/**
 * Function triggered onchange of input[type="date"],
 * if current passenger age <= 1 (infant), remove
 * services and infant checkbox.
 */
function check_age() {
    console.log('\t- Checking age');
    let input_date = document.getElementById('entry-bday').value;
    let age = get_age(input_date);

    console.log('\t- Input date: ' + input_date + ' ; Age: ' + age);
    let infant_discl = document.getElementById("infant-discl");
    let services = document.getElementById('services');
    if (infant_discl != null && services != null) {
        if (age <= 1) {
            infant_discl.style.opacity = 0.22;
            infant_discl.disabled = true;
            services.style.opacity = 0.22;
            services.disabled = true;
        } else {
            infant_discl.style.opacity = 1;
            infant_discl.disabled = false;
            services.style.opacity = 1;
            services.disabled = false;
        }
    }
}

// TODO: Add previous bt to go back
function print_checkout_html() {
    let offer_id = document.getElementById("curr_offer_id").innerHTML;
    let offer_amount = document.getElementById("offer_payment").innerHTML;
    let checkout_elem = document.getElementById("checkout");
    if (checkout_elem != null) {
        checkout_elem.innerHTML = '<div class=\'checkout-entry\'>Offer '+offer_id+': '+offer_amount+'</div>';
        checkout_elem.innerHTML += print_services_checkout_html();
        checkout_elem.innerHTML += print_passenger_checkout_html();
        checkout_elem.innerHTML += print_total_sum_checkout_html();
    }
}

function print_services_checkout_html() {
    let code = '';
    let index = 0;
    while (index < passenger_list.length) {
        let passenger = passenger_list[index];
        let services = passenger.get_services();

        let serv_index = 0;
        while (serv_index < services.length) {
            let service = services[serv_index];
            let service_id = service.get_id();
            let service_price = service.get_price();
            let service_currency = service.get_currency();
            code += '<div class=\'checkout-sub-entry\'>Service '+service_id+': '+service_price+' '+service_currency+'</div>';
            serv_index++;
        }

        index++;
    }
    return code;
}

function print_passenger_checkout_html() {
    let code = '<div class=\'checkout_mid section\'>';
    let index = 0;
    while(index < passenger_list.length) {
        let passenger = passenger_list[index];
        code += '<div class=\'checkout-entry\'>Passenger '+(index+1)+' - '+passenger.get_type()+'|'+passenger.get_full_name()+'|'+passenger.get_contacts()+'</div>';
        index++;
    }
    return code + '</div>';
}

// Pynkiwi tax -> 15%
// Todo: Add stripe payment 
function print_total_sum_checkout_html() {
    let code = '<div class=\'checkout_mid\'><div class=\'checkout-entry\'>Total plus tax: ';
    let total_amount = get_total_amount(document.getElementById("offer_payment").innerHTML);
    let total_amount_arr = total_amount.split(' ');

    let pynkiwi_tax = parseFloat(total_amount_arr[0]) * 0.15;
    let final_total_amount = parseFloat(total_amount_arr[0]) + pynkiwi_tax;
    code += final_total_amount + ' ' + total_amount_arr[1] + '</div>';
    code += '<button class=\'pay-bt\' onclick=\'send_payment()\'>Payment</button>'
    return code + '</div>';
}

function send_payment() {
    let user_id = document.getElementById("user_id").innerHTML;
    let offer_id = document.getElementById("curr_offer_id").innerHTML;
    let max_psgs = document.getElementById("pass_count").innerHTML[2];
    let total_amount = document.getElementById("offer_payment");
    let pass_list_len = passenger_list.length;
    let infants_allocated_list_len = infants_allocated.length;
    let pay_later_flag = 0;
    let input_pay_later = document.getElementById("input_pay_later");
    if (input_pay_later != null && input_pay_later.checked) {
        pay_later_flag++;
    }
    if (total_amount != null) {
        total_amount = get_total_amount(total_amount.innerHTML);
    }
    console.log('\t- Total amount: ' + total_amount + ' ; Offer id: ' + offer_id);
    if (pass_list_len + infants_allocated_list_len == max_psgs && infants_not_allocated.length == 0) {
        let url = new URL("https://pynkiwi.wpcomstaging.com/?page_id=3294");
        url.searchParams.append("user_id", user_id);
        url.searchParams.append("pay_offer_id", offer_id);
        url.searchParams.append("total_amount", total_amount); // includes currency
        if (pay_later_flag) {
            url.searchParams.append("type", "hold");
        } else {
            url.searchParams.append("type", "instant");
        }

        let index = 0;
        while (index < pass_list_len) {
            let passenger = passenger_list[index];
            passenger.set_passenger_info(url, index)
            index++;
        }

        window.location.href = url;
        // https://pynkiwi.wpcomstaging.com/?page_id=3294&pay_offer_id=off_0000ABeUHFGL98sK7wUHKK&p_0_id=pas_0000ABeUEc6Rln6s63bZmF&p_0_name=will+pere&p_0_gender=male&p_0_phone=111+111+111&p_0_email=will%40test.com&p_0_city=porto&p_0_postcode=111-11&p_0_birthday=1996-06-22&p_0_ase_0_id=ase_0000ABeUIFssrtvDBiaDaM&p_0_ase_0_quan=0&p_1_id=pas_0000ABeUEc6Rln6s63bZmG&p_1_name=maria+mei&p_1_gender=female&p_1_phone=111+111+111+11&p_1_email=maria%40test.com&p_1_city=porto&p_1_postcode=111-11&p_1_birthday=1990-07-10&p_1_ase_0_id=ase_0000ABeUIFssrtvDBiaDaM&p_1_ase_0_quan=1
    } else {
        alert('Missing passenger and/or passenger information!');
    }
}


function add_passenger() {
    if (init_flag) {
        init();
        init_flag--;
    }

    let title = document.getElementById('entry-title').value;
    let first_last_name = document.getElementById('entry-name').value;
    let gender = document.getElementById('entry-gender').value;
    let email = document.getElementById('entry-mail').value;
    let country = document.getElementById('entry-country').value;
    let phone = document.getElementById('entry-phone').value;
    let passport_id = "";
    let passport_exp_date = "";

    if (document.getElementById("passport-info").style.display != "none") {
        passport_id = document.getElementById("entry-doc_id").value;
        passport_exp_date = document.getElementById("entry-doc_exp_date").value;
    }

    if (sanitize_input(first_last_name, phone, email, country, passport_id)) {
        let birthday = document.getElementById('entry-bday').value;
        let age = get_age(birthday);
        let id = "";
        let type = "";
        let services = [];
        let infant_id = "";
        let index = -1;

        if (age <= 1) { // infant_without_seat
            //index = get_index('infant_without_seat');
            alert('Infants don\'t need to be added');
            clear_form();
            return;
        } else {
            if (age < 14) { // child
                index = get_index('child');
            } else { // adult
                index = get_index('adult');
                if (infants_not_allocated.length && document.getElementById("infant-input").checked) {
                    /* ATM infant's are being allocated
                    in a FIFO manner. In the future
                    each adult should be able to choose
                    between multiple infants.
                    */
                    infant_id = infants_not_allocated.pop();
                    console.log('\t- Infant allocated to adult');
                }
            }
        }

        if (index != -1) {
            id = passenger_ids[index];
            type = passenger_types[index];
        }

        if (document.getElementById('seg_ids') != null) {
            let ase_index = 0;
            service_ids = document.getElementById('seg_ids').innerHTML;
            arr_service_ids = service_ids.split(';');

            while (ase_index < arr_service_ids.length) {
                let ase_id = arr_service_ids[ase_index];
                if (document.getElementById('price-' + ase_id) != null && document.getElementById('quan-' + ase_id) != null) {
                    quan = document.getElementById('quan-' + ase_id).value;
                    if (parseInt(quan) != 0) {
                        price = document.getElementById('price-' + ase_id).innerHTML;
                        services.push(new Service(ase_id, quan, price));
                    }
                }
                ase_index++;
            }
        }

        let max_psgs = document.getElementById("pass_count").innerHTML[2];
        let allocated_passengers = passenger_list.length + infants_allocated.length
        if (allocated_passengers < max_psgs) {
            let passenger = new Passenger(id, type,
                title, first_last_name,
                gender, email, phone,
                birthday, country,
                services, infant_id,
                passport_id, passport_exp_date
            );
            passenger_list.push(passenger);
            passenger_ids.splice(index, 1);
            passenger_types.splice(index, 1);
            if (infant_id != "") {
                infants_allocated.push(infant_id);
                console.log('\t- Added new passenger plus infant');
            } else {
                console.log('\t- Added new passenger');
            }
            allocated_passengers = passenger_list.length + infants_allocated.length
            console.log('\t- Passenger list count: ' + allocated_passengers);
            document.getElementById("pass_count").innerHTML = allocated_passengers + "/" + max_psgs + " Passengers";
            clear_form();
            add_passenger_to_html_list(passenger);
        }
    } else {
        throw new Error('Input error');
    }
}

function add_passenger_to_html_list(passenger) {
    let pass_list = document.getElementById("pass_list");
    let pass_id = passenger.get_id();
    let pass_type = passenger.get_type();
    let pass_name = passenger.get_full_name();

    pass_list.innerHTML += '<div id=\''+pass_id+'_row\' class=\'pass_row\'><div onclick=\'call_action(event)\' id=\'shw_' + pass_id + '\' class=\'pass_tab\'>' + pass_name + ' | ' + pass_type + '</div><div id=\'chg_' + pass_id + '\' onclick=\'call_action(event)\' class=\'pass_bt\' style=\'display: none;\'>Update</div><div id=\'rmv_' + pass_id + '\' onclick=\'call_action(event)\' class=\'pass_bt\' style=\'display: none;\'>Remove</div></div>';
}

function call_action(e) {
    let elem = e.srcElememt || e.target;
    let elem_id = elem.id;

    // debug
    //alert(elem.id);

    let key = elem_id.substring(0, 3);
    let pas_id = elem_id.substring(4);
    let pas_index = get_pass_index_by_id(pas_id);

    if (pas_index != -1) {
        let pas = passenger_list[pas_index];
        if (key == "shw") { // shw_pas_0000(...)
            pass_tab_action(pas_id, pas);
        } else if (key == "chg") {
            console.log(' [*] Updating passenger info');
            update_passenger_action(pas_id, pas);
        } else if (key == "rmv") {
            console.log(' [*] Removing passenger');
            let parent = document.getElementById('pass_list');
            let max_psgs = document.getElementById("pass_count").innerHTML[2];
            let curr_pass_count = passenger_list.length + infants_allocated.length;
            pas.remove_passenger_info();
            passenger_ids.push(pas_id);
            passenger_types.push(pas.get_type());
            passenger_list.splice(pas_index, 1);
            parent.removeChild(document.getElementById(pas_id + '_row'));
            console.log('\t- Passenger list count: ' + curr_pass_count);
            document.getElementById("pass_count").innerHTML = curr_pass_count + "/" + max_psgs + " Passengers";
            document.getElementById('entry-bday').disabled = false; 
            clear_form();
        }
    } else {
        throw new Error('Passenger not found');
    }
}

/**
 * Action from passenger tab element.
 * @param {Unique string} pas_id
 * @param {Class Instance} passenger 
 */
function pass_tab_action(pas_id, passenger) {
    if (document.getElementById('chg_' + pas_id + '').style.display == 'none') {
        console.log(' [*] Showing passenger info');
        passenger.show_passenger_info();
        document.getElementById('chg_' + pas_id + '').style.display = 'inline-flex';
        document.getElementById('rmv_' + pas_id + '').style.display = 'inline-flex';
    } else {
        clear_form();
        document.getElementById('chg_' + pas_id + '').style.display = 'none';
        document.getElementById('rmv_' + pas_id + '').style.display = 'none';
    }
}

function update_passenger_action(pas_id, passenger) {
    let doc_id = ""
    let doc_exp_date = "";
    let services = [];
    if (document.getElementById('seg_ids') != null) {
        let ase_index = 0;
        service_ids = document.getElementById('seg_ids').innerHTML;
        arr_service_ids = service_ids.split(';');

        while (ase_index < arr_service_ids.length) {
            let ase_id = arr_service_ids[ase_index];
            if (document.getElementById('price-' + ase_id) != null && document.getElementById('quan-' + ase_id) != null) {
                quan = document.getElementById('quan-' + ase_id).value;
                if (parseInt(quan) != 0) {
                    price = document.getElementById('price-' + ase_id).innerHTML;
                    services.push(new Service(ase_id, quan, price));
                }
            }
            ase_index++;
        }
    }
    if (document.getElementById("entry-doc_id").style.display != "none") {
        doc_id = document.getElementById("entry-doc_id").value;
        doc_exp_date = document.getElementById("entry-doc_exp_date").value;
    }
    passenger.update_passenger_info(
        pas_id,
        document.getElementById('entry-title').value,
        document.getElementById('entry-name').value,
        document.getElementById('entry-gender').value,
        document.getElementById("entry-mail").value,
        document.getElementById("entry-country").value,
        document.getElementById("entry-phone").value,
        doc_id, doc_exp_date, services
    );
    clear_form();
}

/**
 * Sets new total_amount value, based on selected
 * passenger services.
 * @param {String} total_amount 
 */
function get_total_amount(total_amount) {
    let total_amount_arr = total_amount.split(' ');
    let amount = parseFloat(total_amount_arr[0]);
    let currency = total_amount_arr[1];

    if (check_passenger_services() != -1) {
        let index = 0;
        while (index < passenger_list.length) {
            if (passenger_types[index] == "adult") {
                let passenger = passenger_list[index];
                amount += passenger.get_services_price();

                if (passenger.get_currency() != currency) {
                    console.log('Total amount and services currency are diff.');
                }
            }
            index++;
        }
    }
    return amount + ' ' + currency;
}

/**
 * Returns index of the first
 * passenger found with additional
 * services. If no passenger is found,
 * returns -1.
 */
function check_passenger_services() {
    let index = 0;
    while (index < passenger_list.length) {
        if (passenger_types[index] == "adult") {
            let passenger = passenger_list[index];
            if (passenger.get_services().length > 0) {
                return index;
            }
        }
        index++;
    }
    return -1;
}

function refresh() {
    console.log('\t- Deleting passenger list');
    init_flag = 1;
    passenger_list.splice(0, passenger_list.length);
    passenger_ids.splice(0, passenger_ids.length);
    passenger_types.splice(0, passenger_types.length);
    infants_not_allocated.splice(0, infants_not_allocated.length);
    clear_form();
    document.getElementById('error-log').innerHTML = "";
    document.getElementById("pass_count").innerHTML = "0/" + document.getElementById("pass_count").innerHTML[2] + " Passengers";
}

class Service {
    constructor(id, quantity, price) {
        this.id = id;
        this.quantity = quantity;
        this.price = price;
        this.debug_input();
    }

    get_id() {
        return this.id;
    }

    get_quan() {
        return this.quantity;
    }

    get_price() {   // price: 12.35 EUR
        return parseInt(this.price.split(' ')[0]) * parseInt(this.quantity);
    }

    get_currency() {
        return this.price.split(' ')[1];
    }

    debug_input() {
        console.log('*** Service input debug log ***');
        console.log('\t- id: ' + this.id + ' ; price: ' + this.price);
        console.log(' *** ');
    }
}

class Passenger {
    constructor(id, type, title, name,
        gender, email, phone,
        birthday, country,
        services, infant_id,
        doc_id, doc_exp_date) {
        this.id = id;
        this.type = type;
        this.title = title;
        this.name = name;
        this.gender = gender;
        this.phone = phone;
        this.email = email;
        this.country = country;
        this.birthday = birthday;
        this.services = services;
        this.infant_id = infant_id;
        this.doc_id = doc_id;
        this.doc_exp_date = doc_exp_date;
    }

    // Sets passenger info via url query format
    // key format -> p_index_(id/name/email/etc)
    set_passenger_info(url, index, pay_later_flag) {
        let key_format = "p_" + index + "_";

        url.searchParams.append(key_format + 'id', this.id);
        url.searchParams.append(key_format + 'title', this.title);
        url.searchParams.append(key_format + 'name', this.name);
        url.searchParams.append(key_format + 'gender', this.gender);
        url.searchParams.append(key_format + 'phone', this.phone);
        url.searchParams.append(key_format + 'email', this.email);
        url.searchParams.append(key_format + 'country', this.country);
        url.searchParams.append(key_format + 'birthday', this.birthday);

        if (this.doc_id != "") {
            url.searchParams.append(key_format + 'doc_id', this.doc_id);
            url.searchParams.append(key_format + 'doc_exp_date', this.doc_exp_date);
        }

        if (pay_later_flag == 0 && this.services.length != 0) {
            let ase_index = 0;
            while (ase_index < this.services.length) {
                let service = this.services[ase_index];
                url.searchParams.append(key_format + 'ase_' + ase_index + '_id', service.get_id());
                url.searchParams.append(key_format + 'ase_' + ase_index + '_quan', service.get_quan());
                ase_index++;
            }
        }

        if (this.infant_id != "") {
            url.searchParams.append(key_format + 'infant_id', this.infant_id);
        }
    }

    get_id() {
        return this.id;
    }

    get_type() {
        return this.type;
    }

    get_full_name() {
        return this.title + ' ' + this.name;
    }

    get_contacts() {
        return this.email + ' ' + this.phone
    }

    get_services() {
        return this.services;
    }

    get_services_price() {
        let index = 0;
        let sum = 0;
        while (index < this.services.length) {
            let service = this.services[index];
            sum += service.get_price();
            index++;
        }
        return sum;
    }

    get_services_currency() {
        return this.services[0].get_currency();
    }

    show_passenger_info() {
        document.getElementById('entry-title').value = this.title;
        document.getElementById('entry-name').value = this.name;
        document.getElementById('entry-gender').value = this.gender;
        document.getElementById('entry-mail').value = this.email;
        document.getElementById('entry-country').value = this.country;
        document.getElementById('entry-phone').value = this.phone;
        document.getElementById('entry-bday').value = this.birthday;
        document.getElementById('entry-bday').disabled = true; // cant change type of passenger
        if (this.passport_id != "") {
            document.getElementById("entry-doc_id").value = this.passport_id
            document.getElementById("entry-doc_exp_date").value = this.passport_exp_date;
        }
        if (this.infant_id != "") {
            document.getElementById("infant-input").checked = true;
        }
        if (this.services.length > 0) {
            let ase_index = 0;
            while (ase_index < this.services.length) {
                let service = this.services[ase_index];
                let ase_id = service.get_id();
                document.getElementById('quan-' + ase_id).value = service.get_quan();
                ase_index++;
            }
        }
    }

    // Infant allocation and type aren't re allocated
    update_passenger_info(
        pas_id,
        title, name, gender,
        email, country, phone, 
        doc_id, doc_exp_date, services
    ) {
        if (this.title != title || this.name != name) {
            document.getElementById('shw_' + pas_id + '').innerHTML = title+' '+name+' | '+this.type;
        }
        this.title = title;
        this.name = name;
        this.gender = gender;
        this.email = email;
        this.country = country;
        this.phone = phone;
        //this.birthday = birthday;
        if (doc_id != "") {
            this.doc_id = doc_id;
            this.doc_exp_date = doc_exp_date;
        }
        if (services.length > 0) {
            this.services = services;
        }
    }

    remove_passenger_info() {
        if (this.infant_id != "") {
            infants_not_allocated.push(this.infant_id);
        }
    }

    debug_input(text_input_re, email_input_re, phone_input_re) {
        // Debug
        console.log('*** Input debug log ***');
        console.log('\t- Name: ' + this.title + ' ' + this.name + ' ; ' + this.gender);
        console.log('\t- Info: ' + this.phone + ' ; ' + this.email);
        console.log('\t- Geo: ' + this.country);

        console.log('\t- Name test: ' + text_input_re.test(this.title + ' ' + this.name));
        console.log('\t- Phone test: ' + phone_input_re.test(this.phone));
        console.log('\t- Email test: ' + email_input_re.test(this.email));
        console.log('\t- Country test: ' + text_input_re.test(this.country));
        console.log(' *** ');
    }
}

// AUX

function get_index(type) {
    let index = 0;
    while (index != passenger_types.length) {
        if (passenger_types[index] === type) {
            return index;
        }
        index++;
    }
    console.log('\t- Passenger type, ' + type + ' not found');
}

function get_pass_index_by_id(id) {
    let index = 0;
    while (index != passenger_list.length) {
        let pass = passenger_list[index];
        if (pass.get_id() == id) {
            return index;
        }
        index++;
    }
    return -1;
}

function get_age(input_date) {
    let curr_date = new Date();
    input_date = new Date(input_date);
    let age = curr_date.getFullYear() - input_date.getFullYear();
    let months = curr_date.getMonth() - input_date.getMonth();
    if (months < 0 || (months == 0 && curr_date.getDate() < input_date.getDate())) {
        age--;
    }
    return age;
}

function clear_form() {
    document.getElementById('entry-title').value = "";
    document.getElementById('entry-name').value = "";
    document.getElementById('entry-mail').value = "";
    document.getElementById('entry-bday').value = "";
    document.getElementById('entry-country').value = "";
    document.getElementById('entry-phone').value = "";
    document.getElementById('entry-gender').value = "";
    let infant_input = document.getElementById("infant-discl");
    if (infant_input != null) {
        if (infants_not_allocated.length == 0) {
            infant_input.style.display = "none";
            console.log('\t- All infant passengers allocated');
        } else {
            infant_input.checked = false;
        }
    }
    if (document.getElementById('seg_ids') != null) {
        let ase_index = 0;
        service_ids = document.getElementById('seg_ids').innerHTML;
        arr_service_ids = service_ids.split(';');

        while (ase_index < arr_service_ids.length) {
            let input = document.getElementById("quan-" + arr_service_ids[ase_index]);
            if (input != null) {
                input.value = 0;
            }
            ase_index++;
        }
    }
    document.getElementById("error-log").innerHTML = "";
}

function debug_pass_info() {
    let len = passenger_ids.length;
    let index = 0;
    let ids = "";
    let types = "";
    while (index < len) {
        ids += passenger_ids[index] + ' ';
        types += passenger_types[index] + ' ';
        index++;
    }
    console.log('\t- IDS: ' + ids + ' ; TYPES: ' + types);
}


function sanitize_input(
    name, phone, email, country, doc_id
) {
    let passport_id_re = /^[A-Za-z0-9]+$/;
    let text_input_re = /^[A-Za-z' ']+$/;
    let email_input_re = /^[A-Za-z0-9'.']+@[A-Za-z0-9'.']+$/;
    let phone_input_re = /^(\+?)[0-9' ']+$/;
    let error_log = document.getElementById('error-log');

    if (!text_input_re.test(name) || !phone_input_re.test(phone) || !email_input_re.test(email) || !text_input_re.test(country) || (doc_id != "" && !passport_id_re.test(doc_id))) {
        let elem = document.createElement('p');
        elem.innerHTML = "Input data not valid!";
        elem.classList.add('lower');
        error_log.appendChild(elem);
        return false;
    }
    return true;
}
