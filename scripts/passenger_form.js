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
 *      --> Add services support
 */

let passenger_list = [];
let passenger_ids = []; // ids
let passenger_types = []; // types
let id_index = 0;

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
        index++;
    }
}

function check_age() {
    console.log('\t- Checking age');
    let input_date = document.getElementById('entry-bday').value;
    let curr_date = new Date();
    input_date = new Date(input_date);
    let age = curr_date.getFullYear() - input_date.getFullYear();
    let months = curr_date.getMonth() - input_date.getMonth();
    
    if (months < 0 || (months == 0 && curr_date.getDate() < input_date.getDate())) {
        age--;
    }

    if (age <= 1) {
        let infant_discl = document.getElementById("infant-discl");
        infant_discl.style.opacity = 0.22;
    }
}

// TODO!
function send_payment() {
    let max_psgs = document.getElementById("pass_count").innerHTML[2];
    if (passenger_list.length == max_psgs) {
        window.location.href = "https://pynkiwi.wpcomstaging.com/?page_id=2475";
    } else {
        alert('Missing passenger information!');
    }
}

function refresh() {
    console.log('\t- Deleting passenger list');
    while (passenger_list.length) {
        passenger_list.pop();
    }
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
    let title = document.getElementById('entry-title').value;
    let first_last_name = document.getElementById('entry-name').value;
    let gender = document.getElementById('entry-gender').value;
    let email = document.getElementById('entry-mail').value;
    let birthday = document.getElementById('entry-bday').value;
    let postcode = document.getElementById('entry-postcode').value;
    let city = document.getElementById('entry-city').value;
    let phone = document.getElementById('entry-phone').value;
    let services = ""; // TODO

    let psg = new Passenger(
        title, first_last_name, gender,
        email, phone, birthday, 
        city, postcode, services
    );
    let max_psgs = document.getElementById("pass_count").innerHTML[2];
    if (psg.sanitize_input() && passenger_list.length < max_psgs) {
        passenger_list.push(psg);
        document.getElementById("pass_count").innerHTML = passenger_list.length + "/" + max_psgs + " Passengers";
        clear_form();
        console.log('Added new passenger - Current list count: ' + passenger_list.length); 
    } 
}

class Passenger {
    constructor(id, title, name, gender,
        email, phone, birthday,
        city, postcode, services) {
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

function clear_form() {
    document.getElementById('entry-title').value = "";
    document.getElementById('entry-name').value = "";
    document.getElementById('entry-mail').value = "";
    document.getElementById('entry-bday').value = "";
    document.getElementById('entry-postcode').value = "";
    document.getElementById('entry-city').value = "";
    document.getElementById('entry-phone').value = "";
    let services = ""; // TODO
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
