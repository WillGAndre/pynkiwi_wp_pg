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

    console.log('Input date: '+input_date+' ; Age: '+age);
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

// TODO!
function send_payment() {
    let max_psgs = document.getElementById("pass_count").innerHTML[2];
    let total_amount = document.getElementById("offer_payment");
    if (total_amount != null) {
        total_amount = get_total_amount(total_amount.innerHTML);
    }
    console.log('\t- Total amount: '+total_amount);
    if (passenger_list.length == max_psgs) {
        window.location.href = "https://pynkiwi.wpcomstaging.com/?page_id=2475";
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
            index = 0;
            service_ids = document.getElementById('seg_ids').innerHTML;
            arr_service_ids = service_ids.split(';');
            
            while (index < arr_service_ids.length) {
                id = arr_service_ids[index];
                if (document.getElementById('price-'+id) != null && document.getElementById('quan-'+id) != null) {
                    quan = document.getElementById('quan-' + id).value;
                    price = document.getElementById('price-'+id).innerHTML;
                    services.push(new Service(id, quan, price));
                }
                index++;
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

    get_price() {   // price: 12.35 EUR
        return parseInt(this.price.split(' ')[0]) * parseInt(this.quantity);
    }

    get_currency() {
        return this.price.split(' ')[1];
    }

    debug_input() {
        console.log('*** Service input debug log ***');
        console.log('id: '+this.id+' ; price: '+this.price);
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
        if (!text_input_re.test(this.title+' '+this.first_name+' '+this.last_name) || !phone_input_re.test(this.phone) || !email_input_re.test(this.email) || !text_input_re.test(this.city) || !postcode_input_re.test(this.postcode)) {
            let elem = document.createElement('p');
            elem.innerHTML = "Input data not valid!";
            elem.classList.add('lower');
            error_log.appendChild(elem);
            return false;
        }
        return true;
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
        console.log('Name: ' + this.title + ' ' + this.name + ' ; ' + this.gender);
        console.log('Info: ' + this.phone + ' ; ' + this.email);
        console.log('Geo: ' + this.city + ' ; ' + this.postcode);

        console.log('Name test: ' + text_input_re.test(this.title + ' ' + this.name));
        console.log('Phone test: ' + phone_input_re.test(this.phone));
        console.log('Email test: ' + email_input_re.test(this.email));
        console.log('City test: ' + text_input_re.test(this.city));
        console.log('Postcode test: ' + postcode_input_re.test(this.postcode));
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
    console.log('IDS: ' + ids + ' ; TYPES: ' + types);
}
