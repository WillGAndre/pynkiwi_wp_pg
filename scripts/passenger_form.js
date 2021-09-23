/**
 * Script used to create list of passengers,
 * add new passengers and clear the current list.
 * 
 * TODO:
 *      --> On payment click, redirect to url page 
 *          in which passenger, services, total amount 
 *          and the offer id are sent as query (or body),
 *          so that PHP can process the payment request to Duffel.
 * 
 *      --> GET OFFER ID (SEND IT VIA PhP->html->js)
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

let init_flag = 1;
function init() {
    console.log('[*] Main scripts init');
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

    console.log('\t- Input date: '+input_date+' ; Age: '+age);
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

function send_payment() {
    let offer_id = document.getElementById("curr_offer_id").innerHTML;
    let max_psgs = document.getElementById("pass_count").innerHTML[2];
    let total_amount = document.getElementById("offer_payment");
    let pass_list_len = passenger_list.length;
    if (total_amount != null) {
        total_amount = get_total_amount(total_amount.innerHTML);
    }
    console.log('\t- Total amount: '+total_amount+' ; Offer id: '+offer_id);
    if (pass_list_len == max_psgs) {
        let url = new URL("https://pynkiwi.wpcomstaging.com/?page_id=2475");
        url.searchParams.append("pay_offer_id", offer_id);

        let index = 0;
        while (index < pass_list_len) {
            let passenger = passenger_list[index];
            passenger.set_passenger_info(url, index)
            index++;
        }

        window.location.href = url;
        // window.location.href = 
        //     "https://pynkiwi.wpcomstaging.com/?page_id=2475" + "&payment=true";

        // https://pynkiwi.wpcomstaging.com/?page_id=2475&pay_offer_id=off_0000ABeUHFGL98sK7wUHKK&p_0_id=pas_0000ABeUEc6Rln6s63bZmF&p_0_name=will+pere&p_0_gender=male&p_0_phone=111+111+111&p_0_email=will%40test.com&p_0_city=porto&p_0_postcode=111-11&p_0_birthday=1996-06-22&p_0_ase_0_id=ase_0000ABeUIFssrtvDBiaDaM&p_0_ase_0_quan=0&p_1_id=pas_0000ABeUEc6Rln6s63bZmG&p_1_name=maria+mei&p_1_gender=female&p_1_phone=111+111+111+11&p_1_email=maria%40test.com&p_1_city=porto&p_1_postcode=111-11&p_1_birthday=1990-07-10&p_1_ase_0_id=ase_0000ABeUIFssrtvDBiaDaM&p_1_ase_0_quan=1
    } else {
        alert('Missing passenger information!');
    }
}

/**
 * Sets new total_amount value, based on selected
 * passenger services.
 * @param {String} total_amount 
 */
function get_total_amount(total_amount) {
    let index = 0;
    let currency = passenger_list[0].get_services_currency();
    total_amount = parseInt(total_amount);
    while (index < passenger_list.length) {
        if (passenger_types[index] === "adult") {
            let passenger = passenger_list[index];
            total_amount += passenger.get_services_price();
        }
        index++;
    }
    return total_amount + ' ' + currency;
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

function add_passenger() {
    // trigger init first
    if (init_flag) {
        init();
        init_flag--;
    }

    let birthday = document.getElementById('entry-bday').value;
    let age = get_age(birthday);
    let id = "";
    let services = [];
    let infant_id = "";
    let index = -1;

    if (age <= 1) { // infant_without_seat
        index = get_index('infant_without_seat');
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

        if (document.getElementById('seg_ids') != null) {
            let ase_index = 0;
            service_ids = document.getElementById('seg_ids').innerHTML;
            arr_service_ids = service_ids.split(';');
            
            while (ase_index < arr_service_ids.length) {
                let ase_id = arr_service_ids[ase_index];
                if (document.getElementById('price-' + ase_id) != null && document.getElementById('quan-' + ase_id) != null) {
                    quan = document.getElementById('quan-'+ase_id).value;
                    if (parseInt(quan) != 0) {
                        price = document.getElementById('price-' + ase_id).innerHTML;
                        services.push(new Service(ase_id, quan, price));
                    }
                }
                ase_index++;
            }
        }
    }
    
    if (index != -1) {
        id = passenger_ids[index];
        passenger_ids.splice(index, 1);
        passenger_types.splice(index, 1);
    }

    let title = document.getElementById('entry-title').value;
    let first_last_name = document.getElementById('entry-name').value;
    let gender = document.getElementById('entry-gender').value;
    let email = document.getElementById('entry-mail').value;
    let postcode = document.getElementById('entry-postcode').value;
    let city = document.getElementById('entry-city').value;
    let phone = document.getElementById('entry-phone').value;

    let psg = new Passenger(
        id, title, first_last_name, 
        gender, email, phone, 
        birthday, city, postcode, 
        services, infant_id
    );
    let max_psgs = document.getElementById("pass_count").innerHTML[2];
    if (psg.sanitize_input() && passenger_list.length < max_psgs) {
        passenger_list.push(psg);
        document.getElementById("pass_count").innerHTML = passenger_list.length + "/" + max_psgs + " Passengers";
        clear_form();
        console.log('Added new passenger - Current list count: ' + passenger_list.length); 
    } else {
        // Beacuse the service is created beforehand,
        // if the pass info is invalid then pop();
        services.pop();
    }
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
        console.log('\t- id: '+this.id+' ; price: '+this.price);
        console.log(' *** ');
    }
}

class Passenger {
    constructor(id, title, name, 
        gender, email, phone, 
        birthday, city, postcode, 
        services, infant_id) {
        this.id = id;
        this.title = title;
        this.name = name;
        this.gender = gender;
        this.phone = phone;
        this.email = email;
        this.city = city;
        this.postcode = postcode;
        this.birthday = birthday;
        this.services = services;
        this.infant_id = infant_id;
    }

    sanitize_input() {
        let text_input_re = /^[A-Za-z' ']+$/;
        let email_input_re = /^[A-Za-z0-9'.']+@[A-Za-z0-9'.']+$/;
        let phone_input_re = /^(\+?)[0-9' ']+$/;
        let postcode_input_re = /^([0-9]+)-([0-9]+)$/;
        let error_log = document.getElementById('error-log');

        //this.debug_input(text_input_re, email_input_re, phone_input_re, postcode_input_re);
        if (!text_input_re.test(this.title+' '+this.name) || !phone_input_re.test(this.phone) || !email_input_re.test(this.email) || !text_input_re.test(this.city) || !postcode_input_re.test(this.postcode)) {
            let elem = document.createElement('p');
            elem.innerHTML = "Input data not valid!";
            elem.classList.add('lower');
            error_log.appendChild(elem);
            return false;
        }
        return true;
    }

    // Sets passenger info via url query format
    // key format -> p_index_(id/name/email/etc)
    set_passenger_info(url, index) {
        let key_format = "p_"+index+"_";

        url.searchParams.append(key_format + 'id', this.id);
        url.searchParams.append(key_format + 'name', this.name);
        url.searchParams.append(key_format + 'gender', this.gender);
        url.searchParams.append(key_format + 'phone', this.phone);
        url.searchParams.append(key_format + 'email', this.email);
        url.searchParams.append(key_format + 'city', this.city);
        url.searchParams.append(key_format + 'postcode', this.postcode);
        url.searchParams.append(key_format + 'birthday', this.birthday);

        if (this.services.length != 0) {
            let ase_index = 0;
            while (ase_index < this.services.length) {
                let service = this.services[ase_index];
                url.searchParams.append(key_format + 'ase_'+ase_index+'_id', service.get_id());
                url.searchParams.append(key_format + 'ase_'+ase_index+'_quan', service.get_quan());
                ase_index++;
            }
        }
        
        if (this.infant_id != "") {
            url.searchParams.append(key_format + 'infant_id', this.infant_id);   
        }
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

    debug_input(text_input_re, email_input_re, phone_input_re, postcode_input_re) {
        // Debug
        console.log('*** Input debug log ***');
        console.log('\t- Name: ' + this.title + ' ' + this.name + ' ; ' + this.gender);
        console.log('\t- Info: ' + this.phone + ' ; ' + this.email);
        console.log('\t- Geo: ' + this.city + ' ; ' + this.postcode);

        console.log('\t- Name test: ' + text_input_re.test(this.title + ' ' + this.name));
        console.log('\t- Phone test: ' + phone_input_re.test(this.phone));
        console.log('\t- Email test: ' + email_input_re.test(this.email));
        console.log('\t- City test: ' + text_input_re.test(this.city));
        console.log('\t- Postcode test: ' + postcode_input_re.test(this.postcode));
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
    console.log('\t- Passenger type, '+type+' not found');
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

// TODO: Clear services and uncheck infant_input (not unchecking)
function clear_form() {
    document.getElementById('entry-title').value = "";
    document.getElementById('entry-name').value = "";
    document.getElementById('entry-mail').value = "";
    document.getElementById('entry-bday').value = "";
    document.getElementById('entry-postcode').value = "";
    document.getElementById('entry-city').value = "";
    document.getElementById('entry-phone').value = "";
    let infant_input = document.getElementById("infant-input");
    if (infant_input != null) {
        infant_input.unchecked;
    }
    // let service_ids = document.getElementById('seg_ids');
    // if (service_ids != null) {
    //     service_ids = service_ids.innerHTML.split(';');
    // }
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
